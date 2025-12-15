<?php
/**
 * Arxiu Mapbox Sync - Integració amb Mapbox per ubicacions
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Mapbox_Sync {
    
    /**
     * Singleton
     */
    private static $instance = null;
    
    /**
     * Opcions per defecte
     */
    private $default_options = [
        'token'           => '',
        'style'           => 'mapbox://styles/mapbox/light-v11',
        'center_lng'      => -0.8389,
        'center_lat'      => 38.4372,
        'tileset_barri'   => 'javipolo.9kix7kmf',
        'layer_barri'     => 'barris_to_mapbox_181125-82e6ta',
        'tileset_poi'     => 'javipolo.09zhvvyp',
        'layer_poi'       => 'pois_to_mapbox_181125-0njp49',
        'cache_duration'  => 3600,
        'tilequery_radius'=> 5000,
        'tilequery_limit' => 50,
    ];
    
    private $options;
    
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
        $this->options = $this->get_options();
        $this->init_hooks();
    }
    
    /**
     * Obtenir opcions
     */
    public function get_options() {
        $saved = get_option( 'arxiu_mapbox_options', [] );
        return wp_parse_args( $saved, $this->default_options );
    }
    
    /**
     * Inicialitzar hooks
     */
    private function init_hooks() {
        // Pàgines d'admin
        add_action( 'admin_menu', [ $this, 'add_settings_pages' ], 20 );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        
        // AJAX
        add_action( 'wp_ajax_arxiu_validate_mapbox_token', [ $this, 'ajax_validate_token' ] );
        add_action( 'wp_ajax_arxiu_sync_mapbox', [ $this, 'ajax_sync_mapbox' ] );
        
        // Cron per neteja de cache
        add_action( 'arxiu_cleanup_mapbox_cache', [ $this, 'cleanup_old_cache' ] );
        if ( ! wp_next_scheduled( 'arxiu_cleanup_mapbox_cache' ) ) {
            wp_schedule_event( time(), 'daily', 'arxiu_cleanup_mapbox_cache' );
        }
    }
    
    /**
     * Afegir pàgines de configuració
     */
    public function add_settings_pages() {
        add_submenu_page(
            'edit.php?post_type=' . ARXIU_POST_TYPE,
            __( 'Configuració Mapbox', 'arxiu-monovar' ),
            '🗺️ ' . __( 'Mapbox', 'arxiu-monovar' ),
            'manage_options',
            'arxiu-mapbox-settings',
            [ $this, 'render_settings_page' ]
        );
        
        add_submenu_page(
            'edit.php?post_type=' . ARXIU_POST_TYPE,
            __( 'Sincronitzar Ubicacions', 'arxiu-monovar' ),
            '🔄 ' . __( 'Sincronitzar', 'arxiu-monovar' ),
            'manage_options',
            'arxiu-mapbox-sync',
            [ $this, 'render_sync_page' ]
        );
    }
    
    /**
     * Registrar settings
     */
    public function register_settings() {
        register_setting( 'arxiu_mapbox_options', 'arxiu_mapbox_options', [
            'sanitize_callback' => [ $this, 'sanitize_options' ]
        ]);
    }
    
    /**
     * Sanititzar opcions
     */
    public function sanitize_options( $input ) {
        $sanitized = [];
        
        $sanitized['token'] = isset( $input['token'] ) 
            ? sanitize_text_field( trim( $input['token'] ) ) : '';
        
        $sanitized['style'] = isset( $input['style'] )
            ? esc_url_raw( $input['style'] ) : $this->default_options['style'];
        
        $sanitized['center_lng'] = isset( $input['center_lng'] )
            ? floatval( $input['center_lng'] ) : $this->default_options['center_lng'];
        
        $sanitized['center_lat'] = isset( $input['center_lat'] )
            ? floatval( $input['center_lat'] ) : $this->default_options['center_lat'];
        
        $sanitized['tileset_barri'] = isset( $input['tileset_barri'] )
            ? sanitize_text_field( $input['tileset_barri'] ) : $this->default_options['tileset_barri'];
        
        $sanitized['layer_barri'] = isset( $input['layer_barri'] )
            ? sanitize_text_field( $input['layer_barri'] ) : $this->default_options['layer_barri'];
        
        $sanitized['tileset_poi'] = isset( $input['tileset_poi'] )
            ? sanitize_text_field( $input['tileset_poi'] ) : $this->default_options['tileset_poi'];
        
        $sanitized['layer_poi'] = isset( $input['layer_poi'] )
            ? sanitize_text_field( $input['layer_poi'] ) : $this->default_options['layer_poi'];
        
        $sanitized['cache_duration'] = isset( $input['cache_duration'] )
            ? absint( $input['cache_duration'] ) : $this->default_options['cache_duration'];
        
        $sanitized['tilequery_radius'] = isset( $input['tilequery_radius'] )
            ? absint( $input['tilequery_radius'] ) : $this->default_options['tilequery_radius'];
        
        $sanitized['tilequery_limit'] = isset( $input['tilequery_limit'] )
            ? min( 50, absint( $input['tilequery_limit'] ) ) : $this->default_options['tilequery_limit'];
        
        return $sanitized;
    }
    
    /**
     * AJAX: Validar token
     */
    public function ajax_validate_token() {
        check_ajax_referer( 'arxiu_mapbox_nonce', 'nonce' );
        
        $token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
        
        if ( empty( $token ) ) {
            wp_send_json_error( 'Token buit' );
        }
        
        $url = "https://api.mapbox.com/tokens/v2?access_token={$token}";
        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        
        if ( $code === 200 ) {
            wp_send_json_success( 'Token vàlid' );
        } else {
            wp_send_json_error( 'Token invàlid (HTTP ' . $code . ')' );
        }
    }
    
    /**
     * AJAX: Sincronitzar taxonomia
     */
    public function ajax_sync_mapbox() {
        check_ajax_referer( 'arxiu_mapbox_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'No tens permisos' );
        }
        
        $clear_cache = isset( $_POST['clear_cache'] ) && $_POST['clear_cache'] === 'true';
        
        if ( $clear_cache ) {
            $this->clear_features_cache();
        }
        
        $result = $this->sync_taxonomy_from_mapbox();
        
        if ( isset( $result['error'] ) ) {
            wp_send_json_error( $result['error'] );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * Sincronitzar taxonomia des de Mapbox
     */
    private function sync_taxonomy_from_mapbox() {
        if ( ! taxonomy_exists( 'ubicacio' ) ) {
            return [ 'error' => 'La taxonomia "ubicacio" no existeix' ];
        }
        
        $created = 0;
        $skipped = 0;
        
        // Obtenir barris
        $barrios = $this->fetch_features_from_tileset(
            $this->options['tileset_barri'],
            $this->options['layer_barri'],
            'barri_slug'
        );
        
        foreach ( $barrios as $feature ) {
            if ( empty( $feature['slug'] ) ) continue;
            
            $slug = sanitize_title( $feature['slug'] );
            $name = ! empty( $feature['name'] ) ? $feature['name'] : $this->slug_to_name( $slug );
            
            if ( ! term_exists( $slug, 'ubicacio' ) ) {
                $result = wp_insert_term( $name, 'ubicacio', [ 'slug' => $slug ] );
                if ( ! is_wp_error( $result ) ) $created++;
            } else {
                $skipped++;
            }
        }
        
        // Obtenir POIs
        $pois = $this->fetch_features_from_tileset(
            $this->options['tileset_poi'],
            $this->options['layer_poi'],
            'poi_slug'
        );
        
        foreach ( $pois as $feature ) {
            if ( empty( $feature['slug'] ) ) continue;
            
            $slug = sanitize_title( $feature['slug'] );
            $name = ! empty( $feature['name'] ) ? $feature['name'] : $this->slug_to_name( $slug );
            
            if ( ! term_exists( $slug, 'ubicacio' ) ) {
                $result = wp_insert_term( $name, 'ubicacio', [ 'slug' => $slug ] );
                if ( ! is_wp_error( $result ) ) $created++;
            } else {
                $skipped++;
            }
        }
        
        return [
            'created'       => $created,
            'skipped'       => $skipped,
            'total_barrios' => count( $barrios ),
            'total_pois'    => count( $pois ),
        ];
    }
    
    /**
     * Obtenir features des de tileset amb cache
     */
    private function fetch_features_from_tileset( $tileset_id, $layer_name, $slug_property ) {
        $cache_key = 'arxiu_features_' . md5( $tileset_id . $layer_name );
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached && is_array( $cached ) ) {
            return $cached;
        }
        
        $features = $this->fetch_features_from_tilequery( $tileset_id, $layer_name, $slug_property );
        
        if ( ! empty( $features ) ) {
            set_transient( $cache_key, $features, $this->options['cache_duration'] );
        }
        
        return $features;
    }
    
    /**
     * Obtenir features des de Tilequery API
     */
    private function fetch_features_from_tilequery( $tileset_id, $layer_name, $slug_property ) {
        $center_lng = $this->options['center_lng'];
        $center_lat = $this->options['center_lat'];
        $radius = $this->options['tilequery_radius'];
        $limit = $this->options['tilequery_limit'];
        
        // Punts de consulta (quadrícula 3x3)
        $query_points = [
            [ 'lng' => $center_lng, 'lat' => $center_lat ],
            [ 'lng' => $center_lng - 0.05, 'lat' => $center_lat ],
            [ 'lng' => $center_lng + 0.05, 'lat' => $center_lat ],
            [ 'lng' => $center_lng, 'lat' => $center_lat - 0.05 ],
            [ 'lng' => $center_lng, 'lat' => $center_lat + 0.05 ],
        ];
        
        $all_features = [];
        $seen_slugs = [];
        
        foreach ( $query_points as $point ) {
            $url = sprintf(
                'https://api.mapbox.com/v4/%s/tilequery/%f,%f.json?radius=%d&limit=%d&access_token=%s',
                $tileset_id,
                $point['lng'],
                $point['lat'],
                $radius,
                $limit,
                $this->options['token']
            );
            
            $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
            
            if ( is_wp_error( $response ) ) continue;
            if ( wp_remote_retrieve_response_code( $response ) !== 200 ) continue;
            
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( ! isset( $data['features'] ) ) continue;
            
            foreach ( $data['features'] as $feature ) {
                if ( ! isset( $feature['properties'][ $slug_property ] ) ) continue;
                
                $slug = $feature['properties'][ $slug_property ];
                
                if ( in_array( $slug, $seen_slugs, true ) ) continue;
                
                $seen_slugs[] = $slug;
                $all_features[] = [
                    'slug' => $slug,
                    'name' => $feature['properties']['name'] ?? null,
                ];
            }
        }
        
        return $all_features;
    }
    
    /**
     * Convertir slug a nom llegible
     */
    private function slug_to_name( $slug ) {
        return ucwords( str_replace( [ '-', '_' ], ' ', $slug ) );
    }
    
    /**
     * Netejar cache de features
     */
    private function clear_features_cache() {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_arxiu_features_%' 
             OR option_name LIKE '_transient_timeout_arxiu_features_%'"
        );
    }
    
    /**
     * Neteja automàtica de cache antic
     */
    public function cleanup_old_cache() {
        $this->clear_features_cache();
    }
    
    /**
     * Renderitzar pàgina de configuració
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tens permisos' );
        }
        
        include ARXIU_PATH . 'templates/settings-mapbox.php';
    }
    
    /**
     * Renderitzar pàgina de sincronització
     */
    public function render_sync_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tens permisos' );
        }
        
        include ARXIU_PATH . 'templates/sync-mapbox.php';
    }
    
    /**
     * Verificar si el token està configurat
     */
    public function has_token() {
        return ! empty( $this->options['token'] );
    }
    
    /**
     * Obtenir token
     */
    public function get_token() {
        return $this->options['token'];
    }
}
