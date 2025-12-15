<?php
/**
 * Template: Configuració Mapbox
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$options = get_option( 'arxiu_mapbox_options', [] );
$defaults = [
    'token'           => '',
    'style'           => 'mapbox://styles/mapbox/light-v11',
    'center_lng'      => -0.8389,
    'center_lat'      => 38.4372,
    'tileset_barri'   => 'javipolo.9kix7kmf',
    'layer_barri'     => 'barris_to_mapbox_181125-82e6ta',
    'tileset_poi'     => 'javipolo.09zhvvyp',
    'layer_poi'       => 'pois_to_mapbox_181125-0njp49',
];
$options = wp_parse_args( $options, $defaults );
$has_token = ! empty( $options['token'] );
?>

<div class="wrap">
    
    <h1>
        <span class="dashicons dashicons-location-alt"></span>
        Configuració Mapbox
    </h1>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'arxiu_mapbox_options' ); ?>
        <?php wp_nonce_field( 'arxiu_mapbox_nonce', 'arxiu_mapbox_nonce' ); ?>
        
        <!-- Token -->
        <div class="card">
            <h2>🔑 Access Token</h2>
            <table class="form-table">
                <tr>
                    <th><label for="mapbox_token">Mapbox Token</label></th>
                    <td>
                        <input type="password" 
                               id="mapbox_token" 
                               name="arxiu_mapbox_options[token]" 
                               value="<?php echo esc_attr( $options['token'] ); ?>"
                               class="regular-text"
                               placeholder="pk.eyJ1...">
                        <button type="button" class="button" id="toggle-token">Mostrar</button>
                        <button type="button" class="button" id="validate-token" <?php disabled( ! $has_token ); ?>>Validar</button>
                        <span id="token-status"></span>
                        <p class="description">
                            <a href="https://account.mapbox.com/access-tokens/" target="_blank">Obtenir token →</a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Estil i Centre -->
        <div class="card">
            <h2>🎨 Estil del Mapa</h2>
            <table class="form-table">
                <tr>
                    <th><label for="mapbox_style">Estil</label></th>
                    <td>
                        <select id="mapbox_style" name="arxiu_mapbox_options[style]">
                            <option value="mapbox://styles/mapbox/light-v11" <?php selected( $options['style'], 'mapbox://styles/mapbox/light-v11' ); ?>>Light</option>
                            <option value="mapbox://styles/mapbox/dark-v11" <?php selected( $options['style'], 'mapbox://styles/mapbox/dark-v11' ); ?>>Dark</option>
                            <option value="mapbox://styles/mapbox/streets-v12" <?php selected( $options['style'], 'mapbox://styles/mapbox/streets-v12' ); ?>>Streets</option>
                            <option value="mapbox://styles/mapbox/satellite-v9" <?php selected( $options['style'], 'mapbox://styles/mapbox/satellite-v9' ); ?>>Satellite</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label>Centre (Monòver)</label></th>
                    <td>
                        <label>
                            Longitud: 
                            <input type="number" step="0.0001" name="arxiu_mapbox_options[center_lng]" 
                                   value="<?php echo esc_attr( $options['center_lng'] ); ?>" style="width:120px;">
                        </label>
                        &nbsp;
                        <label>
                            Latitud: 
                            <input type="number" step="0.0001" name="arxiu_mapbox_options[center_lat]" 
                                   value="<?php echo esc_attr( $options['center_lat'] ); ?>" style="width:120px;">
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Tilesets -->
        <div class="card">
            <h2>📦 Tilesets</h2>
            <table class="form-table">
                <tr>
                    <th>Barris</th>
                    <td>
                        <label>
                            Tileset ID: 
                            <input type="text" name="arxiu_mapbox_options[tileset_barri]" 
                                   value="<?php echo esc_attr( $options['tileset_barri'] ); ?>" class="regular-text">
                        </label>
                        <br><br>
                        <label>
                            Layer: 
                            <input type="text" name="arxiu_mapbox_options[layer_barri]" 
                                   value="<?php echo esc_attr( $options['layer_barri'] ); ?>" class="regular-text">
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>POIs</th>
                    <td>
                        <label>
                            Tileset ID: 
                            <input type="text" name="arxiu_mapbox_options[tileset_poi]" 
                                   value="<?php echo esc_attr( $options['tileset_poi'] ); ?>" class="regular-text">
                        </label>
                        <br><br>
                        <label>
                            Layer: 
                            <input type="text" name="arxiu_mapbox_options[layer_poi]" 
                                   value="<?php echo esc_attr( $options['layer_poi'] ); ?>" class="regular-text">
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php submit_button( 'Desar configuració' ); ?>
        
    </form>
    
</div>

<script>
jQuery(document).ready(function($) {
    
    $('#toggle-token').on('click', function() {
        var $input = $('#mapbox_token');
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).text(type === 'password' ? 'Mostrar' : 'Amagar');
    });
    
    $('#validate-token').on('click', function() {
        var $btn = $(this);
        var $status = $('#token-status');
        var token = $('#mapbox_token').val();
        
        if (!token) {
            $status.html('<span style="color:#d63638;">✗ Token buit</span>');
            return;
        }
        
        $btn.prop('disabled', true);
        $status.html('<span style="color:#646970;">Validant...</span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_validate_mapbox_token',
                nonce: $('#arxiu_mapbox_nonce').val(),
                token: token
            },
            success: function(res) {
                if (res.success) {
                    $status.html('<span style="color:#00a32a;">✓ Token vàlid</span>');
                } else {
                    $status.html('<span style="color:#d63638;">✗ ' + res.data + '</span>');
                }
            },
            error: function() {
                $status.html('<span style="color:#d63638;">✗ Error de connexió</span>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
});
</script>
