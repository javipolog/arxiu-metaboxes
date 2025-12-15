<?php
/**
 * Template: Metabox Autor i Col·lecció
 * 
 * @package Arxiu_Monovar
 * @since 13.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Obtenir termes
$autors = get_terms([
    'taxonomy'   => 'autor',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

$colleccions = get_terms([
    'taxonomy'   => 'colleccio',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);

// Termes assignats
$selected_autor = wp_get_post_terms( $post->ID, 'autor', [ 'fields' => 'ids' ] );
$selected_colleccio = wp_get_post_terms( $post->ID, 'colleccio', [ 'fields' => 'ids' ] );
?>

<div class="arxiu-autor-colleccio-wrapper">
    
    <div class="arxiu-ac-row">
        
        <!-- Autor -->
        <div class="arxiu-ac-field">
            <label for="arxiu_autor_select">
                <span class="dashicons dashicons-admin-users"></span>
                Autor
            </label>
            <select name="arxiu_autor_select" id="arxiu_autor_select">
                <option value="">— Sense autor —</option>
                <?php if ( ! is_wp_error( $autors ) ): ?>
                    <?php foreach ( $autors as $term ): ?>
                        <option value="<?php echo esc_attr( $term->term_id ); ?>" 
                                <?php selected( in_array( $term->term_id, $selected_autor ) ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=autor&post_type=arxiu' ); ?>" 
               class="arxiu-add-new" target="_blank" title="Afegir nou autor">
                <span class="dashicons dashicons-plus-alt2"></span>
            </a>
        </div>
        
        <!-- Col·lecció -->
        <div class="arxiu-ac-field">
            <label for="arxiu_colleccio_select">
                <span class="dashicons dashicons-portfolio"></span>
                Col·lecció
            </label>
            <select name="arxiu_colleccio_select" id="arxiu_colleccio_select">
                <option value="">— Sense col·lecció —</option>
                <?php if ( ! is_wp_error( $colleccions ) ): ?>
                    <?php foreach ( $colleccions as $term ): ?>
                        <option value="<?php echo esc_attr( $term->term_id ); ?>" 
                                <?php selected( in_array( $term->term_id, $selected_colleccio ) ); ?>>
                            <?php echo esc_html( $term->name ); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <a href="<?php echo admin_url( 'edit-tags.php?taxonomy=colleccio&post_type=arxiu' ); ?>" 
               class="arxiu-add-new" target="_blank" title="Afegir nova col·lecció">
                <span class="dashicons dashicons-plus-alt2"></span>
            </a>
        </div>
        
    </div>
    
    <div class="arxiu-ac-help">
        💡 <strong>Consell:</strong> Pots crear nous autors i col·leccions des del menú 
        <em>Arxius → Autor / Col·lecció</em> a la barra lateral.
    </div>
    
</div>

<style>
.arxiu-autor-colleccio-wrapper {
    padding: 0;
}

.arxiu-ac-row {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
}

.arxiu-ac-field {
    flex: 1;
    min-width: 250px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.arxiu-ac-field label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    font-size: 13px;
    color: #1d2327;
}

.arxiu-ac-field label .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #646970;
}

.arxiu-ac-field select {
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    background: #fff;
    width: 100%;
}

.arxiu-ac-field select:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.arxiu-ac-field {
    position: relative;
}

.arxiu-add-new {
    position: absolute;
    right: 8px;
    top: 32px;
    color: #2271b1;
    text-decoration: none;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.arxiu-add-new:hover {
    opacity: 1;
}

.arxiu-add-new .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.arxiu-ac-help {
    background: #f0f6fc;
    border-left: 3px solid #72aee6;
    padding: 10px 14px;
    font-size: 12px;
    color: #50575e;
    margin-top: 16px;
    border-radius: 0 4px 4px 0;
}
</style>
