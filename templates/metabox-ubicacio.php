<?php
/**
 * Template: Metabox Ubicació amb Mapa Mapbox
 * 
 * Versió optimitzada - JS en fitxer extern
 * 
 * @package Arxiu_Monovar
 * @since 16.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$area = get_post_meta( $post->ID, Arxiu_Monovar::META_AREA, true );
$lat  = get_post_meta( $post->ID, Arxiu_Monovar::META_LAT, true );
$lng  = get_post_meta( $post->ID, Arxiu_Monovar::META_LNG, true );

$mapbox_options = get_option( 'arxiu_mapbox_options', [] );
$has_token = ! empty( $mapbox_options['token'] );

$selected_terms = wp_get_post_terms( $post->ID, 'ubicacio', [ 'fields' => 'all' ] );
$selected_names = [];
if ( ! is_wp_error( $selected_terms ) && ! empty( $selected_terms ) ) {
    foreach ( $selected_terms as $term ) {
        $selected_names[] = $term->name;
    }
}
?>

<div class="arxiu-ubicacio-wrapper">
    
    <?php if ( ! $has_token ): ?>
    <div class="notice notice-warning inline" style="margin: 0 0 15px 0;">
        <p>
            ⚠️ <strong>Mapbox no configurat.</strong> 
            Ves a <a href="<?php echo admin_url( 'edit.php?post_type=arxiu&page=arxiu-mapbox-settings' ); ?>">Configuració Mapbox</a> 
            i afegeix el teu Access Token.
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Contenidor del Mapa -->
    <div id="arxiu-map-container">
        <?php if ( $has_token ): ?>
            <div id="arxiu-map"></div>
            <div id="arxiu-map-loading">
                <span class="spinner is-active"></span>
                <span>Carregant mapa...</span>
            </div>
        <?php else: ?>
            <div class="arxiu-map-placeholder">
                <span class="dashicons dashicons-location-alt"></span>
                <p>Configura Mapbox per veure el mapa</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Llegenda -->
    <div class="arxiu-ubicacio-legend">
        <span class="legend-title"><strong>Llegenda:</strong></span>
        <span class="legend-item">
            <span class="legend-dot poi-unselected"></span> POI
        </span>
        <span class="legend-item">
            <span class="legend-dot poi-selected"></span> POI seleccionat
        </span>
        <span class="legend-item">
            <span class="legend-fill barri-unselected"></span> Barri
        </span>
        <span class="legend-item">
            <span class="legend-fill barri-selected"></span> Barri seleccionat
        </span>
    </div>
    
    <!-- Controls -->
    <div class="arxiu-ubicacio-controls">
        <div class="arxiu-control-row">
            <label for="arxiu-ubicacio-area">
                <span class="dashicons dashicons-location"></span>
                <strong>Ubicació</strong>
            </label>
            <input type="text" 
                   id="arxiu-ubicacio-area" 
                   name="arxiu_area" 
                   value="<?php echo esc_attr( $area ?: implode( ', ', $selected_names ) ); ?>" 
                   placeholder="<?php echo $has_token ? 'Fes clic en un POI o barri del mapa...' : 'Configura Mapbox primer'; ?>" 
                   readonly>
            <button type="button" class="button" id="arxiu-ubicacio-clear" title="Netejar selecció">
                <span class="dashicons dashicons-trash"></span>
            </button>
        </div>
        
        <div class="arxiu-coords-display">
            <div class="arxiu-coord">
                <span class="label">Lat:</span>
                <span id="arxiu-lat-display"><?php echo $lat ?: '—'; ?></span>
            </div>
            <div class="arxiu-coord">
                <span class="label">Lng:</span>
                <span id="arxiu-lng-display"><?php echo $lng ?: '—'; ?></span>
            </div>
        </div>
    </div>
    
    <!-- Camps ocults per guardar coordenades -->
    <input type="hidden" id="arxiu-ubicacio-lat" name="arxiu_lat" value="<?php echo esc_attr( $lat ); ?>">
    <input type="hidden" id="arxiu-ubicacio-lng" name="arxiu_lng" value="<?php echo esc_attr( $lng ); ?>">
    
    <!-- Ajuda -->
    <p class="arxiu-ubicacio-help">
        <small>
            <strong>Barris:</strong> Selecció única (només 1 a la vegada) · 
            <strong>POIs:</strong> Selecció múltiple (fes clic per afegir/treure)
        </small>
    </p>
    
</div>

<style>
/* Placeholder quan no hi ha token */
.arxiu-map-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #646970;
    background: #f6f7f7;
}

.arxiu-map-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 12px;
    opacity: 0.5;
}
</style>
