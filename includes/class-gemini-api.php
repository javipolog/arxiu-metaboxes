<?php
/**
 * Arxiu Gemini API - Integració amb Google Gemini Vision
 * 
 * @package Arxiu_Monovar
 * @since 17.0.0
 * 
 * CANVIS v17.0.0:
 * - Actualització a models Gemini 2.5 Flash/Lite
 * - Anàlisi unificada (gènere + contingut en 1 crida)
 * - Mode debug configurable
 * - Millor gestió de quota i errors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Arxiu_Gemini_API {
    
    /**
     * Mode debug - Controla logging
     */
    private static $debug_mode = null;
    
    /**
     * Models disponibles - Actualitzat Desembre 2025
     * 
     * IMPORTANT: Usar noms exactes de l'API de Google
     * - gemini-2.5-flash (GA)
     * - gemini-2.5-flash-lite-preview-06-17 (Preview - nom complet!)
     * - gemini-2.0-flash (GA)
     * - gemini-2.0-flash-lite (GA)
     */
    const MODELS = [
        'gemini-2.5-flash'                     => 'Gemini 2.5 Flash (🏆 Recomanat - GA)',
        'gemini-2.5-flash-lite-preview-06-17'  => 'Gemini 2.5 Flash-Lite (⚡ Preview - Ràpid)',
        'gemini-2.0-flash'                     => 'Gemini 2.0 Flash (✅ GA - Estable)',
        'gemini-2.0-flash-lite'                => 'Gemini 2.0 Flash-Lite (💰 GA - Econòmic)',
        'gemini-1.5-flash'                     => 'Gemini 1.5 Flash (Legacy)',
    ];
    
    /**
     * Model per defecte
     */
    const DEFAULT_MODEL = 'gemini-2.5-flash';
    
    /**
     * Model per tasques simples (classificació)
     * Usem 2.0-flash-lite que és GA i molt ràpid
     */
    const LITE_MODEL = 'gemini-2.0-flash-lite';
    
    /**
     * Límits de quota (gratuït)
     */
    const QUOTA_LIMITS = [
        'rpm'  => 15,     // Requests per minute
        'rpd'  => 1500,   // Requests per day
        'tpm'  => 1000000 // Tokens per minute
    ];
    
    /**
     * Verificar si debug mode està actiu
     */
    public static function is_debug() {
        if ( self::$debug_mode === null ) {
            self::$debug_mode = defined( 'ARXIU_DEBUG' ) && ARXIU_DEBUG;
        }
        return self::$debug_mode;
    }
    
    /**
     * Logging condicional
     */
    private static function log( $message, $data = null ) {
        if ( ! self::is_debug() ) return;
        
        $log_message = '[Arxiu Gemini] ' . $message;
        if ( $data !== null ) {
            $log_message .= ' | ' . ( is_string( $data ) ? $data : wp_json_encode( $data ) );
        }
        error_log( $log_message );
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * ANÀLISI UNIFICADA (NOVA v17.0)
     * Una sola crida que retorna: gènere + tags + data + ubicació
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function analyze_complete( $image_id, $title = '', $api_key = null ) {
        if ( ! $api_key ) {
            $api_key = Arxiu_Credentials::get_api_key();
        }
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada' );
        }
        
        // Validar imatge
        $image_validation = self::validate_image( $image_id );
        if ( is_wp_error( $image_validation ) ) {
            return $image_validation;
        }
        
        $image_path = $image_validation['path'];
        $mime_type = $image_validation['mime'];
        $image_data = base64_encode( file_get_contents( $image_path ) );
        
        // Obtenir context del Knowledge Base
        $context = '';
        $ubicacions_context = '';
        if ( class_exists( 'Arxiu_Knowledge_Base' ) ) {
            $context = Arxiu_Knowledge_Base::get_gemini_context( 'complert' );
            $ubicacions_context = Arxiu_Knowledge_Base::get_ubicacions_context();
        }
        
        // Nom del fitxer per context addicional
        $filename = pathinfo( $image_path, PATHINFO_FILENAME );
        $filename = str_replace( [ '-', '_', '.' ], ' ', $filename );
        $combined_title = trim( $title . ' ' . $filename );
        
        // Construir prompt unificat
        $prompt = self::build_unified_prompt( $combined_title, $context, $ubicacions_context );
        
        self::log( 'Iniciant anàlisi unificada', [ 'image_id' => $image_id, 'title' => $combined_title ] );
        
        // Incrementar comptador
        self::increment_call_counter();
        
        // Cridar API amb model principal
        $result = self::call_api_v2( $prompt, $image_data, $mime_type, $api_key, [
            'temperature'      => 0.3,
            'maxOutputTokens'  => 4096,
            'responseMimeType' => 'application/json',
        ]);
        
        if ( is_wp_error( $result ) ) {
            self::log( 'Error en anàlisi', $result->get_error_message() );
            return $result;
        }
        
        // Processar i enriquir resposta
        $result = self::enrich_response( $result );
        
        self::log( 'Anàlisi completada', [ 'genre' => $result['genre'] ?? 'unknown' ] );
        
        return $result;
    }
    
    /**
     * Construir prompt unificat que demana TOT en una sola resposta
     */
    private static function build_unified_prompt( $title, $context, $ubicacions_context ) {
        return "Analitza aquesta imatge històrica de Monòver (Alacant).

TÍTOL/NOM FITXER: \"{$title}\"

{$context}
{$ubicacions_context}

TASCA: Respon amb JSON vàlid amb TOTA aquesta informació:

{
  \"genre\": \"fotografia|document|grafisme\",
  \"genre_confidence\": \"high|medium|low\",
  
  \"tags_val\": [\"etiquetes en VALENCIÀ (8-15 paraules)\"],
  \"tags_es\": [\"etiquetes en CASTELLÀ\"],
  \"summary\": \"descripció breu en valencià (2-3 frases)\",
  
  \"date_estimate\": {
    \"year\": 1970,
    \"year_end\": null,
    \"month\": null,
    \"day\": null,
    \"confidence\": \"high|medium|low\",
    \"source\": \"visible_text|visual_context|clothing|vehicles|photo_quality|festival\",
    \"visible_text_found\": false,
    \"reason\": \"Explicació breu\"
  },
  
  \"ubicacio_estimate\": {
    \"poi_slug\": \"torre-rellotge\",
    \"poi_nom\": \"Torre del Rellotge\",
    \"barri_slug\": \"Nucli Historic\",
    \"barri_nom\": \"Nucli Històric\",
    \"confidence\": \"high|medium|low\",
    \"elements_identificats\": [\"elements visuals identificats\"],
    \"reason\": \"Raonament breu\"
  },
  
  \"ubicacio_alternatives\": [],
  
  \"festa_detected\": null
}

INSTRUCCIONS:
1. GENRE: Classifica com fotografia (foto real), document (text/papers) o grafisme (dibuix/cartell/gràfic)
2. DATE: Busca text visible primer (cartells, periòdics), després roba, vehicles, qualitat foto
3. UBICACIO: Usa NOMÉS slugs de la llista proporcionada. Si no estàs segur, confidence \"low\"
4. TAGS: En valencià alacantí, sense noms de lloc ni anys, descriptius
5. Si no pots determinar alguna cosa, deixa null o array buit

Respon NOMÉS amb JSON vàlid, sense explicacions addicionals.";
    }
    
    /**
     * Enriquir resposta amb dades addicionals
     */
    private static function enrich_response( $result ) {
        // Assegurar estructura mínima
        $defaults = [
            'genre'              => 'fotografia',
            'genre_confidence'   => 'medium',
            'tags_val'           => [],
            'tags_es'            => [],
            'summary'            => '',
            'date_estimate'      => null,
            'ubicacio_estimate'  => null,
            'ubicacio_alternatives' => [],
            'festa_detected'     => null,
            '_analyzed_at'       => current_time( 'mysql' ),
            '_model_used'        => get_option( 'arxiu_gemini_model', self::DEFAULT_MODEL ),
        ];
        
        if ( is_array( $result ) ) {
            $result = array_merge( $defaults, $result );
        } else {
            $result = $defaults;
            $result['_raw_response'] = $result;
        }
        
        // Afegir term_id del gènere si existeix
        if ( ! empty( $result['genre'] ) ) {
            $genre_slug = sanitize_title( $result['genre'] );
            $term = get_term_by( 'slug', $genre_slug, 'generes' );
            if ( $term ) {
                $result['genre_term_id'] = $term->term_id;
                $result['genre_term_name'] = $term->name;
            }
        }
        
        return $result;
    }
    
    /**
     * Validar imatge abans de processar
     */
    private static function validate_image( $image_id ) {
        $image_path = get_attached_file( $image_id );
        $mime_type = get_post_mime_type( $image_id );
        
        if ( ! $image_path || ! file_exists( $image_path ) ) {
            return new WP_Error( 'no_image', 'Imatge no trobada (ID: ' . $image_id . ')' );
        }
        
        $supported_mimes = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
        if ( ! in_array( $mime_type, $supported_mimes ) ) {
            return new WP_Error( 'unsupported_mime', 'Tipus d\'imatge no suportat: ' . $mime_type );
        }
        
        $file_size = filesize( $image_path );
        if ( $file_size > 20 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', 'La imatge és massa gran (màx 20MB)' );
        }
        
        return [
            'path' => $image_path,
            'mime' => $mime_type,
            'size' => $file_size,
        ];
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * API CALL v2 - Amb retry intel·ligent i gestió de quota
     * ═══════════════════════════════════════════════════════════════════
     */
    public static function call_api_v2( $prompt, $image_data, $mime_type, $api_key, $config = [] ) {
        $model = get_option( 'arxiu_gemini_model', self::DEFAULT_MODEL );
        
        // Fallback models si el principal falla
        $models_to_try = [ $model ];
        if ( $model === 'gemini-2.5-flash' ) {
            $models_to_try[] = 'gemini-2.5-flash-lite';
            $models_to_try[] = 'gemini-2.0-flash';
        }
        
        $last_error = null;
        
        foreach ( $models_to_try as $try_model ) {
            $result = self::execute_api_call( $try_model, $prompt, $image_data, $mime_type, $api_key, $config );
            
            if ( ! is_wp_error( $result ) ) {
                return $result;
            }
            
            $error_code = $result->get_error_code();
            $last_error = $result;
            
            // Si és error de quota, esperar i reintentar amb model alternatiu
            if ( $error_code === 'rate_limit' || $error_code === 'quota_exceeded' ) {
                self::log( "Model {$try_model} quota esgotada, provant alternatiu..." );
                usleep( 1000000 ); // 1 segon
                continue;
            }
            
            // Si és error de model no disponible, provar alternatiu
            if ( $error_code === 'model_not_found' ) {
                self::log( "Model {$try_model} no disponible, provant alternatiu..." );
                continue;
            }
            
            // Altres errors: retornar immediatament
            break;
        }
        
        return $last_error;
    }
    
    /**
     * Executar crida a l'API
     */
    private static function execute_api_call( $model, $prompt, $image_data, $mime_type, $api_key, $config ) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;
        
        $default_config = [
            'temperature'      => 0.3,
            'topK'             => 32,
            'topP'             => 0.95,
            'maxOutputTokens'  => 4096,
        ];
        
        $gen_config = array_merge( $default_config, $config );
        
        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $prompt ],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data'      => $image_data,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => $gen_config,
        ];
        
        self::log( "Cridant API amb model: {$model}" );
        
        $response = wp_remote_post( $url, [
            'timeout' => 60,
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
        ]);
        
        if ( is_wp_error( $response ) ) {
            return new WP_Error( 'connection_error', 'Error de connexió: ' . $response->get_error_message() );
        }
        
        $status_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $data = json_decode( $response_body, true );
        
        self::log( "Resposta HTTP: {$status_code}" );
        
        // Gestionar errors HTTP
        if ( $status_code === 429 ) {
            return new WP_Error( 'rate_limit', 'Quota esgotada. Espera uns minuts.' );
        }
        
        if ( $status_code === 404 ) {
            return new WP_Error( 'model_not_found', "Model {$model} no disponible" );
        }
        
        if ( $status_code === 400 ) {
            $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Petició invàlida';
            return new WP_Error( 'bad_request', $error_msg );
        }
        
        if ( $status_code !== 200 ) {
            $error_msg = isset( $data['error']['message'] ) ? $data['error']['message'] : "HTTP {$status_code}";
            return new WP_Error( 'api_error', $error_msg );
        }
        
        // Verificar bloqueig de seguretat
        if ( isset( $data['promptFeedback']['blockReason'] ) ) {
            return new WP_Error( 'blocked', 'Contingut bloquejat: ' . $data['promptFeedback']['blockReason'] );
        }
        
        // Verificar candidates
        if ( empty( $data['candidates'] ) ) {
            return new WP_Error( 'no_response', 'L\'API no ha generat resposta' );
        }
        
        // Verificar finish reason
        $finish_reason = $data['candidates'][0]['finishReason'] ?? 'STOP';
        if ( $finish_reason === 'SAFETY' ) {
            return new WP_Error( 'safety_block', 'Bloquejat per filtres de seguretat' );
        }
        
        // Extreure text
        if ( ! isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return new WP_Error( 'invalid_response', 'Estructura de resposta inesperada' );
        }
        
        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Si esperem JSON, parsejar
        if ( isset( $config['responseMimeType'] ) && $config['responseMimeType'] === 'application/json' ) {
            $text = preg_replace( '/```json\s*|\s*```/', '', $text );
            $text = trim( $text );
            
            $parsed = json_decode( $text, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                return $parsed;
            }
            
            // Si no es pot parsejar, retornar raw
            return [ 'raw' => $text, '_parse_error' => json_last_error_msg() ];
        }
        
        return $text;
    }
    
    /**
     * ═══════════════════════════════════════════════════════════════════
     * FUNCIONS LEGACY (Mantingudes per compatibilitat)
     * ═══════════════════════════════════════════════════════════════════
     */
    
    /**
     * Analitzar imatge (legacy - crida a analyze_complete)
     */
    public static function analyze_image( $image_id, $title = '', $api_key = null ) {
        return self::analyze_complete( $image_id, $title, $api_key );
    }
    
    /**
     * Classificar gènere (legacy - ara inclòs en analyze_complete)
     * Mantingut per si es vol fer classificació ràpida sense anàlisi completa
     */
    public static function classify_genre( $image_id, $api_key = null ) {
        if ( ! $api_key ) {
            $api_key = Arxiu_Credentials::get_api_key();
        }
        
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'API key no configurada' );
        }
        
        $image_validation = self::validate_image( $image_id );
        if ( is_wp_error( $image_validation ) ) {
            return $image_validation;
        }
        
        $image_data = base64_encode( file_get_contents( $image_validation['path'] ) );
        
        // Incrementar comptador
        self::increment_call_counter();
        
        // Usar model lite per classificació simple
        $result = self::call_simple_classification( $image_data, $image_validation['mime'], $api_key );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        // Processar resposta
        $clean_text = strtolower( trim( str_replace( [ '.', '"', "'", "\n", "\r", '*' ], '', $result ) ) );
        
        $matched_slug = '';
        if ( strpos( $clean_text, 'fotogra' ) !== false || strpos( $clean_text, 'photo' ) !== false ) {
            $matched_slug = 'fotografia';
        } elseif ( strpos( $clean_text, 'document' ) !== false ) {
            $matched_slug = 'document';
        } elseif ( strpos( $clean_text, 'grafis' ) !== false || strpos( $clean_text, 'graph' ) !== false ) {
            $matched_slug = 'grafisme';
        }
        
        if ( empty( $matched_slug ) ) {
            return new WP_Error( 'no_match', "Resposta no reconeguda: '{$clean_text}'" );
        }
        
        $term = get_term_by( 'slug', $matched_slug, 'generes' );
        if ( ! $term ) {
            wp_insert_term( ucfirst( $matched_slug ), 'generes', [ 'slug' => $matched_slug ] );
            $term = get_term_by( 'slug', $matched_slug, 'generes' );
        }
        
        return [
            'term_id'   => $term->term_id,
            'term_name' => $term->name,
            'term_slug' => $term->slug,
            'raw'       => $clean_text,
        ];
    }
    
    /**
     * Classificació simple amb model lite
     * 
     * NOTA: Usem models GA (Generally Available) per més estabilitat
     */
    private static function call_simple_classification( $image_data, $mime_type, $api_key ) {
        // Models ordenats per velocitat i disponibilitat (tots GA)
        $models_to_try = [ 
            'gemini-2.0-flash-lite',  // GA - Més ràpid i econòmic
            'gemini-2.0-flash',       // GA - Estable
            'gemini-2.5-flash',       // GA - Més intel·ligent però més lent
        ];
        
        $last_error = '';
        
        foreach ( $models_to_try as $model ) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $api_key;
            
            $body = [
                'contents' => [
                    [
                        'parts' => [
                            [ 'text' => 'Classify this image. Reply with ONLY ONE word: Fotografia, Document, or Grafisme.' ],
                            [
                                'inline_data' => [
                                    'mime_type' => $mime_type,
                                    'data'      => $image_data,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature'     => 0.0,
                    'maxOutputTokens' => 10,
                ],
            ];
            
            $response = wp_remote_post( $url, [
                'timeout' => 30,
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode( $body ),
            ]);
            
            if ( is_wp_error( $response ) ) {
                $last_error = $response->get_error_message();
                continue;
            }
            
            $status_code = wp_remote_retrieve_response_code( $response );
            
            if ( $status_code === 429 ) {
                self::log( "⚠️ Rate limit amb {$model} (HTTP 429)" );
                $last_error = 'S\'ha superat el límit de consultes per minut. Espera uns segons.';
                usleep( 1000000 ); // 1 segon de pausa
                continue;
            }
            
            if ( $status_code === 404 ) {
                self::log( "❌ Model {$model} no existeix (HTTP 404)" );
                $last_error = "Model {$model} no disponible";
                continue;
            }
            
            if ( $status_code === 503 ) {
                self::log( "⚠️ Servei temporalment no disponible ({$model})" );
                $last_error = 'Servei de Gemini temporalment no disponible. Reintenta en uns minuts.';
                usleep( 500000 );
                continue;
            }
            
            if ( $status_code !== 200 ) {
                $data = json_decode( wp_remote_retrieve_body( $response ), true );
                $last_error = $data['error']['message'] ?? "HTTP {$status_code}";
                continue;
            }
            
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            
            if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
                self::log( "✅ Classificació OK amb {$model}" );
                return trim( $data['candidates'][0]['content']['parts'][0]['text'] );
            }
        }
        
        // Determinar el tipus d'error
        $error_msg = $last_error ?: 'Tots els models han fallat';
        if ( strpos( $last_error, 'límit' ) !== false || strpos( $last_error, 'rate' ) !== false ) {
            $error_msg = 'Quota API exhaurida. Espera un minut abans de reintentar.';
        }
        
        return new WP_Error( 'classification_failed', $error_msg );
    }
    
    /**
     * Test de connexió
     */
    public static function test_connection( $api_key, $model = null ) {
        if ( ! $model ) {
            $model = self::DEFAULT_MODEL;
        }
        
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}?key=" . $api_key;
        
        $response = wp_remote_get( $url, [ 'timeout' => 10 ] );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code( $response );
        
        if ( $code !== 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            $msg = isset( $body['error']['message'] ) ? $body['error']['message'] : "HTTP $code";
            return new WP_Error( 'connection_failed', $msg );
        }
        
        return true;
    }
    
    /**
     * Obtenir informació de quota (estimada)
     */
    public static function get_quota_info() {
        $today_count = get_transient( 'arxiu_api_calls_today' ) ?: 0;
        
        return [
            'calls_today'  => $today_count,
            'limit_daily'  => self::QUOTA_LIMITS['rpd'],
            'remaining'    => max( 0, self::QUOTA_LIMITS['rpd'] - $today_count ),
            'percent_used' => round( ( $today_count / self::QUOTA_LIMITS['rpd'] ) * 100, 1 ),
        ];
    }
    
    /**
     * Incrementar comptador de crides
     */
    public static function increment_call_counter() {
        $count = get_transient( 'arxiu_api_calls_today' ) ?: 0;
        $count++;
        
        // Expirar a mitjanit
        $midnight = strtotime( 'tomorrow midnight' ) - time();
        set_transient( 'arxiu_api_calls_today', $count, $midnight );
        
        return $count;
    }
    
    /**
     * Obtenir models disponibles
     */
    public static function get_available_models() {
        return self::MODELS;
    }
}
