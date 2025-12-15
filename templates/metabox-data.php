<?php
/**
 * Template: Metabox Data amb Detecció Intel·ligent
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Obtenir anys assignats
$years = wp_get_post_terms( $post->ID, 'data', [ 'fields' => 'names' ] );
$years = array_map( 'intval', $years );

$is_exact = ( count( $years ) === 1 );
$min_year = $years ? min( $years ) : 1950;
$max_year = $years ? max( $years ) : 1980;
$current_year = (int) date( 'Y' );

// Dia i mes
$meta_dia = get_post_meta( $post->ID, Arxiu_Monovar::META_DATE_DIA, true );
$meta_mes = get_post_meta( $post->ID, Arxiu_Monovar::META_DATE_MES, true );
$date_source = get_post_meta( $post->ID, Arxiu_Monovar::META_DATE_SOURCE, true );

$has_api_key = Arxiu_Credentials::has_api_key();

// Mesos en valencià
$mesos = [
    1 => 'Gener', 2 => 'Febrer', 3 => 'Març', 4 => 'Abril',
    5 => 'Maig', 6 => 'Juny', 7 => 'Juliol', 8 => 'Agost',
    9 => 'Setembre', 10 => 'Octubre', 11 => 'Novembre', 12 => 'Desembre'
];
?>

<div class="arxiu-data-wrapper">
    
    <!-- Sistema de 3 nivells -->
    <div class="arxiu-detection-system">
        <div class="arxiu-detection-header">
            <h4>🔍 Detecció Intel·ligent de Dates</h4>
            <button type="button" class="button button-primary" id="arxiu-detect-date" <?php disabled( ! $has_api_key ); ?>>
                <span class="dashicons dashicons-search"></span>
                Detectar data automàticament
            </button>
        </div>
        
        <div class="arxiu-detection-steps">
            <div class="arxiu-step" id="step-title">
                <span class="step-number">1️⃣</span>
                <span class="step-label">Títol/Nom fitxer</span>
                <span class="step-status" id="step-title-status">—</span>
            </div>
            <div class="arxiu-step" id="step-visual">
                <span class="step-number">2️⃣</span>
                <span class="step-label">Text visible</span>
                <span class="step-status" id="step-visual-status">—</span>
            </div>
            <div class="arxiu-step" id="step-context">
                <span class="step-number">3️⃣</span>
                <span class="step-label">Context visual</span>
                <span class="step-status" id="step-context-status">—</span>
            </div>
        </div>
        
        <div class="arxiu-detection-result" id="arxiu-detection-result" style="display:none;">
            <!-- Resultat de la detecció -->
        </div>
    </div>
    
    <!-- Selector de mode -->
    <div class="arxiu-mode-selector">
        <label class="arxiu-mode-option">
            <input type="radio" name="data_mode" value="exact" <?php checked( $is_exact ); ?>>
            <span class="mode-icon">🎯</span>
            <span class="mode-label">Any exacte</span>
        </label>
        
        <label class="arxiu-mode-option">
            <input type="radio" name="data_mode" value="rango" <?php checked( ! $is_exact ); ?>>
            <span class="mode-icon">📊</span>
            <span class="mode-label">Rang d'anys</span>
        </label>
    </div>
    
    <!-- Slider any exacte -->
    <div class="arxiu-slider-wrapper" id="data-slider-exact-wrap" style="<?php echo $is_exact ? '' : 'display:none;'; ?>">
        <div class="arxiu-slider-label">
            <span>Any:</span>
            <strong id="year-exact-display"><?php echo esc_html( $min_year ); ?></strong>
        </div>
        <div id="year-slider-exact" class="arxiu-slider"></div>
        <input type="hidden" name="year_exact" id="year_exact" value="<?php echo esc_attr( $min_year ); ?>">
    </div>
    
    <!-- Slider rang -->
    <div class="arxiu-slider-wrapper" id="data-slider-range-wrap" style="<?php echo ! $is_exact ? '' : 'display:none;'; ?>">
        <div class="arxiu-slider-label">
            <span>Rang:</span>
            <strong><span id="year-range-display-min"><?php echo esc_html( $min_year ); ?></span> — <span id="year-range-display-max"><?php echo esc_html( $max_year ); ?></span></strong>
        </div>
        <div id="year-slider-range" class="arxiu-slider"></div>
        <input type="hidden" name="year_min" id="year_min" value="<?php echo esc_attr( $min_year ); ?>">
        <input type="hidden" name="year_max" id="year_max" value="<?php echo esc_attr( $max_year ); ?>">
    </div>
    
    <!-- Dia i Mes -->
    <div class="arxiu-date-extra">
        <div class="arxiu-field-group">
            <label for="data_dia">Dia</label>
            <input type="number" 
                   name="data_dia" 
                   id="data_dia" 
                   min="1" max="31" 
                   value="<?php echo esc_attr( $meta_dia ); ?>" 
                   placeholder="—">
        </div>
        
        <div class="arxiu-field-group">
            <label for="data_mes">Mes</label>
            <select name="data_mes" id="data_mes">
                <option value="">— Sense mes —</option>
                <?php foreach ( $mesos as $num => $nom ): ?>
                    <option value="<?php echo $num; ?>" <?php selected( $meta_mes, $num ); ?>>
                        <?php echo esc_html( $nom ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="arxiu-field-group arxiu-field-source">
            <label for="arxiu_date_source">Font</label>
            <input type="text" 
                   name="arxiu_date_source" 
                   id="arxiu_date_source" 
                   value="<?php echo esc_attr( $date_source ); ?>" 
                   placeholder="Automàtic" 
                   readonly>
        </div>
    </div>
    
    <!-- Ajuda -->
    <details class="arxiu-help-details">
        <summary>💡 Patrons de dates reconeguts</summary>
        <div class="arxiu-help-content">
            <ul>
                <li><code>15/09/1986</code> → Data completa</li>
                <li><code>setembre 1986</code> → Mes i any</li>
                <li><code>1986</code> → Any sol</li>
                <li><code>anys 80</code>, <code>80s</code> → Dècada</li>
                <li><code>curs 85-86</code> → Curs escolar</li>
                <li><code>Festes Patronals</code> → Setembre (6-10)</li>
                <li><code>Sant Antoni</code> → Gener (17)</li>
                <li><code>Falles</code> → Març (15-19)</li>
            </ul>
        </div>
    </details>
    
</div>

<style>
.arxiu-data-wrapper {
    padding: 0;
}

.arxiu-detection-system {
    background: #f8f9fa;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 20px;
}

.arxiu-detection-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.arxiu-detection-header h4 {
    margin: 0;
    font-size: 14px;
}

.arxiu-detection-steps {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}

.arxiu-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 12px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    text-align: center;
    transition: all 0.3s ease;
}

.arxiu-step.active {
    border-color: #2271b1;
    background: #f0f6fc;
}

.arxiu-step.success {
    border-color: #00a32a;
    background: #edfaef;
}

.arxiu-step.partial {
    border-color: #dba617;
    background: #fff8e5;
}

.arxiu-step.failed {
    border-color: #d63638;
    background: #fcf0f1;
}

.step-number {
    font-size: 20px;
    margin-bottom: 4px;
}

.step-label {
    font-size: 11px;
    color: #646970;
    margin-bottom: 4px;
}

.step-status {
    font-size: 11px;
    font-weight: 600;
}

.arxiu-detection-result {
    padding: 16px;
    border-radius: 8px;
    font-size: 13px;
    margin-top: 12px;
    line-height: 1.6;
}

.arxiu-detection-result.success {
    background: linear-gradient(135deg, #d1e7dd 0%, #c3e6cb 100%);
    color: #0f5132;
    border: 1px solid #badbcc;
}

.arxiu-detection-result.partial {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
    color: #664d03;
    border: 1px solid #ffecb5;
}

.arxiu-detection-result strong {
    font-size: 15px;
    display: block;
    margin-bottom: 8px;
}

.arxiu-detection-result em {
    display: block;
    font-style: normal;
    color: #495057;
    background: rgba(255,255,255,0.6);
    padding: 8px 12px;
    border-radius: 6px;
    margin-top: 8px;
}

.arxiu-confidence-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    margin-left: 8px;
}

.arxiu-confidence-badge.arxiu-confidence-high {
    background: #198754;
    color: #fff;
}

.arxiu-confidence-badge.arxiu-confidence-medium {
    background: #ffc107;
    color: #000;
}

.arxiu-confidence-badge.arxiu-confidence-low {
    background: #6c757d;
    color: #fff;
}

.arxiu-mode-selector {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
}

.arxiu-mode-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #fff;
    border: 2px solid #dcdcde;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.arxiu-mode-option:hover {
    border-color: #2271b1;
}

.arxiu-mode-option:has(input:checked) {
    border-color: #2271b1;
    background: #f0f6fc;
}

.arxiu-mode-option input {
    display: none;
}

.mode-icon {
    font-size: 18px;
}

.mode-label {
    font-weight: 500;
}

.arxiu-slider-wrapper {
    margin-bottom: 24px;
}

.arxiu-slider-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 13px;
}

.arxiu-slider {
    height: 8px;
    margin: 20px 0 40px;
}

.arxiu-date-extra {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.arxiu-field-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.arxiu-field-group label {
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

.arxiu-field-group input,
.arxiu-field-group select {
    padding: 6px 10px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 13px;
    min-width: 100px;
}

.arxiu-field-source input {
    background: #f0f0f1;
    color: #646970;
}

.arxiu-help-details {
    font-size: 12px;
    color: #646970;
}

.arxiu-help-details summary {
    cursor: pointer;
    padding: 8px 0;
}

.arxiu-help-content {
    padding: 12px;
    background: #f8f9fa;
    border-radius: 4px;
    margin-top: 8px;
}

.arxiu-help-content ul {
    margin: 0;
    padding-left: 20px;
}

.arxiu-help-content li {
    margin-bottom: 4px;
}

.arxiu-help-content code {
    background: #e0e0e0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

/* noUiSlider custom styles */
.noUi-target {
    background: #dcdcde;
    border: none;
    border-radius: 4px;
}

.noUi-connect {
    background: #2271b1;
}

.noUi-handle {
    width: 24px !important;
    height: 24px !important;
    border-radius: 50% !important;
    background: #fff !important;
    border: 2px solid #2271b1 !important;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
    cursor: pointer !important;
    top: -8px !important;
    right: -12px !important;
}

.noUi-handle:before,
.noUi-handle:after {
    display: none !important;
}

.noUi-tooltip {
    background: #1d2327;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    padding: 4px 8px;
}

.noUi-pips {
    color: #646970;
    font-size: 11px;
}

.noUi-marker-large {
    background: #8c8f94;
}

.noUi-value {
    cursor: pointer;
}

.noUi-value:hover {
    color: #2271b1;
}
</style>
