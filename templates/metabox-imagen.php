<?php
/**
 * Template: Metabox Imatge i Anàlisi IA
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$image_id = get_post_meta( $post->ID, Arxiu_Monovar::META_IMAGE_ID, true );
$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : '';
$tags_json = get_post_meta( $post->ID, Arxiu_Monovar::META_TAGS, true );
$description_data = get_post_meta( $post->ID, Arxiu_Monovar::META_DESCRIPTION, true );

$tags = [];
if ( $tags_json ) {
    $decoded = json_decode( $tags_json, true );
    if ( is_array( $decoded ) ) $tags = $decoded;
}

$description = [];
if ( $description_data ) {
    $decoded = json_decode( $description_data, true );
    if ( is_array( $decoded ) ) $description = $decoded;
}

$has_api_key = Arxiu_Credentials::has_api_key();
?>

<div class="arxiu-imagen-wrapper">
    
    <!-- SECCIÓ 1: Selecció d'imatge -->
    <div class="arxiu-section arxiu-section-image">
        <div class="arxiu-section-header">
            <h4><span class="dashicons dashicons-format-image"></span> Imatge Principal</h4>
        </div>
        
        <div class="arxiu-image-container <?php echo $image_id ? 'has-image' : ''; ?>" id="arxiu-image-container">
            <?php if ( $image_url ): ?>
                <div class="arxiu-image-preview" id="arxiu-image-preview">
                    <img src="<?php echo esc_url( $image_url ); ?>" id="arxiu-image-element">
                    <div class="arxiu-image-overlay" id="arxiu-image-overlay">
                        <!-- Pins es renderitzen aquí per JS -->
                    </div>
                </div>
            <?php else: ?>
                <div class="arxiu-image-placeholder" id="arxiu-image-placeholder">
                    <span class="dashicons dashicons-format-image"></span>
                    <p>Fes clic per seleccionar una imatge</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="arxiu-image-actions">
            <button type="button" class="button button-primary" id="arxiu-select-image">
                <span class="dashicons dashicons-upload"></span>
                <?php echo $image_id ? 'Canviar imatge' : 'Seleccionar imatge'; ?>
            </button>
            
            <?php if ( $image_id ): ?>
                <button type="button" class="button" id="arxiu-remove-image">
                    <span class="dashicons dashicons-trash"></span>
                    Eliminar
                </button>
            <?php endif; ?>
        </div>
        
        <input type="hidden" name="arxiu_image_id" id="arxiu-image-id" value="<?php echo esc_attr( $image_id ); ?>">
        <input type="hidden" name="arxiu_tags_data" id="arxiu-tags-data" value="<?php echo esc_attr( $tags_json ); ?>">
    </div>
    
    <!-- SECCIÓ 2: Detecció de persones -->
    <div class="arxiu-section arxiu-section-detection" <?php if ( ! $image_id ) echo 'style="display:none;"'; ?>>
        <div class="arxiu-section-header">
            <h4><span class="dashicons dashicons-groups"></span> Detecció de Persones</h4>
            <div class="arxiu-model-status" id="arxiu-model-status">
                <span class="status-dot"></span>
                <span class="status-text">Carregant IA...</span>
            </div>
        </div>
        
        <div class="arxiu-detection-toolbar">
            <button type="button" class="button button-primary" id="arxiu-detect-persons" title="Detectar persones automàticament">
                <span class="dashicons dashicons-visibility"></span>
                Re-escanejar
            </button>
            
            <button type="button" class="button" id="arxiu-add-manual-tag" title="Afegir etiqueta manualment">
                <span class="dashicons dashicons-plus-alt2"></span>
                Afegir manual
            </button>
            
            <span class="arxiu-detection-count" id="arxiu-detection-count">
                <?php echo count( $tags ); ?> etiquetes
            </span>
        </div>
        
        <p class="arxiu-help-text">
            <small>💡 Les persones es detecten automàticament. Fes clic a les caixes blaves per confirmar o afegeix etiquetes manualment.</small>
        </p>
        
        <div class="arxiu-tags-list" id="arxiu-tags-list">
            <!-- Es mostra la llista de tags per JS -->
        </div>
    </div>
    
    <!-- SECCIÓ 3: Anàlisi de contingut -->
    <div class="arxiu-section arxiu-section-analysis" <?php if ( ! $image_id ) echo 'style="display:none;"'; ?>>
        <div class="arxiu-section-header">
            <h4>🔍 Anàlisi de Contingut (Gemini)</h4>
            
            <?php if ( ! $has_api_key ): ?>
                <span class="arxiu-warning-badge">
                    ⚠️ API no configurada
                </span>
            <?php endif; ?>
        </div>
        
        <div class="arxiu-analysis-toolbar">
            <button type="button" class="button button-primary" id="arxiu-analyze-content" <?php disabled( ! $has_api_key ); ?>>
                <span class="dashicons dashicons-visibility"></span>
                Analitzar imatge
            </button>
            
            <button type="button" class="button" id="arxiu-reanalyze-content" <?php disabled( ! $has_api_key ); ?> style="display:none;">
                <span class="dashicons dashicons-update"></span>
                Re-analitzar
            </button>
        </div>
        
        <div class="arxiu-analysis-results" id="arxiu-analysis-results" style="<?php echo empty( $description ) ? 'display:none;' : ''; ?>">
            
            <!-- Etiquetes visuals -->
            <div class="arxiu-result-group">
                <label>Etiquetes (Valencià)</label>
                <div class="arxiu-visual-tags" id="arxiu-visual-tags" contenteditable="true">
                    <?php 
                    if ( ! empty( $description['tags_val'] ) && is_array( $description['tags_val'] ) ) {
                        foreach ( $description['tags_val'] as $tag ) {
                            echo '<span class="arxiu-tag">' . esc_html( $tag ) . '</span>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Descripció -->
            <div class="arxiu-result-group">
                <label>Descripció</label>
                <div class="arxiu-description-text" id="arxiu-description-text" contenteditable="true">
                    <?php echo esc_html( $description['summary'] ?? '' ); ?>
                </div>
            </div>
            
            <!-- Ubicació detectada -->
            <?php if ( ! empty( $description['ubicacio_estimate']['lloc_nom'] ) ): ?>
            <div class="arxiu-result-group arxiu-result-inline">
                <label>📍 Ubicació detectada</label>
                <span class="arxiu-detected-value">
                    <?php echo esc_html( $description['ubicacio_estimate']['lloc_nom'] ); ?>
                    <span class="arxiu-confidence-badge arxiu-confidence-<?php echo esc_attr( $description['ubicacio_estimate']['confidence'] ?? 'low' ); ?>">
                        <?php echo esc_html( $description['ubicacio_estimate']['confidence'] ?? '' ); ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Festa detectada -->
            <?php if ( ! empty( $description['festa_detected']['festa_nom'] ) ): ?>
            <div class="arxiu-result-group arxiu-result-inline">
                <label>🎉 Festa detectada</label>
                <span class="arxiu-detected-value">
                    <?php echo esc_html( $description['festa_detected']['festa_nom'] ); ?>
                    <span class="arxiu-confidence-badge arxiu-confidence-<?php echo esc_attr( $description['festa_detected']['confidence'] ?? 'low' ); ?>">
                        <?php echo esc_html( $description['festa_detected']['confidence'] ?? '' ); ?>
                    </span>
                </span>
            </div>
            <?php endif; ?>
            
        </div>
        
        <input type="hidden" name="arxiu_description_data" id="arxiu-description-data" value="<?php echo esc_attr( $description_data ); ?>">
    </div>
    
</div>

<!-- Loading overlay -->
<div class="arxiu-loading-overlay" id="arxiu-loading-overlay" style="display:none;">
    <div class="arxiu-loading-content">
        <span class="dashicons dashicons-update arxiu-spin"></span>
        <span class="arxiu-loading-text">Processant...</span>
    </div>
</div>
