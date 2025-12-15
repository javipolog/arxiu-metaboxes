<?php
/**
 * Arxiu Credentials - Gestió segura de credencials
 * 
 * @package Arxiu_Imagen_IA
 * @since 12.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Credentials {
    
    /**
     * Obté la clau d'encriptació
     */
    private static function get_encryption_key() {
        // Si hi ha una clau dedicada definida, usar-la
        if ( defined( 'ARXIU_ENCRYPTION_KEY' ) ) {
            return ARXIU_ENCRYPTION_KEY;
        }
        
        // Fallback a LOGGED_IN_KEY de WordPress
        if ( defined( 'LOGGED_IN_KEY' ) ) {
            return LOGGED_IN_KEY;
        }
        
        // Últim recurs: generar una clau basada en el path
        return md5( ABSPATH . 'arxiu-imagen-ia' );
    }
    
    /**
     * Encripta dades
     */
    public static function encrypt( $data ) {
        if ( empty( $data ) ) return '';
        
        // Comprovar que OpenSSL està disponible
        if ( ! function_exists( 'openssl_encrypt' ) ) {
            // Fallback: encoding simple (no segur, però millor que res)
            return base64_encode( $data );
        }
        
        $key = hash( 'sha256', self::get_encryption_key(), true );
        $iv = openssl_random_pseudo_bytes( 16 );
        $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
        
        return base64_encode( $iv . $encrypted );
    }
    
    /**
     * Desencripta dades
     */
    public static function decrypt( $data ) {
        if ( empty( $data ) ) return '';
        
        // Comprovar que OpenSSL està disponible
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            // Fallback
            return base64_decode( $data );
        }
        
        $data = base64_decode( $data );
        if ( strlen( $data ) < 16 ) return '';
        
        $key = hash( 'sha256', self::get_encryption_key(), true );
        $iv = substr( $data, 0, 16 );
        $encrypted = substr( $data, 16 );
        
        $decrypted = openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
        
        return $decrypted !== false ? $decrypted : '';
    }
    
    /**
     * Guarda l'API key de forma segura
     */
    public static function save_api_key( $api_key ) {
        if ( empty( $api_key ) ) {
            delete_option( 'arxiu_gemini_api_key_enc' );
            delete_option( 'arxiu_gemini_api_key' );
            return;
        }
        
        $encrypted = self::encrypt( $api_key );
        update_option( 'arxiu_gemini_api_key_enc', $encrypted );
        
        // Eliminar la versió antiga en text pla si existeix
        delete_option( 'arxiu_gemini_api_key' );
    }
    
    /**
     * Obté l'API key desencriptada
     */
    public static function get_api_key() {
        // Primer intentar la versió encriptada
        $encrypted = get_option( 'arxiu_gemini_api_key_enc', '' );
        if ( $encrypted ) {
            return self::decrypt( $encrypted );
        }
        
        // Fallback a la versió antiga (migració automàtica)
        $plain = get_option( 'arxiu_gemini_api_key', '' );
        if ( $plain ) {
            // Migrar a versió encriptada
            self::save_api_key( $plain );
            return $plain;
        }
        
        return '';
    }
    
    /**
     * Comprova si l'API key està configurada
     */
    public static function has_api_key() {
        return ! empty( self::get_api_key() );
    }
    
    /**
     * Obté els últims 4 caràcters de l'API key per mostrar
     */
    public static function get_api_key_hint() {
        $key = self::get_api_key();
        if ( empty( $key ) || strlen( $key ) < 8 ) {
            return '';
        }
        return '********' . substr( $key, -4 );
    }
}
