<?php
/**
 * Plugin Name: Arxiu Monòver - Gestió Completa
 * Description: Plugin unificat per a la gestió completa del CPT Arxiu amb IA (Gemini Vision), dates, ubicacions (Mapbox), gèneres, autor i col·lecció.
 * Version: 17.3.0
 * Author: Arxiu Monòver
 * Text Domain: arxiu-monovar
 * Requires PHP: 7.4
 * Requires at least: 6.0
 * 
 * CHANGELOG v17.3.0:
 * - FIX: Corregit error de rate limit en classificació de gènere
 * - FIX: Actualitzats noms de models Gemini (gemini-2.5-flash-lite NO existeix!)
 * - FIX: Eliminada doble crida a l'API quan es selecciona una imatge
 * - MILLORA: Ara la classificació de gènere ve inclosa en l'anàlisi completa
 * - MILLORA: Models ordenats per disponibilitat GA (2.0-flash-lite, 2.0-flash, 2.5-flash)
 * 
 * CHANGELOG v17.2.0:
 * - Integració amb la Mediateca de WordPress
 * - Columna d'estat IA a la llista de fitxers
 * - Accions bulk per analitzar múltiples imatges
 * - Auto-queue d'imatges noves (opcional)
 * - Detalls d'anàlisi al modal d'attachment
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ═══════════════════════════════════════════════════════════════════════════════
// CONSTANTS
// ═══════════════════════════════════════════════════════════════════════════════

define( 'ARXIU_VERSION', '17.3.0' );
define( 'ARXIU_PATH', plugin_dir_path( __FILE__ ) );
define( 'ARXIU_URL', plugin_dir_url( __FILE__ ) );
define( 'ARXIU_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );
define( 'ARXIU_POST_TYPE', 'arxiu' );

// ═══════════════════════════════════════════════════════════════════════════════
// AUTOLOADER
// ═══════════════════════════════════════════════════════════════════════════════

spl_autoload_register( function( $class ) {
    $prefix = 'Arxiu_';
    if ( strpos( $class, $prefix ) !== 0 ) return;
    
    $class_map = [
        'Arxiu_Knowledge_Base'      => 'class-knowledge-base.php',
        'Arxiu_Date_Detector'       => 'class-date-detector.php',
        'Arxiu_Credentials'         => 'class-credentials.php',
        'Arxiu_Gemini_API'          => 'class-gemini-api.php',
        'Arxiu_Genre_Classifier'    => 'class-genre-classifier.php',
        'Arxiu_Mapbox_Sync'         => 'class-mapbox-sync.php',
        'Arxiu_Queue_Manager'       => 'class-queue-manager.php',
        'Arxiu_Media_Integration'   => 'class-media-integration.php',
    ];
    
    if ( isset( $class_map[ $class ] ) ) {
        $file = ARXIU_PATH . 'includes/' . $class_map[ $class ];
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
});

// ═══════════════════════════════════════════════════════════════════════════════
// CLASSE PRINCIPAL
// ═══════════════════════════════════════════════════════════════════════════════

final class Arxiu_Monovar {
    
    /**
     * Singleton
     */
    private static $instance = null;
    
    /**
     * Meta keys
     */
    const META_IMAGE_ID     = '_arxiu_img_id';
    const META_TAGS         = '_arxiu_img_tags';
    const META_DESCRIPTION  = '_arxiu_img_description';
    const META_DATE_DIA     = '_data_dia';
    const META_DATE_MES     = '_data_mes';
    const META_DATE_SOURCE  = '_arxiu_date_source';
    const META_LAT          = '_lat';
    const META_LNG          = '_lng';
    const META_AREA         = '_area';
    
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
        // Hooks d'inicialització
        add_action( 'init', [ $this, 'register_post_type_and_taxonomies' ], 5 );
        add_action( 'add_meta_boxes', [ $this, 'register_all_metaboxes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'save_post_' . ARXIU_POST_TYPE, [ $this, 'save_all_meta' ], 10, 2 );
        
        // AJAX endpoints
        add_action( 'wp_ajax_arxiu_analyze_image', [ $this, 'ajax_analyze_image' ] );
        add_action( 'wp_ajax_arxiu_analyze_title', [ $this, 'ajax_analyze_title' ] );
        add_action( 'wp_ajax_arxiu_classify_genre', [ $this, 'ajax_classify_genre' ] );
        add_action( 'wp_ajax_arxiu_save_settings', [ $this, 'ajax_save_settings' ] );
        add_action( 'wp_ajax_arxiu_test_gemini', [ $this, 'ajax_test_gemini' ] );
        add_action( 'wp_ajax_arxiu_get_ubicacions', [ $this, 'ajax_get_ubicacions' ] );
        add_action( 'wp_ajax_arxiu_get_quota', [ $this, 'ajax_get_quota' ] );
        add_action( 'wp_ajax_arxiu_get_attachment_analysis', [ $this, 'ajax_get_attachment_analysis' ] );
        add_action( 'wp_ajax_arxiu_save_auto_queue', [ $this, 'ajax_save_auto_queue' ] );
        
        // AJAX endpoints per la cua
        add_action( 'wp_ajax_arxiu_queue_add_unprocessed', [ $this, 'ajax_queue_add_unprocessed' ] );
        add_action( 'wp_ajax_arxiu_queue_process_now', [ $this, 'ajax_queue_process_now' ] );
        add_action( 'wp_ajax_arxiu_queue_clear_completed', [ $this, 'ajax_queue_clear_completed' ] );
        add_action( 'wp_ajax_arxiu_queue_retry_failed', [ $this, 'ajax_queue_retry_failed' ] );
        add_action( 'wp_ajax_arxiu_queue_unpause', [ $this, 'ajax_queue_unpause' ] );
        add_action( 'wp_ajax_arxiu_queue_stats', [ $this, 'ajax_queue_stats' ] );
        
        // Handler per accions POST de la cua
        add_action( 'admin_init', [ $this, 'handle_queue_actions' ] );
        
        // Menú de configuració
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        
        // Inicialitzar mòduls
        add_action( 'plugins_loaded', [ $this, 'init_modules' ] );
    }
    
    /**
     * Handler per accions POST de la cua
     */
    public function handle_queue_actions() {
        if ( ! isset( $_POST['arxiu_action'] ) || ! isset( $_POST['arxiu_queue_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['arxiu_queue_nonce'], 'arxiu_queue_action' ) ) {
            return;
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        $action = sanitize_text_field( $_POST['arxiu_action'] );
        $redirect_url = admin_url( 'edit.php?post_type=' . ARXIU_POST_TYPE . '&page=arxiu-queue' );
        $message = '';
        
        switch ( $action ) {
            case 'bulk_add':
                $count = isset( $_POST['bulk_count'] ) ? intval( $_POST['bulk_count'] ) : 10;
                $result = $this->queue_add_unprocessed_images( $count );
                $message = sprintf( 'Afegides %d imatges a la cua', $result['added'] );
                break;
                
            case 'process_now':
                $queue->process_batch();
                $message = 'Batch processat';
                break;
                
            case 'retry_failed':
                $retried = $queue->retry_failed_items();
                $message = sprintf( '%d items reiniciats', $retried );
                break;
                
            case 'clear':
                $queue->clear_queue();
                $message = 'Cua buidada';
                break;
                
            case 'cleanup':
                $deleted = $queue->cleanup_old_items();
                $message = sprintf( '%d items antics eliminats', $deleted );
                break;
        }
        
        if ( $message ) {
            $redirect_url = add_query_arg( 'arxiu_message', urlencode( $message ), $redirect_url );
        }
        
        wp_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Afegir imatges no processades a la cua
     */
    private function queue_add_unprocessed_images( $limit = 100 ) {
        global $wpdb;
        
        // Obtenir imatges no analitzades
        $attachment_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID 
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_arxiu_analyzed_at'
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%%'
             AND pm.meta_value IS NULL
             ORDER BY p.post_date DESC
             LIMIT %d",
            min( 500, $limit )
        ));
        
        if ( empty( $attachment_ids ) ) {
            return [ 'added' => 0, 'skipped' => 0 ];
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        return $queue->add_bulk_to_queue( $attachment_ids );
    }
    
    /**
     * Inicialitzar mòduls
     */
    public function init_modules() {
        // Mapbox si està configurat
        if ( class_exists( 'Arxiu_Mapbox_Sync' ) ) {
            Arxiu_Mapbox_Sync::get_instance();
        }
    }
    
    /**
     * Registrar CPT i taxonomies
     */
    public function register_post_type_and_taxonomies() {
        // El CPT hauria d'estar registrat al tema, però per si de cas...
        if ( ! post_type_exists( ARXIU_POST_TYPE ) ) {
            $this->register_arxiu_post_type();
        }
        
        // Assegurar que les taxonomies existeixen
        $this->ensure_taxonomies();
    }
    
    /**
     * Registrar CPT Arxiu
     */
    private function register_arxiu_post_type() {
        register_post_type( ARXIU_POST_TYPE, [
            'labels' => [
                'name'               => __( 'Arxius', 'arxiu-monovar' ),
                'singular_name'      => __( 'Arxiu', 'arxiu-monovar' ),
                'add_new'            => __( 'Afegir nou', 'arxiu-monovar' ),
                'add_new_item'       => __( 'Afegir nou Arxiu', 'arxiu-monovar' ),
                'edit_item'          => __( 'Editar Arxiu', 'arxiu-monovar' ),
                'view_item'          => __( 'Veure Arxiu', 'arxiu-monovar' ),
                'all_items'          => __( 'Tots els Arxius', 'arxiu-monovar' ),
                'search_items'       => __( 'Cercar Arxius', 'arxiu-monovar' ),
            ],
            'public'              => true,
            'has_archive'         => true,
            'rewrite'             => [ 'slug' => 'arxiu' ],
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ],
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-archive',
        ]);
    }
    
    /**
     * Assegurar taxonomies
     */
    private function ensure_taxonomies() {
        $taxonomies = [
            'generes' => [
                'label' => __( 'Gèneres', 'arxiu-monovar' ),
                'hierarchical' => true,
            ],
            'ubicacio' => [
                'label' => __( 'Ubicació', 'arxiu-monovar' ),
                'hierarchical' => true,
            ],
            'data' => [
                'label' => __( 'Data', 'arxiu-monovar' ),
                'hierarchical' => false,
            ],
            'autor' => [
                'label' => __( 'Autor', 'arxiu-monovar' ),
                'hierarchical' => false,
            ],
            'colleccio' => [
                'label' => __( 'Col·lecció', 'arxiu-monovar' ),
                'hierarchical' => true,
            ],
        ];
        
        foreach ( $taxonomies as $slug => $args ) {
            if ( ! taxonomy_exists( $slug ) ) {
                register_taxonomy( $slug, ARXIU_POST_TYPE, [
                    'label'        => $args['label'],
                    'hierarchical' => $args['hierarchical'],
                    'show_in_rest' => true,
                    'rewrite'      => [ 'slug' => $slug ],
                ]);
            }
        }
    }
    
    /**
     * Registrar totes les metaboxes
     */
    public function register_all_metaboxes() {
        // 1. Imatge + Etiquetado IA
        add_meta_box(
            'arxiu_imagen_ia',
            '🖼️ Imatge i Anàlisi IA',
            [ $this, 'render_imagen_metabox' ],
            ARXIU_POST_TYPE,
            'normal',
            'high'
        );
        
        // 2. Gèneres amb IA
        add_meta_box(
            'arxiu_generes_ia',
            '🎨 Gèneres (Classificació IA)',
            [ $this, 'render_generes_metabox' ],
            ARXIU_POST_TYPE,
            'normal',
            'high'
        );
        
        // 3. Data (slider)
        add_meta_box(
            'arxiu_data_slider',
            '📅 Data (Detecció Intel·ligent)',
            [ $this, 'render_data_metabox' ],
            ARXIU_POST_TYPE,
            'normal',
            'high'
        );
        
        // 4. Ubicació (Mapbox)
        add_meta_box(
            'arxiu_ubicacio_map',
            '🗺️ Ubicació (Mapa)',
            [ $this, 'render_ubicacio_metabox' ],
            ARXIU_POST_TYPE,
            'normal',
            'high'
        );
        
        // 5. Autor i Col·lecció
        add_meta_box(
            'arxiu_autor_colleccio',
            '👤 Autor i 📚 Col·lecció',
            [ $this, 'render_autor_colleccio_metabox' ],
            ARXIU_POST_TYPE,
            'normal',
            'default'
        );
        
        // Eliminar metaboxes per defecte de taxonomies (les gestionem nosaltres)
        remove_meta_box( 'tagsdiv-data', ARXIU_POST_TYPE, 'side' );
        remove_meta_box( 'tagsdiv-autor', ARXIU_POST_TYPE, 'side' );
        remove_meta_box( 'generesdiv', ARXIU_POST_TYPE, 'side' );
        remove_meta_box( 'collecciodiv', ARXIU_POST_TYPE, 'side' );
        remove_meta_box( 'ubicaciodiv', ARXIU_POST_TYPE, 'side' );
    }
    
    /**
     * Enqueue assets admin
     */
    public function enqueue_admin_assets( $hook ) {
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) return;
        
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== ARXIU_POST_TYPE ) return;
        
        // WordPress media
        wp_enqueue_media();
        wp_enqueue_script( 'jquery-ui-draggable' );
        
        // noUiSlider
        wp_enqueue_style( 'nouislider-css', 'https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.css', [], '15.7.1' );
        wp_enqueue_script( 'nouislider-js', 'https://cdn.jsdelivr.net/npm/nouislider@15.7.1/dist/nouislider.min.js', [], '15.7.1', true );
        
        // Mapbox
        $mapbox_options = get_option( 'arxiu_mapbox_options', [] );
        if ( ! empty( $mapbox_options['token'] ) ) {
            wp_enqueue_style( 'mapbox-gl-css', 'https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.css', [], '3.8.0' );
            wp_enqueue_script( 'mapbox-gl-js', 'https://api.mapbox.com/mapbox-gl-js/v3.8.0/mapbox-gl.js', [], '3.8.0', true );
            wp_enqueue_script( 'turf-js', 'https://cdn.jsdelivr.net/npm/@turf/turf@6.5.0/turf.min.js', [ 'mapbox-gl-js' ], '6.5.0', true );
            
            // Mapbox Ubicació (mòdul optimitzat)
            wp_enqueue_style( 'arxiu-mapbox-css', ARXIU_URL . 'assets/css/mapbox-ubicacio.css', [ 'mapbox-gl-css' ], ARXIU_VERSION );
            wp_enqueue_script( 'arxiu-mapbox-js', ARXIU_URL . 'assets/js/mapbox-ubicacio.js', [ 'jquery', 'mapbox-gl-js', 'turf-js' ], ARXIU_VERSION, true );
        }
        
        // Plugin CSS i JS
        wp_enqueue_style( 'arxiu-admin-css', ARXIU_URL . 'assets/css/admin.css', [], ARXIU_VERSION );
        wp_enqueue_script( 'arxiu-admin-js', ARXIU_URL . 'assets/js/admin.js', [ 'jquery', 'jquery-ui-draggable', 'nouislider-js' ], ARXIU_VERSION, true );
        
        // Configuració per JavaScript
        global $post;
        
        $existing_image_id = get_post_meta( $post->ID, self::META_IMAGE_ID, true );
        $existing_filename = '';
        if ( $existing_image_id ) {
            $file_path = get_attached_file( $existing_image_id );
            if ( $file_path ) {
                $existing_filename = pathinfo( $file_path, PATHINFO_FILENAME );
                $existing_filename = str_replace( [ '-', '_', '.' ], ' ', $existing_filename );
            }
        }
        
        // Mapbox config
        $selected_ubicacio = wp_get_post_terms( $post->ID, 'ubicacio', [ 'fields' => 'slugs' ] );
        if ( is_wp_error( $selected_ubicacio ) ) $selected_ubicacio = [];
        
        // Configuració per al JS de Mapbox (script separat)
        wp_localize_script( 'arxiu-mapbox-js', 'ArxiuMapboxData', [
            'token'    => $mapbox_options['token'] ?? '',
            'style'    => $mapbox_options['style'] ?? 'mapbox://styles/mapbox/light-v11',
            'center'   => [
                (float) ( $mapbox_options['center_lng'] ?? -0.8389 ),
                (float) ( $mapbox_options['center_lat'] ?? 38.4372 ),
            ],
            'zoom'     => (float) ( $mapbox_options['zoom'] ?? 13.5 ),
            'sources'  => [
                'barris' => 'mapbox://' . ( $mapbox_options['tileset_barri'] ?? 'javipolo.9kix7kmf' ),
                'pois'   => 'mapbox://' . ( $mapbox_options['tileset_poi'] ?? 'javipolo.09zhvvyp' ),
            ],
            'layers'   => [
                'barri' => $mapbox_options['layer_barri'] ?? 'barris_to_mapbox_181125-82e6ta',
                'poi'   => $mapbox_options['layer_poi'] ?? 'pois_to_mapbox_181125-0njp49',
            ],
            'selected' => $selected_ubicacio,
        ]);
        
        // Configuració per al JS d'admin
        wp_localize_script( 'arxiu-admin-js', 'ArxiuConfig', [
            'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
            'nonce'             => wp_create_nonce( 'arxiu_nonce' ),
            'postId'            => $post->ID,
            'existingFilename'  => $existing_filename,
            'hasGeminiKey'      => Arxiu_Credentials::has_api_key(),
            'tensorflowUrl'     => 'https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js',
            'cocoSsdUrl'        => 'https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3/dist/coco-ssd.min.js',
            'minConfidence'     => 0.5,
            'duplicateThreshold'=> 5,
            
            'i18n' => $this->get_i18n_strings(),
        ]);
    }
    
    /**
     * Strings d'internacionalització
     */
    private function get_i18n_strings() {
        return [
            'loading'             => __( 'Carregant...', 'arxiu-monovar' ),
            'ready'               => __( '✓ Preparat', 'arxiu-monovar' ),
            'scanning'            => __( '🔍 Escanejant...', 'arxiu-monovar' ),
            'found'               => __( 'detectades', 'arxiu-monovar' ),
            'noDetections'        => __( 'Cap detecció', 'arxiu-monovar' ),
            'error'               => __( 'Error', 'arxiu-monovar' ),
            'confirm'             => __( 'Segur?', 'arxiu-monovar' ),
            'confirmRemoveImage'  => __( 'Eliminar la imatge?', 'arxiu-monovar' ),
            'analyzing'           => __( '🔍 Analitzant...', 'arxiu-monovar' ),
            'analyzed'            => __( '✓ Anàlisi completada', 'arxiu-monovar' ),
            'needsApiKey'         => __( 'Cal configurar l\'API Key', 'arxiu-monovar' ),
            'classifying'         => __( '🤖 Classificant...', 'arxiu-monovar' ),
            'classified'          => __( '✓ Classificat', 'arxiu-monovar' ),
            'selectImageFirst'    => __( 'Selecciona una imatge primer', 'arxiu-monovar' ),
        ];
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // RENDER METABOXES
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Metabox: Imatge + IA
     */
    public function render_imagen_metabox( $post ) {
        wp_nonce_field( 'arxiu_save_meta', 'arxiu_meta_nonce' );
        include ARXIU_PATH . 'templates/metabox-imagen.php';
    }
    
    /**
     * Metabox: Gèneres
     */
    public function render_generes_metabox( $post ) {
        include ARXIU_PATH . 'templates/metabox-generes.php';
    }
    
    /**
     * Metabox: Data
     */
    public function render_data_metabox( $post ) {
        include ARXIU_PATH . 'templates/metabox-data.php';
    }
    
    /**
     * Metabox: Ubicació
     */
    public function render_ubicacio_metabox( $post ) {
        include ARXIU_PATH . 'templates/metabox-ubicacio.php';
    }
    
    /**
     * Metabox: Autor i Col·lecció
     */
    public function render_autor_colleccio_metabox( $post ) {
        include ARXIU_PATH . 'templates/metabox-autor-colleccio.php';
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // SAVE META
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * Guardar totes les dades
     */
    public function save_all_meta( $post_id, $post ) {
        // Verificacions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( ! isset( $_POST['arxiu_meta_nonce'] ) || 
             ! wp_verify_nonce( $_POST['arxiu_meta_nonce'], 'arxiu_save_meta' ) ) return;
        
        // 1. IMATGE
        if ( isset( $_POST['arxiu_image_id'] ) ) {
            $image_id = absint( $_POST['arxiu_image_id'] );
            if ( $image_id > 0 ) {
                update_post_meta( $post_id, self::META_IMAGE_ID, $image_id );
            } else {
                delete_post_meta( $post_id, self::META_IMAGE_ID );
            }
        }
        
        // Tags de persones
        if ( isset( $_POST['arxiu_tags_data'] ) ) {
            $tags = json_decode( wp_unslash( $_POST['arxiu_tags_data'] ), true );
            if ( is_array( $tags ) ) {
                update_post_meta( $post_id, self::META_TAGS, wp_json_encode( $tags ) );
            }
        }
        
        // Descripció IA
        if ( isset( $_POST['arxiu_description_data'] ) ) {
            update_post_meta( $post_id, self::META_DESCRIPTION, wp_unslash( $_POST['arxiu_description_data'] ) );
        }
        
        // 2. GÈNERES
        if ( isset( $_POST['generes_btns'] ) && is_array( $_POST['generes_btns'] ) ) {
            wp_set_post_terms( $post_id, array_map( 'intval', $_POST['generes_btns'] ), 'generes' );
        } else {
            wp_set_post_terms( $post_id, [], 'generes' );
        }
        
        // 3. DATA
        $mode = isset( $_POST['data_mode'] ) ? sanitize_text_field( $_POST['data_mode'] ) : 'rango';
        
        if ( $mode === 'exact' && isset( $_POST['year_exact'] ) ) {
            $year = (string) intval( $_POST['year_exact'] );
            if ( $year ) wp_set_post_terms( $post_id, [ $year ], 'data' );
        } elseif ( isset( $_POST['year_min'], $_POST['year_max'] ) ) {
            $min = intval( $_POST['year_min'] );
            $max = intval( $_POST['year_max'] );
            if ( $min && $max && $min <= $max ) {
                $years = [];
                for ( $y = $min; $y <= $max; $y++ ) $years[] = (string) $y;
                wp_set_post_terms( $post_id, $years, 'data' );
            }
        }
        
        // Dia i mes
        foreach ( [ 'data_dia' => self::META_DATE_DIA, 'data_mes' => self::META_DATE_MES ] as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                $val = intval( $_POST[ $field ] );
                if ( $val > 0 ) {
                    update_post_meta( $post_id, $meta_key, $val );
                } else {
                    delete_post_meta( $post_id, $meta_key );
                }
            }
        }
        
        if ( isset( $_POST['arxiu_date_source'] ) ) {
            update_post_meta( $post_id, self::META_DATE_SOURCE, sanitize_text_field( $_POST['arxiu_date_source'] ) );
        }
        
        // 4. UBICACIÓ
        if ( isset( $_POST['arxiu_lat'] ) ) {
            update_post_meta( $post_id, self::META_LAT, sanitize_text_field( $_POST['arxiu_lat'] ) );
        }
        if ( isset( $_POST['arxiu_lng'] ) ) {
            update_post_meta( $post_id, self::META_LNG, sanitize_text_field( $_POST['arxiu_lng'] ) );
        }
        if ( isset( $_POST['arxiu_area'] ) ) {
            update_post_meta( $post_id, self::META_AREA, sanitize_text_field( $_POST['arxiu_area'] ) );
        }
        
        // 5. AUTOR
        if ( isset( $_POST['arxiu_autor_select'] ) ) {
            $autor_id = intval( $_POST['arxiu_autor_select'] );
            wp_set_post_terms( $post_id, $autor_id > 0 ? [ $autor_id ] : [], 'autor' );
        }
        
        // 6. COL·LECCIÓ
        if ( isset( $_POST['arxiu_colleccio_select'] ) ) {
            $colleccio_id = intval( $_POST['arxiu_colleccio_select'] );
            wp_set_post_terms( $post_id, $colleccio_id > 0 ? [ $colleccio_id ] : [], 'colleccio' );
        }
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // AJAX HANDLERS
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * AJAX: Analitzar imatge amb Gemini (UNIFICAT v17.0)
     * Retorna: gènere + tags + data + ubicació en una sola resposta
     */
    public function ajax_analyze_image() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $image_id = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;
        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        
        if ( ! $image_id ) {
            wp_send_json_error( [ 'message' => 'ID d\'imatge invàlid' ] );
        }
        
        $api_key = Arxiu_Credentials::get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API key no configurada', 'needs_setup' => true ] );
        }
        
        // Cache
        $force_refresh = isset( $_POST['force_refresh'] ) && $_POST['force_refresh'] === 'true';
        $cache_key = 'arxiu_gemini_complete_' . $image_id;
        
        if ( ! $force_refresh ) {
            $cached = get_transient( $cache_key );
            if ( $cached ) {
                $cached['_from_cache'] = true;
                wp_send_json_success( $cached );
                return;
            }
        }
        
        // Anàlisi unificada - 1 sola crida a l'API
        $result = Arxiu_Gemini_API::analyze_complete( $image_id, $title, $api_key );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ] );
        }
        
        // Afegir informació de quota
        $result['_quota'] = Arxiu_Gemini_API::get_quota_info();
        
        set_transient( $cache_key, $result, WEEK_IN_SECONDS );
        wp_send_json_success( $result );
    }
    
    /**
     * AJAX: Analitzar títol
     */
    public function ajax_analyze_title() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
        
        if ( empty( $title ) ) {
            wp_send_json_error( [ 'message' => 'Títol buit' ] );
        }
        
        $result = Arxiu_Date_Detector::analyze_title( $title );
        wp_send_json_success( $result );
    }
    
    /**
     * AJAX: Classificar gènere (optimitzat v17.0)
     * Primer mira si ja tenim l'anàlisi completa en cache
     */
    public function ajax_classify_genre() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $image_id = isset( $_POST['image_id'] ) ? absint( $_POST['image_id'] ) : 0;
        
        if ( ! $image_id ) {
            wp_send_json_error( 'ID d\'imatge invàlid' );
        }
        
        // Primer: mirar si tenim cache de l'anàlisi completa
        $cache_key = 'arxiu_gemini_complete_' . $image_id;
        $cached = get_transient( $cache_key );
        
        if ( $cached && ! empty( $cached['genre'] ) ) {
            // Usar gènere de l'anàlisi completa
            $genre_slug = sanitize_title( $cached['genre'] );
            $term = get_term_by( 'slug', $genre_slug, 'generes' );
            
            if ( $term ) {
                wp_send_json_success( [
                    'term_id'   => $term->term_id,
                    'term_name' => $term->name,
                    'term_slug' => $term->slug,
                    '_from_cache' => true,
                ] );
                return;
            }
        }
        
        // Si no hi ha cache, fer crida simple (ràpida)
        $result = Arxiu_Gemini_API::classify_genre( $image_id );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        
        wp_send_json_success( $result );
    }
    
    /**
     * AJAX: Desar configuració
     */
    public function ajax_save_settings() {
        check_ajax_referer( 'arxiu_settings_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( [ 'message' => 'No tens permisos' ] );
        }
        
        if ( isset( $_POST['api_key'] ) ) {
            $api_key = sanitize_text_field( $_POST['api_key'] );
            Arxiu_Credentials::save_api_key( $api_key );
        }
        
        if ( isset( $_POST['gemini_model'] ) ) {
            update_option( 'arxiu_gemini_model', sanitize_text_field( $_POST['gemini_model'] ) );
        }
        
        wp_send_json_success( [ 'message' => 'Configuració guardada' ] );
    }
    
    /**
     * AJAX: Test Gemini
     */
    public function ajax_test_gemini() {
        check_ajax_referer( 'arxiu_settings_nonce', 'nonce' );
        
        $api_key = Arxiu_Credentials::get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( [ 'message' => 'API key no configurada' ] );
        }
        
        $model = get_option( 'arxiu_gemini_model', 'gemini-2.5-flash' );
        $result = Arxiu_Gemini_API::test_connection( $api_key, $model );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        
        wp_send_json_success( [ 'model' => $model, 'status' => 'ok' ] );
    }
    
    /**
     * AJAX: Obtenir llista d'ubicacions disponibles
     */
    public function ajax_get_ubicacions() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        if ( ! class_exists( 'Arxiu_Knowledge_Base' ) ) {
            wp_send_json_error( [ 'message' => 'Knowledge Base no disponible' ] );
        }
        
        $ubicacions = Arxiu_Knowledge_Base::get_ubicacions_per_ia();
        wp_send_json_success( $ubicacions );
    }
    
    /**
     * AJAX: Obtenir informació de quota
     */
    public function ajax_get_quota() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $quota = Arxiu_Gemini_API::get_quota_info();
        wp_send_json_success( $quota );
    }
    
    /**
     * AJAX: Obtenir anàlisi pre-existent d'un attachment
     * Útil quan es selecciona una imatge ja processada en background
     */
    public function ajax_get_attachment_analysis() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $attachment_id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id ) {
            wp_send_json_error( [ 'message' => 'ID invàlid' ] );
        }
        
        $analysis = Arxiu_Queue_Manager::get_attachment_analysis( $attachment_id );
        
        if ( ! $analysis ) {
            wp_send_json_error( [ 
                'message' => 'No analitzat',
                'is_analyzed' => false,
            ] );
        }
        
        $analysis['_is_pre_analyzed'] = true;
        wp_send_json_success( $analysis );
    }
    
    /**
     * AJAX: Guardar opció auto-queue
     */
    public function ajax_save_auto_queue() {
        check_ajax_referer( 'arxiu_settings_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        $enabled = isset( $_POST['enabled'] ) && $_POST['enabled'] == '1';
        update_option( 'arxiu_auto_queue_uploads', $enabled );
        
        wp_send_json_success( [ 'enabled' => $enabled ] );
    }
    
    /**
     * Afegir menú admin
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=' . ARXIU_POST_TYPE,
            __( 'Configuració Arxiu', 'arxiu-monovar' ),
            '⚙️ ' . __( 'Configuració', 'arxiu-monovar' ),
            'manage_options',
            'arxiu-settings',
            [ $this, 'render_settings_page' ]
        );
        
        // Pàgina de la cua
        add_submenu_page(
            'edit.php?post_type=' . ARXIU_POST_TYPE,
            __( 'Cua de Processament', 'arxiu-monovar' ),
            '📦 ' . __( 'Cua IA', 'arxiu-monovar' ),
            'manage_options',
            'arxiu-queue',
            [ $this, 'render_queue_page' ]
        );
    }
    
    /**
     * Renderitzar pàgina configuració
     */
    public function render_settings_page() {
        include ARXIU_PATH . 'templates/settings-page.php';
    }
    
    /**
     * Renderitzar pàgina de la cua
     */
    public function render_queue_page() {
        include ARXIU_PATH . 'templates/queue-status.php';
    }
    
    // ═══════════════════════════════════════════════════════════════════════════
    // AJAX HANDLERS PER LA CUA
    // ═══════════════════════════════════════════════════════════════════════════
    
    /**
     * AJAX: Afegir imatges no processades a la cua
     */
    public function ajax_queue_add_unprocessed() {
        check_ajax_referer( 'arxiu_queue_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        global $wpdb;
        
        // Obtenir imatges sense anàlisi
        $attachments = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} p
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'
             AND NOT EXISTS (
                 SELECT 1 FROM {$wpdb->postmeta} pm 
                 WHERE pm.post_id = p.ID 
                 AND pm.meta_key = '_arxiu_analyzed_at'
             )
             ORDER BY p.ID DESC
             LIMIT 500"
        );
        
        if ( empty( $attachments ) ) {
            wp_send_json_success( [ 'added' => 0, 'message' => 'Totes les imatges ja han estat analitzades' ] );
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        $result = $queue->add_batch_to_queue( $attachments, 5 );
        
        wp_send_json_success( $result );
    }
    
    /**
     * AJAX: Processar ara un batch
     */
    public function ajax_queue_process_now() {
        check_ajax_referer( 'arxiu_queue_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        
        // Obtenir items pendents
        $items = $queue->get_next_batch( 5 );
        
        if ( empty( $items ) ) {
            wp_send_json_success( [ 'processed' => 0, 'failed' => 0, 'message' => 'No hi ha elements pendents' ] );
        }
        
        $processed = 0;
        $failed = 0;
        
        foreach ( $items as $item ) {
            $result = $queue->process_single( $item );
            if ( is_wp_error( $result ) ) {
                $failed++;
            } else {
                $processed++;
            }
            usleep( 500000 ); // 0.5s delay
        }
        
        wp_send_json_success( [ 'processed' => $processed, 'failed' => $failed ] );
    }
    
    /**
     * AJAX: Netejar elements completats
     */
    public function ajax_queue_clear_completed() {
        check_ajax_referer( 'arxiu_queue_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . Arxiu_Queue_Manager::TABLE_NAME;
        
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$table_name} WHERE status IN (%s, %s)",
            Arxiu_Queue_Manager::STATUS_COMPLETED,
            Arxiu_Queue_Manager::STATUS_SKIPPED
        ) );
        
        wp_send_json_success( [ 'deleted' => $deleted ] );
    }
    
    /**
     * AJAX: Reintentar fallades
     */
    public function ajax_queue_retry_failed() {
        check_ajax_referer( 'arxiu_queue_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . Arxiu_Queue_Manager::TABLE_NAME;
        
        $requeued = $wpdb->query( $wpdb->prepare(
            "UPDATE {$table_name} SET status = %s, attempts = 0, error_message = NULL 
             WHERE status = %s",
            Arxiu_Queue_Manager::STATUS_PENDING,
            Arxiu_Queue_Manager::STATUS_FAILED
        ) );
        
        wp_send_json_success( [ 'requeued' => $requeued ] );
    }
    
    /**
     * AJAX: Despausar la cua
     */
    public function ajax_queue_unpause() {
        check_ajax_referer( 'arxiu_queue_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sense permisos' );
        }
        
        $queue = Arxiu_Queue_Manager::get_instance();
        $queue->unpause_queue();
        
        wp_send_json_success();
    }
    
    /**
     * AJAX: Obtenir estadístiques de la cua
     */
    public function ajax_queue_stats() {
        check_ajax_referer( 'arxiu_nonce', 'nonce' );
        
        $queue = Arxiu_Queue_Manager::get_instance();
        wp_send_json_success( $queue->get_stats() );
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// INICIALITZACIÓ
// ═══════════════════════════════════════════════════════════════════════════════

add_action( 'plugins_loaded', function() {
    Arxiu_Monovar::get_instance();
}, 5 );

/**
 * Helper global
 */
function arxiu() {
    return Arxiu_Monovar::get_instance();
}

/**
 * Debug log
 */
function arxiu_log( $message, $data = null ) {
    if ( ! ARXIU_DEBUG ) return;
    $log = '[Arxiu ' . date( 'Y-m-d H:i:s' ) . '] ' . $message;
    if ( $data !== null ) $log .= ' | ' . print_r( $data, true );
    error_log( $log );
}

// ═══════════════════════════════════════════════════════════════════════════════
// ACTIVACIÓ / DESACTIVACIÓ
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Activació del plugin
 */
register_activation_hook( __FILE__, function() {
    // Crear taula de cua
    require_once ARXIU_PATH . 'includes/class-queue-manager.php';
    Arxiu_Queue_Manager::create_table();
    Arxiu_Queue_Manager::activate_cron();
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

/**
 * Desactivació del plugin
 */
register_deactivation_hook( __FILE__, function() {
    // Desactivar cron
    Arxiu_Queue_Manager::deactivate_cron();
});

