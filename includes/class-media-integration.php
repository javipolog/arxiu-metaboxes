<?php
/**
 * Arxiu Media Integration - Integració amb la Mediateca de WordPress
 * 
 * @package Arxiu_Monovar
 * @since 17.2.0
 * 
 * Funcionalitats:
 * - Columna d'estat d'anàlisi IA a la mediateca
 * - Acció bulk per analitzar múltiples imatges
 * - Auto-queue d'imatges noves (opcional)
 * - Indicadors visuals d'estat
 * - Metabox d'anàlisi IA a l'attachment detail
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Media_Integration {
    
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
        // Només a l'admin
        if ( ! is_admin() ) {
            return;
        }
        
        // Columnes a la mediateca (mode llista)
        add_filter( 'manage_media_columns', [ $this, 'add_analysis_column' ] );
        add_action( 'manage_media_custom_column', [ $this, 'render_analysis_column' ], 10, 2 );
        
        // Bulk actions
        add_filter( 'bulk_actions-upload', [ $this, 'add_bulk_actions' ] );
        add_filter( 'handle_bulk_actions-upload', [ $this, 'handle_bulk_actions' ], 10, 3 );
        
        // Attachment details (modal)
        add_filter( 'attachment_fields_to_edit', [ $this, 'add_attachment_fields' ], 10, 2 );
        
        // Auto-queue en upload (si activat)
        if ( get_option( 'arxiu_auto_queue_uploads', false ) ) {
            add_action( 'add_attachment', [ $this, 'auto_queue_new_attachment' ], 20 );
        }
        
        // AJAX handlers
        add_action( 'wp_ajax_arxiu_media_analyze', [ $this, 'ajax_analyze_attachment' ] );
        add_action( 'wp_ajax_arxiu_media_queue', [ $this, 'ajax_queue_attachment' ] );
        add_action( 'wp_ajax_arxiu_media_get_status', [ $this, 'ajax_get_status' ] );
        
        // Scripts i estils
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        
        // Notices
        add_action( 'admin_notices', [ $this, 'show_bulk_action_notices' ] );
    }
    
    /**
     * Afegir columna d'anàlisi IA
     */
    public function add_analysis_column( $columns ) {
        $columns['arxiu_analysis'] = '🤖 IA';
        return $columns;
    }
    
    /**
     * Renderitzar columna d'anàlisi
     */
    public function render_analysis_column( $column_name, $attachment_id ) {
        if ( $column_name !== 'arxiu_analysis' ) {
            return;
        }
        
        // Verificar si és imatge
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            echo '<span class="arxiu-media-status arxiu-status-na" title="No és imatge">—</span>';
            return;
        }
        
        // Verificar si està analitzat
        $analyzed_at = get_post_meta( $attachment_id, '_arxiu_analyzed_at', true );
        
        if ( $analyzed_at ) {
            $analysis = get_post_meta( $attachment_id, '_arxiu_analysis', true );
            $genre = isset( $analysis['genre'] ) ? $analysis['genre'] : '?';
            $confidence = isset( $analysis['genre_confidence'] ) ? $analysis['genre_confidence'] : 'medium';
            
            $genre_emoji = [
                'fotografia' => '📷',
                'document'   => '📄',
                'grafisme'   => '🎨',
            ];
            
            $emoji = $genre_emoji[ $genre ] ?? '✅';
            $title = sprintf( 
                '%s (%s) - Analitzat: %s', 
                ucfirst( $genre ), 
                $confidence,
                date_i18n( 'd/m/Y H:i', strtotime( $analyzed_at ) )
            );
            
            echo '<span class="arxiu-media-status arxiu-status-analyzed" title="' . esc_attr( $title ) . '">' . $emoji . '</span>';
            return;
        }
        
        // Verificar si està a la cua
        global $wpdb;
        $table = $wpdb->prefix . 'arxiu_queue';
        $queue_status = $wpdb->get_var( $wpdb->prepare(
            "SELECT status FROM {$table} WHERE attachment_id = %d ORDER BY id DESC LIMIT 1",
            $attachment_id
        ));
        
        if ( $queue_status === 'pending' ) {
            echo '<span class="arxiu-media-status arxiu-status-pending" title="A la cua">⏳</span>';
        } elseif ( $queue_status === 'processing' ) {
            echo '<span class="arxiu-media-status arxiu-status-processing" title="Processant...">🔄</span>';
        } elseif ( $queue_status === 'failed' ) {
            echo '<span class="arxiu-media-status arxiu-status-failed" title="Error - Reintentar">❌</span>';
        } else {
            // No analitzat, no a la cua
            echo '<button type="button" class="button button-small arxiu-queue-btn" data-id="' . esc_attr( $attachment_id ) . '" title="Afegir a la cua">➕</button>';
        }
    }
    
    /**
     * Afegir accions bulk
     */
    public function add_bulk_actions( $actions ) {
        $actions['arxiu_analyze'] = '🤖 Afegir a cua IA';
        return $actions;
    }
    
    /**
     * Gestionar accions bulk
     */
    public function handle_bulk_actions( $redirect_url, $action, $post_ids ) {
        if ( $action !== 'arxiu_analyze' ) {
            return $redirect_url;
        }
        
        if ( empty( $post_ids ) ) {
            return $redirect_url;
        }
        
        // Filtrar només imatges
        $image_ids = array_filter( $post_ids, 'wp_attachment_is_image' );
        
        if ( empty( $image_ids ) ) {
            return add_query_arg( 'arxiu_bulk_result', 'no_images', $redirect_url );
        }
        
        // Afegir a la cua
        $queue = Arxiu_Queue_Manager::get_instance();
        $result = $queue->add_bulk_to_queue( $image_ids );
        
        return add_query_arg( [
            'arxiu_bulk_result' => 'success',
            'arxiu_added'       => $result['added'],
            'arxiu_skipped'     => $result['skipped'],
        ], $redirect_url );
    }
    
    /**
     * Mostrar notificacions de bulk actions
     */
    public function show_bulk_action_notices() {
        if ( ! isset( $_GET['arxiu_bulk_result'] ) ) {
            return;
        }
        
        $result = sanitize_text_field( $_GET['arxiu_bulk_result'] );
        
        if ( $result === 'no_images' ) {
            echo '<div class="notice notice-warning is-dismissible"><p>⚠️ No s\'han trobat imatges a la selecció.</p></div>';
        } elseif ( $result === 'success' ) {
            $added = isset( $_GET['arxiu_added'] ) ? absint( $_GET['arxiu_added'] ) : 0;
            $skipped = isset( $_GET['arxiu_skipped'] ) ? absint( $_GET['arxiu_skipped'] ) : 0;
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ <strong>' . $added . '</strong> imatges afegides a la cua IA.';
            if ( $skipped > 0 ) {
                echo ' (' . $skipped . ' ja estaven a la cua)';
            }
            echo ' <a href="' . esc_url( admin_url( 'admin.php?page=arxiu-queue' ) ) . '">Veure cua →</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Afegir camps al detall d'attachment (modal)
     */
    public function add_attachment_fields( $form_fields, $post ) {
        // Només per imatges
        if ( ! wp_attachment_is_image( $post->ID ) ) {
            return $form_fields;
        }
        
        $analyzed_at = get_post_meta( $post->ID, '_arxiu_analyzed_at', true );
        $analysis = get_post_meta( $post->ID, '_arxiu_analysis', true );
        
        $html = '<div class="arxiu-attachment-analysis">';
        
        if ( $analyzed_at && $analysis ) {
            // Mostrar resum de l'anàlisi
            $genre = isset( $analysis['genre'] ) ? ucfirst( $analysis['genre'] ) : 'Desconegut';
            $tags = isset( $analysis['tags_val'] ) ? implode( ', ', array_slice( $analysis['tags_val'], 0, 5 ) ) : '';
            $summary = isset( $analysis['summary'] ) ? $analysis['summary'] : '';
            
            $html .= '<p><strong>🤖 Analitzat:</strong> ' . date_i18n( 'd/m/Y H:i', strtotime( $analyzed_at ) ) . '</p>';
            $html .= '<p><strong>Gènere:</strong> ' . esc_html( $genre ) . '</p>';
            if ( $tags ) {
                $html .= '<p><strong>Tags:</strong> ' . esc_html( $tags ) . '</p>';
            }
            if ( $summary ) {
                $html .= '<p><strong>Resum:</strong> ' . esc_html( wp_trim_words( $summary, 20 ) ) . '</p>';
            }
            
            $html .= '<button type="button" class="button arxiu-reanalyze-btn" data-id="' . esc_attr( $post->ID ) . '">🔄 Re-analitzar</button>';
        } else {
            // Botó per analitzar
            $html .= '<p>Aquesta imatge no ha estat analitzada per IA.</p>';
            $html .= '<button type="button" class="button button-primary arxiu-analyze-btn" data-id="' . esc_attr( $post->ID ) . '">🤖 Analitzar ara</button>';
            $html .= ' <button type="button" class="button arxiu-queue-btn" data-id="' . esc_attr( $post->ID ) . '">➕ Afegir a cua</button>';
        }
        
        $html .= '</div>';
        
        $form_fields['arxiu_analysis'] = [
            'label' => '🤖 Anàlisi IA',
            'input' => 'html',
            'html'  => $html,
        ];
        
        return $form_fields;
    }
    
    /**
     * Auto-queue noves imatges
     */
    public function auto_queue_new_attachment( $attachment_id ) {
        // Verificar que és imatge
        if ( ! wp_attachment_is_image( $attachment_id ) ) {
            return;
        }
        
        // Verificar quota disponible
        $queue = Arxiu_Queue_Manager::get_instance();
        $stats = $queue->get_stats();
        
        if ( $stats['quota_remaining'] < 1 ) {
            // No afegir si quota esgotada
            return;
        }
        
        // Afegir a la cua amb prioritat normal
        $queue->add_to_queue( $attachment_id, 5 );
        
        if ( defined( 'ARXIU_DEBUG' ) && ARXIU_DEBUG ) {
            error_log( '[Arxiu Media] Auto-queued attachment: ' . $attachment_id );
        }
    }
    
    /**
     * AJAX: Analitzar attachment immediatament
     */
    public function ajax_analyze_attachment() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            wp_send_json_error( [ 'message' => 'ID d\'imatge invàlid' ] );
        }
        
        $api_key = Arxiu_Credentials::get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API key no configurada' ] );
        }
        
        // Analitzar
        $title = get_the_title( $attachment_id );
        $result = Arxiu_Gemini_API::analyze_complete( $attachment_id, $title, $api_key );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        
        // Guardar metadata
        update_post_meta( $attachment_id, '_arxiu_analysis', $result );
        update_post_meta( $attachment_id, '_arxiu_analyzed_at', current_time( 'mysql' ) );
        
        wp_send_json_success( [
            'message'  => 'Anàlisi completada',
            'analysis' => $result,
        ]);
    }
    
    /**
     * AJAX: Afegir attachment a la cua
     */
    public function ajax_queue_attachment() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
            wp_send_json_error( [ 'message' => 'ID d\'imatge invàlid' ] );
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        $result = $queue->add_to_queue( $attachment_id, 5 );
        
        if ( $result ) {
            wp_send_json_success( [ 'message' => 'Afegit a la cua', 'queue_id' => $result ] );
        } else {
            wp_send_json_error( [ 'message' => 'Ja està a la cua o error' ] );
        }
    }
    
    /**
     * AJAX: Obtenir estat d'un attachment
     */
    public function ajax_get_status() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id ) {
            wp_send_json_error( [ 'message' => 'ID invàlid' ] );
        }
        
        $analyzed = Arxiu_Queue_Manager::is_attachment_analyzed( $attachment_id );
        $analysis = $analyzed ? Arxiu_Queue_Manager::get_attachment_analysis( $attachment_id ) : null;
        
        // Estat a la cua
        global $wpdb;
        $table = $wpdb->prefix . 'arxiu_queue';
        $queue_status = $wpdb->get_var( $wpdb->prepare(
            "SELECT status FROM {$table} WHERE attachment_id = %d ORDER BY id DESC LIMIT 1",
            $attachment_id
        ));
        
        wp_send_json_success( [
            'analyzed'     => $analyzed,
            'analysis'     => $analysis,
            'queue_status' => $queue_status,
        ]);
    }
    
    /**
     * Enqueue assets per la mediateca
     */
    public function enqueue_assets( $hook ) {
        // Només a pàgines de mediateca
        if ( ! in_array( $hook, [ 'upload.php', 'post.php', 'post-new.php' ] ) ) {
            return;
        }
        
        // CSS inline per la columna
        wp_add_inline_style( 'arxiu-admin-css', $this->get_inline_css() );
        
        // JS per interaccions
        wp_add_inline_script( 'arxiu-admin-js', $this->get_inline_js(), 'after' );
    }
    
    /**
     * CSS per la mediateca
     */
    private function get_inline_css() {
        return '
            /* Columna IA a la mediateca */
            .column-arxiu_analysis {
                width: 50px;
                text-align: center;
            }
            
            .arxiu-media-status {
                display: inline-block;
                font-size: 16px;
                cursor: help;
            }
            
            .arxiu-status-analyzed { color: #46b450; }
            .arxiu-status-pending { color: #f0b849; }
            .arxiu-status-processing { color: #00a0d2; animation: arxiu-spin 1s linear infinite; }
            .arxiu-status-failed { color: #dc3232; }
            .arxiu-status-na { color: #999; }
            
            @keyframes arxiu-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .arxiu-queue-btn {
                padding: 2px 8px !important;
                min-height: auto !important;
                font-size: 12px !important;
            }
            
            /* Attachment detail modal */
            .arxiu-attachment-analysis {
                padding: 10px;
                background: #f9f9f9;
                border-radius: 4px;
                margin: 5px 0;
            }
            
            .arxiu-attachment-analysis p {
                margin: 5px 0;
            }
            
            .arxiu-attachment-analysis .button {
                margin-top: 10px;
                margin-right: 5px;
            }
        ';
    }
    
    /**
     * JavaScript per interaccions
     */
    private function get_inline_js() {
        return "
        (function($) {
            'use strict';
            
            // Afegir a cua des de la columna
            $(document).on('click', '.arxiu-queue-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                
                btn.prop('disabled', true).text('...');
                
                $.post(ajaxurl, {
                    action: 'arxiu_media_queue',
                    nonce: arxiu_admin.nonce,
                    attachment_id: id
                }, function(response) {
                    if (response.success) {
                        btn.replaceWith('<span class=\"arxiu-media-status arxiu-status-pending\" title=\"A la cua\">⏳</span>');
                    } else {
                        btn.prop('disabled', false).text('➕');
                        alert(response.data.message || 'Error');
                    }
                });
            });
            
            // Analitzar immediatament
            $(document).on('click', '.arxiu-analyze-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                var container = btn.closest('.arxiu-attachment-analysis');
                
                btn.prop('disabled', true).text('Analitzant...');
                
                $.post(ajaxurl, {
                    action: 'arxiu_media_analyze',
                    nonce: arxiu_admin.nonce,
                    attachment_id: id
                }, function(response) {
                    if (response.success) {
                        var analysis = response.data.analysis;
                        var html = '<p><strong>🤖 Analitzat:</strong> Ara mateix</p>';
                        html += '<p><strong>Gènere:</strong> ' + (analysis.genre || 'Desconegut') + '</p>';
                        if (analysis.tags_val && analysis.tags_val.length) {
                            html += '<p><strong>Tags:</strong> ' + analysis.tags_val.slice(0,5).join(', ') + '</p>';
                        }
                        if (analysis.summary) {
                            html += '<p><strong>Resum:</strong> ' + analysis.summary.substring(0, 100) + '...</p>';
                        }
                        html += '<button type=\"button\" class=\"button arxiu-reanalyze-btn\" data-id=\"' + id + '\">🔄 Re-analitzar</button>';
                        container.html(html);
                    } else {
                        btn.prop('disabled', false).text('🤖 Analitzar ara');
                        alert(response.data.message || 'Error');
                    }
                });
            });
            
            // Re-analitzar
            $(document).on('click', '.arxiu-reanalyze-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                btn.data('original-text', btn.text());
                btn.addClass('arxiu-analyze-btn').trigger('click');
            });
            
        })(jQuery);
        ";
    }
    
    /**
     * Obtenir imatges no analitzades
     */
    public static function get_unanalyzed_images( $limit = 100 ) {
        global $wpdb;
        
        return $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID 
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_arxiu_analyzed_at'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'
             AND pm.meta_value IS NULL
             ORDER BY p.post_date DESC
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Comptar imatges no analitzades
     */
    public static function count_unanalyzed_images() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(p.ID) 
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_arxiu_analyzed_at'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'
             AND pm.meta_value IS NULL"
        );
    }
    
    /**
     * Comptar imatges analitzades
     */
    public static function count_analyzed_images() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM {$wpdb->postmeta}
             WHERE meta_key = '_arxiu_analyzed_at'"
        );
    }
}

// Inicialitzar
add_action( 'plugins_loaded', function() {
    Arxiu_Media_Integration::get_instance();
}, 20 );
