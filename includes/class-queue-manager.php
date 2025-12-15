<?php
/**
 * Arxiu Queue Manager - Gestió de cua de processament d'imatges
 * 
 * @package Arxiu_Monovar
 * @since 17.1.0
 * 
 * Sistema de cua per processar imatges en background:
 * - Afegeix imatges a la cua quan es pugen
 * - Processa en batches cada 2 minuts via WP-Cron
 * - Respecta límits de quota de l'API
 * - Retry automàtic si falla
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Queue_Manager {
    
    /**
     * Nom de la taula de la cua
     */
    const TABLE_NAME = 'arxiu_queue';
    
    /**
     * Status constants
     */
    const STATUS_PENDING    = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED  = 'completed';
    const STATUS_FAILED     = 'failed';
    const STATUS_SKIPPED    = 'skipped';
    
    /**
     * Configuració de la cua
     */
    const CONFIG = [
        'batch_size'       => 5,       // Imatges per execució
        'interval_seconds' => 120,     // Cada 2 minuts
        'max_attempts'     => 3,       // Reintents màxims
        'daily_limit'      => 1400,    // Deixem marge (1500 RPD)
        'pause_on_429'     => 300,     // Pausa 5 min si quota esgotada
    ];
    
    /**
     * Singleton
     */
    private static $instance = null;
    
    /**
     * Obtenir instància
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Hooks
        add_action( 'init', [ $this, 'register_cron_schedule' ] );
        add_action( 'arxiu_process_queue', [ $this, 'process_batch' ] );
        
        // Hook quan es puja una imatge (opcional - activar des de settings)
        if ( get_option( 'arxiu_auto_queue_uploads', false ) ) {
            add_action( 'add_attachment', [ $this, 'maybe_queue_attachment' ] );
        }
    }
    
    /**
     * Registrar schedule personalitzat per WP-Cron
     */
    public function register_cron_schedule() {
        add_filter( 'cron_schedules', function( $schedules ) {
            $schedules['arxiu_every_2_min'] = [
                'interval' => self::CONFIG['interval_seconds'],
                'display'  => __( 'Cada 2 minuts (Arxiu Queue)', 'arxiu-monovar' ),
            ];
            return $schedules;
        });
        
        // Programar si no està programat
        if ( ! wp_next_scheduled( 'arxiu_process_queue' ) ) {
            wp_schedule_event( time(), 'arxiu_every_2_min', 'arxiu_process_queue' );
        }
    }
    
    /**
     * Obtenir nom complet de la taula
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE_NAME;
    }
    
    /**
     * Crear taula de la cua (executar en activació del plugin)
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            attachment_id BIGINT UNSIGNED NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed', 'skipped') DEFAULT 'pending',
            priority TINYINT DEFAULT 5,
            attempts TINYINT DEFAULT 0,
            batch_id VARCHAR(32) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            error_message TEXT NULL,
            processing_time_ms INT NULL,
            result_summary TEXT NULL,
            PRIMARY KEY (id),
            INDEX idx_status (status),
            INDEX idx_attachment (attachment_id),
            INDEX idx_batch (batch_id),
            INDEX idx_priority_status (priority, status)
        ) {$charset_collate};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        
        // Guardar versió de la taula
        update_option( 'arxiu_queue_db_version', '1.0.0' );
        
        return true;
    }
    
    /**
     * Afegir imatge a la cua
     */
    public function add_to_queue( $attachment_id, $priority = 5, $batch_id = null ) {
        global $wpdb;
        
        // Verificar que és una imatge vàlida
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return false;
        }
        
        // Verificar que no està ja a la cua (pending o processing)
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . self::get_table_name() . " 
             WHERE attachment_id = %d AND status IN ('pending', 'processing')",
            $attachment_id
        ));
        
        if ( $existing ) {
            return (int) $existing;
        }
        
        $result = $wpdb->insert(
            self::get_table_name(),
            [
                'attachment_id' => $attachment_id,
                'priority'      => $priority,
                'batch_id'      => $batch_id,
                'status'        => 'pending',
                'created_at'    => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );
        
        if ( $result ) {
            do_action( 'arxiu_queue_item_added', $attachment_id, $wpdb->insert_id );
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Afegir múltiples imatges a la cua (bulk)
     */
    public function add_bulk_to_queue( $attachment_ids, $batch_id = null ) {
        if ( empty( $attachment_ids ) ) {
            return [ 'added' => 0, 'skipped' => 0, 'batch_id' => null ];
        }
        
        if ( ! $batch_id ) {
            $batch_id = 'batch_' . wp_generate_password( 12, false );
        }
        
        $added = 0;
        $skipped = 0;
        $count = count( $attachment_ids );
        $priority = min( 10, max( 3, ceil( $count / 10 ) ) );
        
        foreach ( $attachment_ids as $attachment_id ) {
            $result = $this->add_to_queue( (int) $attachment_id, $priority, $batch_id );
            if ( $result ) {
                $added++;
            } else {
                $skipped++;
            }
        }
        
        do_action( 'arxiu_queue_bulk_added', $batch_id, $added, $skipped );
        
        return [
            'added'    => $added,
            'skipped'  => $skipped,
            'batch_id' => $batch_id,
            'priority' => $priority,
        ];
    }
    
    /**
     * Hook per afegir automàticament attachments nous
     */
    public function maybe_queue_attachment( $attachment_id ) {
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }
        
        $today_count = $this->get_today_processed_count();
        if ( $today_count >= self::CONFIG['daily_limit'] ) {
            return;
        }
        
        $this->add_to_queue( $attachment_id, 5 );
    }
    
    /**
     * Processar un batch d'imatges (cridat per WP-Cron)
     */
    public function process_batch() {
        global $wpdb;
        
        // Verificar pausa
        $pause_until = get_transient( 'arxiu_queue_paused' );
        if ( $pause_until && time() < $pause_until ) {
            $this->log( 'Cua pausada fins ' . date( 'H:i:s', $pause_until ) );
            return;
        }
        
        // Verificar límit diari
        $today_count = $this->get_today_processed_count();
        if ( $today_count >= self::CONFIG['daily_limit'] ) {
            $this->log( 'Límit diari assolit: ' . $today_count . '/' . self::CONFIG['daily_limit'] );
            return;
        }
        
        $table = self::get_table_name();
        $batch_size = min( 
            self::CONFIG['batch_size'], 
            self::CONFIG['daily_limit'] - $today_count 
        );
        
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE status = 'pending' 
             AND attempts < %d
             ORDER BY priority ASC, created_at ASC 
             LIMIT %d",
            self::CONFIG['max_attempts'],
            $batch_size
        ));
        
        if ( empty( $items ) ) {
            $this->log( 'No hi ha items pendents a la cua' );
            return;
        }
        
        $this->log( 'Processant batch de ' . count( $items ) . ' imatges' );
        
        $processed = 0;
        $failed = 0;
        
        foreach ( $items as $item ) {
            $result = $this->process_single_item( $item );
            
            if ( $result === 'quota_exceeded' ) {
                set_transient( 'arxiu_queue_paused', time() + self::CONFIG['pause_on_429'], self::CONFIG['pause_on_429'] + 60 );
                $this->log( 'Quota esgotada - pausant cua 5 minuts' );
                break;
            }
            
            if ( $result === true ) {
                $processed++;
            } else {
                $failed++;
            }
            
            usleep( 500000 ); // 0.5 segons entre processaments
        }
        
        $this->log( "Batch completat: {$processed} processats, {$failed} fallats" );
        do_action( 'arxiu_queue_batch_completed', $processed, $failed );
    }
    
    /**
     * Processar un item individual
     */
    private function process_single_item( $item ) {
        global $wpdb;
        $table = self::get_table_name();
        $start_time = microtime( true );
        
        // Marcar com processing
        $wpdb->update(
            $table,
            [
                'status'     => 'processing',
                'started_at' => current_time( 'mysql' ),
                'attempts'   => $item->attempts + 1,
            ],
            [ 'id' => $item->id ],
            [ '%s', '%s', '%d' ],
            [ '%d' ]
        );
        
        // Verificar attachment
        if ( ! wp_attachment_is_image( $item->attachment_id ) ) {
            $wpdb->update(
                $table,
                [
                    'status'        => 'skipped',
                    'completed_at'  => current_time( 'mysql' ),
                    'error_message' => 'Attachment no trobat o no és imatge',
                ],
                [ 'id' => $item->id ]
            );
            return false;
        }
        
        // API key
        $api_key = Arxiu_Credentials::get_api_key();
        if ( empty( $api_key ) ) {
            $wpdb->update(
                $table,
                [
                    'status'        => 'failed',
                    'error_message' => 'API key no configurada',
                ],
                [ 'id' => $item->id ]
            );
            return false;
        }
        
        // Analitzar
        $filename = get_the_title( $item->attachment_id );
        $result = Arxiu_Gemini_API::analyze_complete( $item->attachment_id, $filename, $api_key );
        $processing_time = round( ( microtime( true ) - $start_time ) * 1000 );
        
        if ( is_wp_error( $result ) ) {
            $error_code = $result->get_error_code();
            $error_msg = $result->get_error_message();
            
            if ( $error_code === 'rate_limit' || $error_code === 'quota_exceeded' ) {
                $wpdb->update(
                    $table,
                    [
                        'status'           => 'pending',
                        'error_message'    => $error_msg,
                        'processing_time_ms' => $processing_time,
                    ],
                    [ 'id' => $item->id ]
                );
                return 'quota_exceeded';
            }
            
            $new_status = ( $item->attempts + 1 >= self::CONFIG['max_attempts'] ) ? 'failed' : 'pending';
            $wpdb->update(
                $table,
                [
                    'status'           => $new_status,
                    'error_message'    => $error_msg,
                    'processing_time_ms' => $processing_time,
                ],
                [ 'id' => $item->id ]
            );
            return false;
        }
        
        // Guardar metadades
        $this->save_analysis_to_attachment( $item->attachment_id, $result );
        
        $summary = wp_json_encode( [
            'genre'    => $result['genre'] ?? null,
            'poi'      => $result['ubicacio_estimate']['poi_slug'] ?? null,
            'year'     => $result['date_estimate']['year'] ?? null,
            'tags_count' => count( $result['tags_val'] ?? [] ),
        ] );
        
        $wpdb->update(
            $table,
            [
                'status'           => 'completed',
                'completed_at'     => current_time( 'mysql' ),
                'processing_time_ms' => $processing_time,
                'result_summary'   => $summary,
                'error_message'    => null,
            ],
            [ 'id' => $item->id ]
        );
        
        do_action( 'arxiu_queue_item_completed', $item->attachment_id, $result );
        return true;
    }
    
    /**
     * Guardar anàlisi com a metadades
     */
    private function save_analysis_to_attachment( $attachment_id, $result ) {
        update_post_meta( $attachment_id, '_arxiu_analysis', $result );
        update_post_meta( $attachment_id, '_arxiu_analyzed_at', current_time( 'mysql' ) );
        
        if ( ! empty( $result['genre'] ) ) {
            update_post_meta( $attachment_id, '_arxiu_genre', $result['genre'] );
        }
        if ( ! empty( $result['tags_val'] ) ) {
            update_post_meta( $attachment_id, '_arxiu_tags', $result['tags_val'] );
        }
        if ( ! empty( $result['date_estimate']['year'] ) ) {
            update_post_meta( $attachment_id, '_arxiu_year', $result['date_estimate']['year'] );
        }
        if ( ! empty( $result['ubicacio_estimate']['poi_slug'] ) ) {
            update_post_meta( $attachment_id, '_arxiu_poi', $result['ubicacio_estimate']['poi_slug'] );
        }
        if ( ! empty( $result['ubicacio_estimate']['barri_slug'] ) ) {
            update_post_meta( $attachment_id, '_arxiu_barri', $result['ubicacio_estimate']['barri_slug'] );
        }
    }
    
    /**
     * Obtenir anàlisi guardada
     */
    public static function get_attachment_analysis( $attachment_id ) {
        $analysis = get_post_meta( $attachment_id, '_arxiu_analysis', true );
        if ( $analysis ) {
            $analysis['_analyzed_at'] = get_post_meta( $attachment_id, '_arxiu_analyzed_at', true );
        }
        return $analysis ?: null;
    }
    
    /**
     * Verificar si attachment analitzat
     */
    public static function is_attachment_analyzed( $attachment_id ) {
        return (bool) get_post_meta( $attachment_id, '_arxiu_analyzed_at', true );
    }
    
    /**
     * Obtenir estadístiques
     */
    public function get_stats() {
        global $wpdb;
        $table = self::get_table_name();
        
        $stats = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
            OBJECT_K
        );
        
        $result = [
            'pending'    => isset( $stats['pending'] ) ? (int) $stats['pending']->count : 0,
            'processing' => isset( $stats['processing'] ) ? (int) $stats['processing']->count : 0,
            'completed'  => isset( $stats['completed'] ) ? (int) $stats['completed']->count : 0,
            'failed'     => isset( $stats['failed'] ) ? (int) $stats['failed']->count : 0,
            'skipped'    => isset( $stats['skipped'] ) ? (int) $stats['skipped']->count : 0,
        ];
        
        $result['total'] = array_sum( $result );
        $result['today_processed'] = $this->get_today_processed_count();
        $result['daily_limit'] = self::CONFIG['daily_limit'];
        $result['quota_remaining'] = max( 0, self::CONFIG['daily_limit'] - $result['today_processed'] );
        
        $pause_until = get_transient( 'arxiu_queue_paused' );
        $result['is_paused'] = $pause_until && time() < $pause_until;
        $result['pause_until'] = $result['is_paused'] ? date( 'H:i:s', $pause_until ) : null;
        
        $next_run = wp_next_scheduled( 'arxiu_process_queue' );
        $result['next_run'] = $next_run ? date( 'H:i:s', $next_run ) : null;
        
        return $result;
    }
    
    /**
     * Comptador de processats avui
     */
    private function get_today_processed_count() {
        global $wpdb;
        $table = self::get_table_name();
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} 
             WHERE status = 'completed' 
             AND DATE(completed_at) = CURDATE()"
        );
    }
    
    /**
     * Netejar items antics
     */
    public function cleanup_old_items( $days = 7 ) {
        global $wpdb;
        $table = self::get_table_name();
        
        return $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table} 
             WHERE status IN ('completed', 'skipped') 
             AND completed_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
    
    /**
     * Reiniciar items fallats
     */
    public function retry_failed_items() {
        global $wpdb;
        $table = self::get_table_name();
        
        return $wpdb->update(
            $table,
            [ 'status' => 'pending', 'attempts' => 0 ],
            [ 'status' => 'failed' ],
            [ '%s', '%d' ],
            [ '%s' ]
        );
    }
    
    /**
     * Obtenir el proper batch per processar (versió pública)
     */
    public function get_next_batch( $size = null ) {
        global $wpdb;
        $table = self::get_table_name();
        $batch_size = $size ?: self::CONFIG['batch_size'];
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE status = 'pending' 
             AND attempts < %d
             ORDER BY priority ASC, created_at ASC 
             LIMIT %d",
            self::CONFIG['max_attempts'],
            $batch_size
        ));
    }
    
    /**
     * Processar un item individual (versió pública)
     */
    public function process_single( $item ) {
        return $this->process_single_item( $item );
    }
    
    /**
     * Despausar la cua
     */
    public function unpause_queue() {
        delete_transient( 'arxiu_queue_paused' );
        $this->log( 'Cua despausada manualment' );
    }
    
    /**
     * Pausar la cua
     */
    public function pause_queue( $seconds = null ) {
        $pause_seconds = $seconds ?: self::CONFIG['pause_on_429'];
        set_transient( 'arxiu_queue_paused', time() + $pause_seconds, $pause_seconds + 60 );
        $this->log( 'Cua pausada per ' . $pause_seconds . ' segons' );
    }
    
    /**
     * Afegir batch a la cua (alias per add_bulk_to_queue)
     */
    public function add_batch_to_queue( $attachment_ids, $priority = 5 ) {
        return $this->add_bulk_to_queue( $attachment_ids, null );
    }
    
    /**
     * Buidar la cua
     */
    public function clear_queue() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::get_table_name() );
    }
    
    /**
     * Obtenir items recents
     */
    public function get_recent_items( $limit = 20 ) {
        global $wpdb;
        $table = self::get_table_name();
        
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT q.*, 
                    p.post_title as attachment_title,
                    pm.meta_value as attachment_file
             FROM {$table} q
             LEFT JOIN {$wpdb->posts} p ON q.attachment_id = p.ID
             LEFT JOIN {$wpdb->postmeta} pm ON q.attachment_id = pm.post_id AND pm.meta_key = '_wp_attached_file'
             ORDER BY q.created_at DESC
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Logging
     */
    private function log( $message ) {
        if ( defined( 'ARXIU_DEBUG' ) && ARXIU_DEBUG ) {
            error_log( '[Arxiu Queue] ' . $message );
        }
    }
}

// Inicialitzar
add_action( 'plugins_loaded', function() {
    Arxiu_Queue_Manager::get_instance();
}, 15 );
