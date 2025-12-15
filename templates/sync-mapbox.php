<?php
/**
 * Template: Sincronització Mapbox
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$options = get_option( 'arxiu_mapbox_options', [] );
$has_token = ! empty( $options['token'] );

// Comptar termes existents
$ubicacio_count = wp_count_terms( [ 'taxonomy' => 'ubicacio', 'hide_empty' => false ] );
?>

<div class="wrap">
    
    <h1>
        <span class="dashicons dashicons-update"></span>
        Sincronitzar Ubicacions
    </h1>
    
    <?php if ( ! $has_token ): ?>
    <div class="notice notice-error">
        <p>
            <strong>⚠️ Mapbox no configurat.</strong>
            <a href="<?php echo admin_url( 'edit.php?post_type=arxiu&page=arxiu-mapbox-settings' ); ?>">Configura el token primer</a>.
        </p>
    </div>
    <?php else: ?>
    
    <div class="card">
        <h2>📊 Estat Actual</h2>
        <p>
            <strong>Termes a "ubicacio":</strong> 
            <span id="current-count"><?php echo number_format_i18n( $ubicacio_count ); ?></span>
        </p>
        <p class="description">
            La sincronització importa els barris i POIs del teu tileset de Mapbox com a termes de la taxonomia "ubicacio".
        </p>
    </div>
    
    <div class="card">
        <h2>🔄 Sincronitzar</h2>
        
        <?php wp_nonce_field( 'arxiu_mapbox_nonce', 'arxiu_mapbox_nonce' ); ?>
        
        <p>
            <label>
                <input type="checkbox" id="clear-cache" checked>
                Netejar cache abans de sincronitzar
            </label>
        </p>
        
        <p>
            <button type="button" class="button button-primary button-hero" id="btn-sync">
                <span class="dashicons dashicons-download"></span>
                Iniciar Sincronització
            </button>
        </p>
        
        <div id="sync-progress" style="display:none; margin-top:20px;">
            <div class="sync-status">
                <span class="dashicons dashicons-update arxiu-spin"></span>
                <span id="sync-status-text">Sincronitzant...</span>
            </div>
        </div>
        
        <div id="sync-result" style="display:none; margin-top:20px;">
            <!-- Resultat -->
        </div>
    </div>
    
    <style>
    .sync-status {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        background: #fff3cd;
        border-left: 4px solid #ffcc00;
        border-radius: 0 4px 4px 0;
    }
    
    .arxiu-spin {
        animation: arxiu-spin 1s linear infinite;
    }
    
    @keyframes arxiu-spin {
        to { transform: rotate(360deg); }
    }
    
    #sync-result {
        padding: 16px;
        border-radius: 4px;
    }
    
    #sync-result.success {
        background: #d1e7dd;
        border: 1px solid #badbcc;
        color: #0f5132;
    }
    
    #sync-result.error {
        background: #f8d7da;
        border: 1px solid #f5c2c7;
        color: #842029;
    }
    
    #sync-result h3 {
        margin-top: 0;
    }
    
    #sync-result ul {
        margin-bottom: 0;
    }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        
        $('#btn-sync').on('click', function() {
            var $btn = $(this);
            var $progress = $('#sync-progress');
            var $result = $('#sync-result');
            
            $btn.prop('disabled', true);
            $progress.show();
            $result.hide().removeClass('success error');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'arxiu_sync_mapbox',
                    nonce: $('#arxiu_mapbox_nonce').val(),
                    clear_cache: $('#clear-cache').is(':checked') ? 'true' : 'false'
                },
                success: function(res) {
                    $progress.hide();
                    
                    if (res.success) {
                        var data = res.data;
                        $result.addClass('success').html(
                            '<h3>✅ Sincronització completada</h3>' +
                            '<ul>' +
                            '<li><strong>' + data.created + '</strong> termes creats</li>' +
                            '<li><strong>' + data.skipped + '</strong> termes ja existien</li>' +
                            '<li><strong>' + data.total_barrios + '</strong> barris trobats</li>' +
                            '<li><strong>' + data.total_pois + '</strong> POIs trobats</li>' +
                            '</ul>'
                        ).show();
                        
                        $('#current-count').text(parseInt($('#current-count').text()) + data.created);
                    } else {
                        $result.addClass('error').html(
                            '<h3>❌ Error</h3><p>' + res.data + '</p>'
                        ).show();
                    }
                },
                error: function() {
                    $progress.hide();
                    $result.addClass('error').html(
                        '<h3>❌ Error de connexió</h3>'
                    ).show();
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });
        
    });
    </script>
    
    <?php endif; ?>
    
</div>
