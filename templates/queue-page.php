<?php
/**
 * Template: Pàgina de la Cua de Processament
 * 
 * @package Arxiu_Monovar
 * @since 17.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$queue = Arxiu_Queue_Manager::get_instance();
$stats = $queue->get_stats();
$items = $queue->get_queue_items( null, 1, 20 );
?>

<div class="wrap arxiu-queue-page">
    <h1>
        <span class="dashicons dashicons-list-view"></span>
        <?php _e( 'Cua de Processament', 'arxiu-monovar' ); ?>
    </h1>
    
    <p class="description">
        <?php _e( 'Les imatges es processen automàticament en background cada 2 minuts.', 'arxiu-monovar' ); ?>
    </p>
    
    <!-- ESTADÍSTIQUES -->
    <div class="arxiu-queue-stats">
        <div class="arxiu-stat-card arxiu-stat-pending">
            <span class="arxiu-stat-icon">⏳</span>
            <span class="arxiu-stat-value" id="stat-pending"><?php echo $stats['pending']; ?></span>
            <span class="arxiu-stat-label"><?php _e( 'Pendents', 'arxiu-monovar' ); ?></span>
        </div>
        
        <div class="arxiu-stat-card arxiu-stat-processing">
            <span class="arxiu-stat-icon">🔄</span>
            <span class="arxiu-stat-value" id="stat-processing"><?php echo $stats['processing']; ?></span>
            <span class="arxiu-stat-label"><?php _e( 'Processant', 'arxiu-monovar' ); ?></span>
        </div>
        
        <div class="arxiu-stat-card arxiu-stat-completed">
            <span class="arxiu-stat-icon">✅</span>
            <span class="arxiu-stat-value" id="stat-completed"><?php echo $stats['completed']; ?></span>
            <span class="arxiu-stat-label"><?php _e( 'Completades', 'arxiu-monovar' ); ?></span>
        </div>
        
        <div class="arxiu-stat-card arxiu-stat-failed">
            <span class="arxiu-stat-icon">❌</span>
            <span class="arxiu-stat-value" id="stat-failed"><?php echo $stats['failed']; ?></span>
            <span class="arxiu-stat-label"><?php _e( 'Fallades', 'arxiu-monovar' ); ?></span>
        </div>
        
        <div class="arxiu-stat-card arxiu-stat-quota">
            <span class="arxiu-stat-icon">📊</span>
            <span class="arxiu-stat-value" id="stat-quota">
                <?php echo $stats['quota']['calls_today']; ?>/<?php echo $stats['quota']['limit_daily']; ?>
            </span>
            <span class="arxiu-stat-label"><?php _e( 'Quota avui', 'arxiu-monovar' ); ?></span>
            <div class="arxiu-quota-bar">
                <div class="arxiu-quota-fill" style="width: <?php echo min( 100, $stats['quota']['percent_used'] ); ?>%"></div>
            </div>
        </div>
    </div>
    
    <!-- INFO CRON -->
    <div class="arxiu-queue-info">
        <p>
            <strong>⏰ Pròxima execució:</strong> 
            <span id="next-run"><?php echo esc_html( $stats['next_run'] ); ?></span>
            
            <?php if ( $stats['paused'] ): ?>
                <span class="arxiu-paused-badge">
                    ⏸️ <?php printf( __( 'Pausat fins %s', 'arxiu-monovar' ), $stats['paused_until'] ); ?>
                </span>
            <?php endif; ?>
        </p>
        <p>
            <strong>📅 Processades avui:</strong> 
            <span id="today-processed"><?php echo $stats['today_processed']; ?></span>
        </p>
    </div>
    
    <!-- ACCIONS -->
    <div class="arxiu-queue-actions">
        <button type="button" class="button button-primary" id="btn-add-images">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e( 'Afegir Imatges', 'arxiu-monovar' ); ?>
        </button>
        
        <button type="button" class="button" id="btn-process-now">
            <span class="dashicons dashicons-controls-play"></span>
            <?php _e( 'Processar Ara', 'arxiu-monovar' ); ?>
        </button>
        
        <button type="button" class="button" id="btn-retry-failed" <?php echo $stats['failed'] == 0 ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-image-rotate"></span>
            <?php _e( 'Reintentar Fallades', 'arxiu-monovar' ); ?>
        </button>
        
        <button type="button" class="button" id="btn-clear-completed" <?php echo $stats['completed'] == 0 ? 'disabled' : ''; ?>>
            <span class="dashicons dashicons-trash"></span>
            <?php _e( 'Netejar Completades', 'arxiu-monovar' ); ?>
        </button>
        
        <button type="button" class="button" id="btn-refresh">
            <span class="dashicons dashicons-update"></span>
            <?php _e( 'Actualitzar', 'arxiu-monovar' ); ?>
        </button>
    </div>
    
    <!-- LLISTA DE LA CUA -->
    <div class="arxiu-queue-list">
        <h2><?php _e( 'Elements de la Cua', 'arxiu-monovar' ); ?></h2>
        
        <table class="wp-list-table widefat fixed striped" id="queue-table">
            <thead>
                <tr>
                    <th class="column-thumb"><?php _e( 'Imatge', 'arxiu-monovar' ); ?></th>
                    <th class="column-filename"><?php _e( 'Nom', 'arxiu-monovar' ); ?></th>
                    <th class="column-status"><?php _e( 'Estat', 'arxiu-monovar' ); ?></th>
                    <th class="column-attempts"><?php _e( 'Intents', 'arxiu-monovar' ); ?></th>
                    <th class="column-time"><?php _e( 'Temps', 'arxiu-monovar' ); ?></th>
                    <th class="column-date"><?php _e( 'Afegit', 'arxiu-monovar' ); ?></th>
                    <th class="column-actions"><?php _e( 'Accions', 'arxiu-monovar' ); ?></th>
                </tr>
            </thead>
            <tbody id="queue-items">
                <?php if ( empty( $items ) ): ?>
                    <tr class="no-items">
                        <td colspan="7"><?php _e( 'La cua està buida', 'arxiu-monovar' ); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ( $items as $item ): ?>
                        <tr data-id="<?php echo $item->id; ?>" class="queue-item status-<?php echo $item->status; ?>">
                            <td class="column-thumb">
                                <?php if ( $item->thumbnail_url ): ?>
                                    <img src="<?php echo esc_url( $item->thumbnail_url ); ?>" alt="" width="50" height="50">
                                <?php else: ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-filename">
                                <a href="<?php echo get_edit_post_link( $item->attachment_id ); ?>" target="_blank">
                                    <?php echo esc_html( $item->filename ?: '#' . $item->attachment_id ); ?>
                                </a>
                            </td>
                            <td class="column-status">
                                <?php echo self::render_status_badge( $item->status ); ?>
                                <?php if ( $item->error_message ): ?>
                                    <span class="error-tooltip" title="<?php echo esc_attr( $item->error_message ); ?>">ℹ️</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-attempts">
                                <?php echo $item->attempts; ?>/3
                            </td>
                            <td class="column-time">
                                <?php if ( $item->processing_time_ms ): ?>
                                    <?php echo number_format( $item->processing_time_ms / 1000, 1 ); ?>s
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="column-date">
                                <?php echo human_time_diff( strtotime( $item->created_at ), current_time( 'timestamp' ) ); ?>
                            </td>
                            <td class="column-actions">
                                <?php if ( $item->status === 'completed' ): ?>
                                    <button class="button button-small btn-view-result" data-id="<?php echo $item->attachment_id; ?>">
                                        👁️ Veure
                                    </button>
                                <?php elseif ( $item->status === 'failed' ): ?>
                                    <button class="button button-small btn-retry-single" data-id="<?php echo $item->id; ?>">
                                        🔄 Reintentar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- MODAL AFEGIR IMATGES -->
    <div id="modal-add-images" class="arxiu-modal" style="display:none;">
        <div class="arxiu-modal-content">
            <div class="arxiu-modal-header">
                <h3><?php _e( 'Afegir Imatges a la Cua', 'arxiu-monovar' ); ?></h3>
                <button type="button" class="arxiu-modal-close">&times;</button>
            </div>
            <div class="arxiu-modal-body">
                <p><?php _e( 'Selecciona les imatges de la mediateca que vols processar:', 'arxiu-monovar' ); ?></p>
                
                <div class="arxiu-image-selector">
                    <button type="button" class="button button-large" id="btn-select-from-media">
                        <span class="dashicons dashicons-admin-media"></span>
                        <?php _e( 'Seleccionar de la Mediateca', 'arxiu-monovar' ); ?>
                    </button>
                    
                    <p class="description">
                        <?php _e( 'O afegir totes les imatges no analitzades:', 'arxiu-monovar' ); ?>
                    </p>
                    
                    <button type="button" class="button" id="btn-add-all-unanalyzed">
                        <span class="dashicons dashicons-images-alt2"></span>
                        <?php _e( 'Afegir totes les no analitzades', 'arxiu-monovar' ); ?>
                    </button>
                </div>
                
                <div id="selected-images-preview" style="display:none;">
                    <h4><?php _e( 'Imatges seleccionades:', 'arxiu-monovar' ); ?> <span id="selected-count">0</span></h4>
                    <div id="selected-images-grid"></div>
                </div>
            </div>
            <div class="arxiu-modal-footer">
                <button type="button" class="button" id="btn-cancel-add"><?php _e( 'Cancel·lar', 'arxiu-monovar' ); ?></button>
                <button type="button" class="button button-primary" id="btn-confirm-add" disabled>
                    <?php _e( 'Afegir a la Cua', 'arxiu-monovar' ); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- MODAL VEURE RESULTAT -->
    <div id="modal-view-result" class="arxiu-modal" style="display:none;">
        <div class="arxiu-modal-content arxiu-modal-large">
            <div class="arxiu-modal-header">
                <h3><?php _e( 'Resultat de l\'Anàlisi', 'arxiu-monovar' ); ?></h3>
                <button type="button" class="arxiu-modal-close">&times;</button>
            </div>
            <div class="arxiu-modal-body" id="result-content">
                <!-- Contingut carregat via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
.arxiu-queue-page { max-width: 1200px; }

/* Stats Cards */
.arxiu-queue-stats {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    flex-wrap: wrap;
}
.arxiu-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px 20px;
    text-align: center;
    min-width: 120px;
    flex: 1;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.arxiu-stat-icon { font-size: 24px; display: block; margin-bottom: 5px; }
.arxiu-stat-value { font-size: 28px; font-weight: bold; display: block; }
.arxiu-stat-label { color: #666; font-size: 12px; text-transform: uppercase; }

.arxiu-stat-pending { border-left: 4px solid #f0ad4e; }
.arxiu-stat-processing { border-left: 4px solid #5bc0de; }
.arxiu-stat-completed { border-left: 4px solid #5cb85c; }
.arxiu-stat-failed { border-left: 4px solid #d9534f; }
.arxiu-stat-quota { border-left: 4px solid #337ab7; }

/* Quota Bar */
.arxiu-quota-bar {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    margin-top: 8px;
    overflow: hidden;
}
.arxiu-quota-fill {
    height: 100%;
    background: linear-gradient(90deg, #5cb85c, #f0ad4e, #d9534f);
    transition: width 0.3s ease;
}

/* Info */
.arxiu-queue-info {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}
.arxiu-queue-info p { margin: 5px 0; }
.arxiu-paused-badge {
    background: #f0ad4e;
    color: #fff;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
}

/* Actions */
.arxiu-queue-actions {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.arxiu-queue-actions .button .dashicons {
    vertical-align: middle;
    margin-right: 3px;
}

/* Table */
.arxiu-queue-list table { margin-top: 10px; }
.column-thumb { width: 60px; }
.column-thumb img { border-radius: 4px; }
.column-status { width: 100px; }
.column-attempts { width: 70px; text-align: center; }
.column-time { width: 70px; text-align: center; }
.column-date { width: 100px; }
.column-actions { width: 100px; }

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
}
.status-pending { background: #fff3cd; color: #856404; }
.status-processing { background: #cce5ff; color: #004085; }
.status-completed { background: #d4edda; color: #155724; }
.status-failed { background: #f8d7da; color: #721c24; }

.error-tooltip { cursor: help; margin-left: 5px; }

/* Modal */
.arxiu-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.arxiu-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.arxiu-modal-large { max-width: 900px; }
.arxiu-modal-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.arxiu-modal-header h3 { margin: 0; }
.arxiu-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.arxiu-modal-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
}
.arxiu-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

/* Image Selector */
.arxiu-image-selector { text-align: center; padding: 20px; }
.arxiu-image-selector .button-large { padding: 15px 30px; }

#selected-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
    margin-top: 10px;
}
#selected-images-grid img {
    width: 100%;
    height: 80px;
    object-fit: cover;
    border-radius: 4px;
    border: 2px solid #ddd;
}
</style>

<script>
jQuery(document).ready(function($) {
    var selectedAttachments = [];
    var mediaFrame;
    
    // Actualitzar estadístiques cada 30 segons
    setInterval(refreshStats, 30000);
    
    function refreshStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_queue_status',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    updateStatsUI(response.data.stats);
                    updateQueueTable(response.data.items);
                }
            }
        });
    }
    
    function updateStatsUI(stats) {
        $('#stat-pending').text(stats.pending);
        $('#stat-processing').text(stats.processing);
        $('#stat-completed').text(stats.completed);
        $('#stat-failed').text(stats.failed);
        $('#stat-quota').text(stats.quota.calls_today + '/' + stats.quota.limit_daily);
        $('.arxiu-quota-fill').css('width', Math.min(100, stats.quota.percent_used) + '%');
        $('#next-run').text(stats.next_run);
        $('#today-processed').text(stats.today_processed);
        
        $('#btn-retry-failed').prop('disabled', stats.failed == 0);
        $('#btn-clear-completed').prop('disabled', stats.completed == 0);
    }
    
    function updateQueueTable(items) {
        // Simplificat - només actualitza si hi ha canvis significatius
        if (items.length !== $('#queue-items tr').length) {
            location.reload();
        }
    }
    
    // Botó Actualitzar
    $('#btn-refresh').click(function() {
        $(this).prop('disabled', true).find('.dashicons').addClass('spin');
        refreshStats();
        setTimeout(function() {
            $('#btn-refresh').prop('disabled', false).find('.dashicons').removeClass('spin');
        }, 1000);
    });
    
    // Botó Processar Ara
    $('#btn-process-now').click(function() {
        $(this).prop('disabled', true);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_process_queue_now',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>'
            },
            complete: function() {
                setTimeout(refreshStats, 2000);
                $('#btn-process-now').prop('disabled', false);
            }
        });
    });
    
    // Botó Reintentar Fallades
    $('#btn-retry-failed').click(function() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_queue_retry',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e( 'Reintentant', 'arxiu-monovar' ); ?> ' + response.data.retried + ' <?php _e( 'elements', 'arxiu-monovar' ); ?>');
                    refreshStats();
                }
            }
        });
    });
    
    // Botó Netejar Completades
    $('#btn-clear-completed').click(function() {
        if (!confirm('<?php _e( 'Eliminar registres completats de fa més d\'1 dia?', 'arxiu-monovar' ); ?>')) return;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_queue_clear',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e( 'Eliminats', 'arxiu-monovar' ); ?> ' + response.data.deleted + ' <?php _e( 'registres', 'arxiu-monovar' ); ?>');
                    refreshStats();
                }
            }
        });
    });
    
    // Modal Afegir Imatges
    $('#btn-add-images').click(function() {
        $('#modal-add-images').show();
    });
    
    $('.arxiu-modal-close, #btn-cancel-add').click(function() {
        $('.arxiu-modal').hide();
        selectedAttachments = [];
        updateSelectedPreview();
    });
    
    // Seleccionar de Mediateca
    $('#btn-select-from-media').click(function() {
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        mediaFrame = wp.media({
            title: '<?php _e( 'Selecciona Imatges', 'arxiu-monovar' ); ?>',
            button: { text: '<?php _e( 'Afegir a la Cua', 'arxiu-monovar' ); ?>' },
            multiple: true,
            library: { type: 'image' }
        });
        
        mediaFrame.on('select', function() {
            var selection = mediaFrame.state().get('selection');
            selection.each(function(attachment) {
                var data = attachment.toJSON();
                if (selectedAttachments.indexOf(data.id) === -1) {
                    selectedAttachments.push(data.id);
                }
            });
            updateSelectedPreview();
        });
        
        mediaFrame.open();
    });
    
    function updateSelectedPreview() {
        if (selectedAttachments.length > 0) {
            $('#selected-images-preview').show();
            $('#selected-count').text(selectedAttachments.length);
            $('#btn-confirm-add').prop('disabled', false);
        } else {
            $('#selected-images-preview').hide();
            $('#btn-confirm-add').prop('disabled', true);
        }
    }
    
    // Confirmar Afegir
    $('#btn-confirm-add').click(function() {
        $(this).prop('disabled', true).text('<?php _e( 'Afegint...', 'arxiu-monovar' ); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_queue_add',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>',
                attachment_ids: selectedAttachments
            },
            success: function(response) {
                if (response.success) {
                    var msg = '<?php _e( 'Afegides', 'arxiu-monovar' ); ?> ' + response.data.added + ' <?php _e( 'imatges a la cua', 'arxiu-monovar' ); ?>';
                    alert(msg);
                    $('.arxiu-modal').hide();
                    selectedAttachments = [];
                    refreshStats();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            complete: function() {
                $('#btn-confirm-add').prop('disabled', false).text('<?php _e( 'Afegir a la Cua', 'arxiu-monovar' ); ?>');
            }
        });
    });
    
    // Veure Resultat
    $(document).on('click', '.btn-view-result', function() {
        var attachmentId = $(this).data('id');
        $('#result-content').html('<p><?php _e( 'Carregant...', 'arxiu-monovar' ); ?></p>');
        $('#modal-view-result').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'arxiu_get_attachment_analysis',
                nonce: '<?php echo wp_create_nonce( 'arxiu_nonce' ); ?>',
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success && response.data) {
                    var html = '<pre style="background:#f5f5f5;padding:15px;overflow:auto;max-height:400px;">' + 
                               JSON.stringify(response.data, null, 2) + '</pre>';
                    $('#result-content').html(html);
                } else {
                    $('#result-content').html('<p><?php _e( 'No hi ha dades disponibles', 'arxiu-monovar' ); ?></p>');
                }
            }
        });
    });
});
</script>

<?php
/**
 * Helper per renderitzar badge d'estat
 */
function render_status_badge( $status ) {
    $labels = [
        'pending'    => '⏳ Pendent',
        'processing' => '🔄 Processant',
        'completed'  => '✅ Completat',
        'failed'     => '❌ Fallat',
        'skipped'    => '⏭️ Omès',
    ];
    
    $label = isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
    return '<span class="status-badge status-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
}
?>
