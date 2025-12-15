<?php
/**
 * Template: Pàgina de Configuració Principal
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No tens permisos' );

$api_key_hint = Arxiu_Credentials::get_api_key_hint();
$has_key = Arxiu_Credentials::has_api_key();
$current_model = get_option( 'arxiu_gemini_model', 'gemini-2.5-flash' );

// Diagnòstic
$diagnostics = [
    'php' => [
        'label'    => 'PHP',
        'value'    => PHP_VERSION,
        'ok'       => version_compare( PHP_VERSION, '7.4', '>=' ),
        'required' => '7.4+',
    ],
    'curl' => [
        'label'    => 'cURL',
        'value'    => function_exists( 'curl_version' ) ? curl_version()['version'] : 'No',
        'ok'       => function_exists( 'curl_version' ),
        'required' => 'Sí',
    ],
    'openssl' => [
        'label'    => 'OpenSSL',
        'value'    => function_exists( 'openssl_encrypt' ) ? 'Sí' : 'No',
        'ok'       => function_exists( 'openssl_encrypt' ),
        'required' => 'Recomanat',
    ],
    'memory' => [
        'label'    => 'Memòria',
        'value'    => ini_get( 'memory_limit' ),
        'ok'       => (int) ini_get( 'memory_limit' ) >= 256,
        'required' => '256M+',
    ],
    'gemini' => [
        'label'    => 'API Gemini',
        'value'    => $has_key ? 'Configurada (' . $api_key_hint . ')' : 'No configurada',
        'ok'       => $has_key,
        'required' => 'Sí',
    ],
];

// Estadístiques
$total_arxius = wp_count_posts( ARXIU_POST_TYPE )->publish ?? 0;
$total_generes = wp_count_terms( [ 'taxonomy' => 'generes', 'hide_empty' => false ] );
$total_ubicacions = wp_count_terms( [ 'taxonomy' => 'ubicacio', 'hide_empty' => false ] );
$total_autors = wp_count_terms( [ 'taxonomy' => 'autor', 'hide_empty' => false ] );
?>

<div class="wrap arxiu-settings-page">
    
    <h1>
        <span class="dashicons dashicons-archive"></span>
        Configuració Arxiu Monòver
    </h1>
    
    <div class="arxiu-settings-grid">
        
        <!-- Columna principal -->
        <div class="arxiu-settings-main">
            
            <!-- API Gemini -->
            <div class="arxiu-card">
                <h2>
                    <span class="dashicons dashicons-cloud"></span>
                    API de Google Gemini
                </h2>
                
                <p class="description">
                    L'API de Gemini s'utilitza per analitzar imatges, classificar gèneres i detectar dates.
                </p>
                
                <form id="arxiu-settings-form">
                    <?php wp_nonce_field( 'arxiu_settings_nonce', 'arxiu_settings_nonce' ); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="arxiu_api_key">API Key</label></th>
                            <td>
                                <div class="arxiu-input-group">
                                    <input type="password" 
                                           id="arxiu_api_key" 
                                           name="api_key" 
                                           class="regular-text"
                                           placeholder="<?php echo $has_key ? '••••••••' . $api_key_hint : 'Introdueix la teva API Key'; ?>"
                                           autocomplete="off">
                                    <button type="button" class="button" id="toggle-api-key" title="Mostrar/Amagar">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <p class="description">
                                    <a href="https://makersuite.google.com/app/apikey" target="_blank">
                                        Obtenir API Key de Google AI Studio →
                                    </a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="arxiu_model">Model</label></th>
                            <td>
                                <select id="arxiu_model" name="gemini_model">
                                    <option value="gemini-2.5-flash" <?php selected( $current_model, 'gemini-2.5-flash' ); ?>>
                                        🏆 Gemini 2.5 Flash (Recomanat - Gratuït)
                                    </option>
                                    <option value="gemini-2.5-flash-lite" <?php selected( $current_model, 'gemini-2.5-flash-lite' ); ?>>
                                        ⚡ Gemini 2.5 Flash-Lite (Ultra ràpid)
                                    </option>
                                    <option value="gemini-2.0-flash" <?php selected( $current_model, 'gemini-2.0-flash' ); ?>>
                                        Gemini 2.0 Flash (Estable)
                                    </option>
                                    <option value="gemini-1.5-flash" <?php selected( $current_model, 'gemini-1.5-flash' ); ?>>
                                        Gemini 1.5 Flash (Legacy)
                                    </option>
                                </select>
                                <p class="description">
                                    <strong>Gemini 2.5 Flash</strong>: Millor qualitat, thinking inclòs, gratuït.<br>
                                    <strong>Gemini 2.5 Flash-Lite</strong>: Més ràpid, ideal per classificació simple.
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-saved"></span>
                            Desar configuració
                        </button>
                        <span class="spinner"></span>
                        <span class="save-message"></span>
                    </p>
                </form>
                
                <!-- Test connexió -->
                <div class="arxiu-test-section">
                    <button type="button" class="button" id="test-gemini" <?php disabled( ! $has_key ); ?>>
                        <span class="dashicons dashicons-networking"></span>
                        Provar connexió
                    </button>
                    <span id="test-result"></span>
                </div>
            </div>
            
            <!-- Diagnòstic -->
            <div class="arxiu-card">
                <h2>
                    <span class="dashicons dashicons-heart"></span>
                    Diagnòstic del Sistema
                </h2>
                
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>Component</th>
                            <th>Valor</th>
                            <th>Requerit</th>
                            <th>Estat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $diagnostics as $check ): ?>
                        <tr>
                            <td><strong><?php echo esc_html( $check['label'] ); ?></strong></td>
                            <td><code><?php echo esc_html( $check['value'] ); ?></code></td>
                            <td><?php echo esc_html( $check['required'] ); ?></td>
                            <td>
                                <?php if ( $check['ok'] ): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-warning" style="color:#dba617;"></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Opcions Mediateca -->
            <div class="arxiu-card">
                <h2>
                    <span class="dashicons dashicons-format-gallery"></span>
                    Integració Mediateca
                </h2>
                
                <p class="description">
                    Configura com s'integra el sistema d'anàlisi IA amb la Mediateca de WordPress.
                </p>
                
                <?php
                $auto_queue = get_option( 'arxiu_auto_queue_uploads', false );
                $unanalyzed_count = class_exists( 'Arxiu_Media_Integration' ) 
                    ? Arxiu_Media_Integration::count_unanalyzed_images() 
                    : 0;
                $analyzed_count = class_exists( 'Arxiu_Media_Integration' ) 
                    ? Arxiu_Media_Integration::count_analyzed_images() 
                    : 0;
                ?>
                
                <table class="form-table">
                    <tr>
                        <th>Estadístiques</th>
                        <td>
                            <p>
                                <span class="dashicons dashicons-yes" style="color:#46b450;"></span>
                                <strong><?php echo number_format_i18n( $analyzed_count ); ?></strong> imatges analitzades
                            </p>
                            <p>
                                <span class="dashicons dashicons-clock" style="color:#f0b849;"></span>
                                <strong><?php echo number_format_i18n( $unanalyzed_count ); ?></strong> imatges pendents d'anàlisi
                            </p>
                            <?php if ( $unanalyzed_count > 0 ): ?>
                            <p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=arxiu-queue' ) ); ?>" class="button">
                                    Afegir a la cua →
                                </a>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="arxiu_auto_queue">Auto-anàlisi</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="arxiu_auto_queue" 
                                       name="arxiu_auto_queue_uploads" 
                                       value="1"
                                       <?php checked( $auto_queue ); ?>
                                       onchange="arxiuSaveAutoQueue(this.checked)">
                                <strong>Analitzar automàticament imatges noves</strong>
                            </label>
                            <p class="description">
                                Quan pugis una imatge nova, s'afegirà automàticament a la cua d'anàlisi IA.
                                <br><em>Nota: Es respectarà el límit diari de quota.</em>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <script>
                function arxiuSaveAutoQueue(enabled) {
                    jQuery.post(ajaxurl, {
                        action: 'arxiu_save_auto_queue',
                        nonce: '<?php echo wp_create_nonce( 'arxiu_settings_nonce' ); ?>',
                        enabled: enabled ? 1 : 0
                    }, function(response) {
                        if (response.success) {
                            // OK
                        }
                    });
                }
                </script>
            </div>
            
        </div>
        
        <!-- Sidebar -->
        <div class="arxiu-settings-sidebar">
            
            <!-- Info plugin -->
            <div class="arxiu-card">
                <h3>
                    <span class="dashicons dashicons-info"></span>
                    Sobre el Plugin
                </h3>
                <p><strong>Versió:</strong> <?php echo ARXIU_VERSION; ?></p>
                <p><strong>Funcionalitats:</strong></p>
                <ul>
                    <li>✓ Detecció de persones (TensorFlow.js)</li>
                    <li>✓ Anàlisi visual (Gemini)</li>
                    <li>✓ Classificació automàtica de gèneres</li>
                    <li>✓ Detecció intel·ligent de dates</li>
                    <li>✓ Mapes interactius (Mapbox)</li>
                </ul>
            </div>
            
            <!-- Estadístiques -->
            <div class="arxiu-card">
                <h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                    Estadístiques
                </h3>
                <div class="arxiu-stats">
                    <div class="arxiu-stat">
                        <span class="stat-value"><?php echo number_format_i18n( $total_arxius ); ?></span>
                        <span class="stat-label">Arxius</span>
                    </div>
                    <div class="arxiu-stat">
                        <span class="stat-value"><?php echo number_format_i18n( $total_generes ); ?></span>
                        <span class="stat-label">Gèneres</span>
                    </div>
                    <div class="arxiu-stat">
                        <span class="stat-value"><?php echo number_format_i18n( $total_ubicacions ); ?></span>
                        <span class="stat-label">Ubicacions</span>
                    </div>
                    <div class="arxiu-stat">
                        <span class="stat-value"><?php echo number_format_i18n( $total_autors ); ?></span>
                        <span class="stat-label">Autors</span>
                    </div>
                </div>
            </div>
            
            <!-- Knowledge Base -->
            <div class="arxiu-card">
                <h3>
                    <span class="dashicons dashicons-book"></span>
                    Base de Coneixement
                </h3>
                <p>Informació de Monòver:</p>
                <ul>
                    <li>📍 <?php echo count( Arxiu_Knowledge_Base::get_llocs() ); ?> llocs</li>
                    <li>🏘️ <?php echo count( Arxiu_Knowledge_Base::get_barris() ); ?> barris</li>
                    <li>🎉 <?php echo count( Arxiu_Knowledge_Base::get_festes() ); ?> festes</li>
                    <li>👤 <?php echo count( Arxiu_Knowledge_Base::get_personatges() ); ?> personatges</li>
                </ul>
            </div>
            
        </div>
        
    </div>
    
</div>

<style>
.arxiu-settings-page h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

.arxiu-settings-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 20px;
    margin-top: 20px;
}

.arxiu-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.arxiu-card h2,
.arxiu-card h3 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.arxiu-card ul {
    margin: 0;
    padding-left: 20px;
}

.arxiu-input-group {
    display: flex;
    gap: 4px;
}

.arxiu-input-group input {
    flex: 1;
}

.arxiu-test-section {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #dcdcde;
    display: flex;
    align-items: center;
    gap: 12px;
}

#test-result {
    font-weight: 600;
}

#test-result.success { color: #00a32a; }
#test-result.error { color: #d63638; }

.save-message {
    margin-left: 12px;
    font-weight: 600;
}

.save-message.success { color: #00a32a; }
.save-message.error { color: #d63638; }

.arxiu-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.arxiu-stat {
    text-align: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 4px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #2271b1;
}

.stat-label {
    font-size: 11px;
    color: #646970;
    text-transform: uppercase;
}

@media (max-width: 960px) {
    .arxiu-settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Toggle API key
    $('#toggle-api-key').on('click', function() {
        var $input = $('#arxiu_api_key');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).find('.dashicons')
               .toggleClass('dashicons-visibility dashicons-hidden');
    });
    
    // Guardar
    $('#arxiu-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var $spinner = $form.find('.spinner');
        var $msg = $form.find('.save-message');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $msg.text('').removeClass('success error');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_save_settings',
                nonce: $('#arxiu_settings_nonce').val(),
                api_key: $('#arxiu_api_key').val(),
                gemini_model: $('#arxiu_model').val()
            },
            success: function(res) {
                if (res.success) {
                    $msg.addClass('success').text('✓ Desat');
                    $('#arxiu_api_key').val('');
                    $('#test-gemini').prop('disabled', false);
                } else {
                    $msg.addClass('error').text('✗ Error');
                }
            },
            error: function() {
                $msg.addClass('error').text('✗ Error de connexió');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Test
    $('#test-gemini').on('click', function() {
        var $btn = $(this);
        var $result = $('#test-result');
        
        $btn.prop('disabled', true);
        $result.removeClass('success error').text('Provant...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_test_gemini',
                nonce: $('#arxiu_settings_nonce').val()
            },
            success: function(res) {
                if (res.success) {
                    $result.addClass('success').text('✓ Connexió correcta');
                } else {
                    $result.addClass('error').text('✗ ' + res.data.message);
                }
            },
            error: function() {
                $result.addClass('error').text('✗ Error');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
});
</script>
