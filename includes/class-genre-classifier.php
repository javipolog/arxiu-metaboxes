<?php
/**
 * Arxiu Genre Classifier - Classificació automàtica de gèneres amb IA
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Genre_Classifier {
    
    /**
     * Gèneres disponibles
     */
    const GENRES = [
        'fotografia' => 'Fotografia',
        'document'   => 'Document',
        'grafisme'   => 'Grafisme',
    ];
    
    /**
     * Classificar imatge
     */
    public static function classify( $image_id ) {
        $api_key = Arxiu_Credentials::get_api_key();
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada' );
        }
        
        return Arxiu_Gemini_API::classify_genre( $image_id, $api_key );
    }
    
    /**
     * Obtenir tots els gèneres com a termes
     */
    public static function get_genre_terms() {
        return get_terms([
            'taxonomy'   => 'generes',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);
    }
    
    /**
     * Assignar gènere a un post
     */
    public static function assign_genre( $post_id, $term_id ) {
        return wp_set_post_terms( $post_id, [ intval( $term_id ) ], 'generes' );
    }
    
    /**
     * Obtenir gèneres assignats a un post
     */
    public static function get_assigned_genres( $post_id ) {
        return wp_get_post_terms( $post_id, 'generes', [ 'fields' => 'ids' ] );
    }
    
    /**
     * Assegurar que existeixen els termes bàsics
     */
    public static function ensure_default_terms() {
        foreach ( self::GENRES as $slug => $name ) {
            if ( ! term_exists( $slug, 'generes' ) ) {
                wp_insert_term( $name, 'generes', [ 'slug' => $slug ] );
            }
        }
    }
}

// Crear termes per defecte en activació
add_action( 'init', function() {
    if ( taxonomy_exists( 'generes' ) ) {
        Arxiu_Genre_Classifier::ensure_default_terms();
    }
}, 20 );
