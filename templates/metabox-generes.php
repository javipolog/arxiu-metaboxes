<?php
/**
 * Template: Metabox Gèneres amb Classificació IA
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$terms = get_terms([
    'taxonomy'   => 'generes',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$selected = wp_get_post_terms( $post->ID, 'generes', [ 'fields' => 'ids' ] );
$image_id = get_post_meta( $post->ID, Arxiu_Monovar::META_IMAGE_ID, true );
$has_api_key = Arxiu_Credentials::has_api_key();
?>

<div class="arxiu-generes-wrapper">
    
    <!-- Toolbar IA -->
    <div class="arxiu-ia-toolbar">
        <div class="arxiu-ia-info">
            <span class="dashicons dashicons-superhero-alt"></span>
            <span>Classificació automàtica amb Intel·ligència Artificial</span>
        </div>
        
        <button type="button" 
                class="button button-primary" 
                id="arxiu-classify-genre"
                <?php disabled( ! $has_api_key || ! $image_id ); ?>
                title="<?php echo ! $has_api_key ? 'Configura l\'API Key primer' : ( ! $image_id ? 'Selecciona una imatge primer' : 'Classificar amb IA' ); ?>">
            <span class="dashicons dashicons-superhero-alt"></span>
            Auto-Classificar
        </button>
    </div>
    
    <!-- Resultat IA -->
    <div class="arxiu-ia-result" id="arxiu-genre-result" style="display:none;">
        <!-- Es mostra el resultat per JS -->
    </div>
    
    <?php if ( ! $has_api_key ): ?>
    <div class="arxiu-warning-box">
        ⚠️ <strong>API no configurada.</strong> 
        Ves a <a href="<?php echo admin_url( 'edit.php?post_type=arxiu&page=arxiu-settings' ); ?>">Configuració</a> 
        i afegeix la teva Google API Key.
    </div>
    <?php endif; ?>
    
    <!-- Botons de gèneres -->
    <div class="arxiu-generes-buttons">
        <?php if ( empty( $terms ) ): ?>
            <p class="arxiu-no-terms">
                No hi ha gèneres creats. 
                <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=generes&post_type=arxiu' ); ?>">Crear gèneres</a>
            </p>
        <?php else: ?>
            <?php foreach ( $terms as $term ): 
                $is_selected = in_array( $term->term_id, $selected );
            ?>
                <label class="arxiu-genre-btn <?php echo $is_selected ? 'selected' : ''; ?>" 
                       data-term-id="<?php echo esc_attr( $term->term_id ); ?>">
                    <input type="checkbox" 
                           name="generes_btns[]" 
                           value="<?php echo esc_attr( $term->term_id ); ?>"
                           <?php checked( $is_selected ); ?>>
                    <span class="arxiu-genre-icon">
                        <?php
                        // Icones per gènere
                        $icons = [
                            'fotografia' => '📷',
                            'document'   => '📄',
                            'grafisme'   => '🎨',
                        ];
                        echo $icons[ $term->slug ] ?? '📁';
                        ?>
                    </span>
                    <span class="arxiu-genre-name"><?php echo esc_html( $term->name ); ?></span>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <p class="arxiu-help-text">
        💡 Pots seleccionar múltiples gèneres. La IA només seleccionarà un a la vegada.
    </p>
    
</div>

<style>
.arxiu-generes-wrapper {
    padding: 0;
}

.arxiu-ia-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f0f6fc;
    border-left: 4px solid #72aee6;
    padding: 12px 16px;
    margin-bottom: 16px;
    border-radius: 0 4px 4px 0;
}

.arxiu-ia-info {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1d2327;
    font-size: 13px;
}

.arxiu-ia-result {
    padding: 10px 14px;
    margin-bottom: 16px;
    border-radius: 4px;
    font-size: 13px;
}

.arxiu-ia-result.success {
    background: #d1e7dd;
    border: 1px solid #badbcc;
    color: #0f5132;
}

.arxiu-ia-result.error {
    background: #f8d7da;
    border: 1px solid #f5c2c7;
    color: #842029;
}

.arxiu-ia-result.loading {
    background: #fff3cd;
    border: 1px solid #ffecb5;
    color: #664d03;
}

.arxiu-warning-box {
    background: #fff3cd;
    border-left: 3px solid #ffcc00;
    padding: 10px 14px;
    font-size: 12px;
    margin-bottom: 16px;
    border-radius: 0 4px 4px 0;
}

.arxiu-generes-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}

.arxiu-genre-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    background: #fff;
    border: 2px solid #c3c4c7;
    border-radius: 24px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
    font-weight: 500;
}

.arxiu-genre-btn:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.arxiu-genre-btn.selected {
    background: #2271b1;
    border-color: #2271b1;
    color: #fff;
}

.arxiu-genre-btn input {
    display: none;
}

.arxiu-genre-icon {
    font-size: 18px;
}

.arxiu-help-text {
    font-size: 12px;
    color: #646970;
    margin: 0;
}

.arxiu-no-terms {
    color: #646970;
    font-style: italic;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Toggle selecció de gèneres
    $('.arxiu-genre-btn').on('click', function(e) {
        if ($(e.target).is('input')) return;
        
        var $label = $(this);
        var $input = $label.find('input');
        
        $input.prop('checked', !$input.is(':checked'));
        $label.toggleClass('selected', $input.is(':checked'));
    });
    
    // Classificar amb IA
    $('#arxiu-classify-genre').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $result = $('#arxiu-genre-result');
        var imageId = $('#arxiu-image-id').val();
        
        if (!imageId) {
            $result.removeClass('success error loading')
                   .addClass('error')
                   .html('⚠️ Selecciona una imatge primer.')
                   .show();
            return;
        }
        
        // Loading state
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update arxiu-spin"></span> Analitzant...');
        
        $result.removeClass('success error')
               .addClass('loading')
               .html('⏳ Processant imatge (ID: ' + imageId + ')...')
               .show();
        
        $.ajax({
            url: ArxiuConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'arxiu_classify_genre',
                nonce: ArxiuConfig.nonce,
                image_id: imageId
            },
            success: function(response) {
                $btn.prop('disabled', false)
                    .html('<span class="dashicons dashicons-superhero-alt"></span> Auto-Classificar');
                
                if (response.success) {
                    var data = response.data;
                    
                    $result.removeClass('loading error')
                           .addClass('success')
                           .html('✅ Classificat com: <strong>' + data.term_name + '</strong>');
                    
                    // Desseleccionar tots
                    $('.arxiu-genre-btn').removeClass('selected')
                                         .find('input').prop('checked', false);
                    
                    // Seleccionar el nou
                    var $target = $('.arxiu-genre-btn[data-term-id="' + data.term_id + '"]');
                    if ($target.length) {
                        $target.addClass('selected')
                               .find('input').prop('checked', true);
                    }
                } else {
                    $result.removeClass('loading success')
                           .addClass('error')
                           .html('❌ ' + response.data);
                }
            },
            error: function() {
                $btn.prop('disabled', false)
                    .html('<span class="dashicons dashicons-superhero-alt"></span> Reintentar');
                
                $result.removeClass('loading success')
                       .addClass('error')
                       .html('❌ Error de connexió.');
            }
        });
    });
    
    // NO classificar automàticament quan es selecciona una imatge
    // L'anàlisi completa (analyzeComplete) ja inclou el gènere i 
    // actualitza la UI a través de renderAnalysisResults()
    // Això evita dobles crides a l'API i errors de quota
    $(document).on('arxiu_image_selected', function(e, newImageId) {
        console.log('📷 Nova imatge detectada (ID: ' + newImageId + ')');
        console.log('ℹ️ El gènere s\'assignarà automàticament amb l\'anàlisi completa');
        
        // Mostrar missatge d'espera
        $('#arxiu-genre-result')
            .removeClass('success error')
            .addClass('loading')
            .html('⏳ El gènere s\'identificarà amb l\'anàlisi completa...')
            .show();
    });
    
});
</script>
