<?php
/**
 * Template: Cua de Processament
 * 
 * @package Arxiu_Monovar
 * @since 17.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$queue = Arxiu_Queue_Manager::get_instance();
$stats = $queue->get_stats();
$recent_items = $queue->get_recent_items( 20 );
?>

<div class="wrap arxiu-queue-page">
    <h1>📷 Cua de Processament d'Imatges</h1>
    
    <div class="arxiu-queue-dashboard">
        
        <!-- ESTADÍSTIQUES -->
        <div class="arxiu-queue-stats">
            <div class="arxiu-stat-card arxiu-stat-pending">
                <span class="arxiu-stat-number"><?php echo esc_html( $stats['pending'] ); ?></span>
                <span class="arxiu-stat-label">⏳ Pendents</span>
            </div>
            <div class="arxiu-stat-card arxiu-stat-processing">
                <span class="arxiu-stat-number"><?php echo esc_html( $stats['processing'] ); ?></span>
                <span class="arxiu-stat-label">🔄 Processant</span>
            </div>
            <div class="arxiu-stat-card arxiu-stat-completed">
                <span class="arxiu-stat-number"><?php echo esc_html( $stats['completed'] ); ?></span>
                <span class="arxiu-stat-label">✅ Completats</span>
            </div>
            <div class="arxiu-stat-card arxiu-stat-failed">
                <span class="arxiu-stat-number"><?php echo esc_html( $stats['failed'] ); ?></span>
                <span class="arxiu-stat-label">❌ Fallats</span>
            </div>
        </div>
        
        <!-- QUOTA -->
        <div class="arxiu-quota-box">
            <h3>📊 Quota Diària</h3>
            <div class="arxiu-quota-bar">
                <div class="arxiu-quota-fill" style="width: <?php echo min( 100, ( $stats['today_processed'] / $stats['daily_limit'] ) * 100 ); ?>%"></div>
            </div>
            <p class="arxiu-quota-text">
                <strong><?php echo esc_html( $stats['today_processed'] ); ?></strong> / <?php echo esc_html( $stats['daily_limit'] ); ?> 
                <span class="arxiu-quota-remaining">(<?php echo esc_html( $stats['quota_remaining'] ); ?> restants)</span>
            </p>
            <?php if ( $stats['is_paused'] ): ?>
                <p class="arxiu-paused-notice">⚠️ Cua pausada fins <?php echo esc_html( $stats['pause_until'] ); ?> (quota temporal esgotada)</p>
            <?php endif; ?>
            <?php if ( $stats['next_run'] ): ?>
                <p class="arxiu-next-run">Pròxim processament: <?php echo esc_html( $stats['next_run'] ); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- ACCIONS -->
        <div class="arxiu-queue-actions">
            <h3>⚡ Accions</h3>
            
            <form method="post" class="arxiu-action-form" id="arxiu-bulk-queue-form">
                <?php wp_nonce_field( 'arxiu_queue_action', 'arxiu_queue_nonce' ); ?>
                <input type="hidden" name="arxiu_action" value="bulk_add">
                
                <div class="arxiu-action-row">
                    <label for="arxiu-bulk-count">Afegir imatges no analitzades:</label>
                    <select name="bulk_count" id="arxiu-bulk-count">
                        <option value="10">10 imatges</option>
                        <option value="25">25 imatges</option>
                        <option value="50">50 imatges</option>
                        <option value="100">100 imatges</option>
                        <option value="500">Totes (màx 500)</option>
                    </select>
                    <button type="submit" class="button button-primary">📥 Afegir a la cua</button>
                </div>
            </form>
            
            <div class="arxiu-action-buttons">
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field( 'arxiu_queue_action', 'arxiu_queue_nonce' ); ?>
                    <input type="hidden" name="arxiu_action" value="process_now">
                    <button type="submit" class="button">▶️ Processar ara</button>
                </form>
                
                <?php if ( $stats['failed'] > 0 ): ?>
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field( 'arxiu_queue_action', 'arxiu_queue_nonce' ); ?>
                    <input type="hidden" name="arxiu_action" value="retry_failed">
                    <button type="submit" class="button">🔄 Reintentar fallats (<?php echo esc_html( $stats['failed'] ); ?>)</button>
                </form>
                <?php endif; ?>
                
                <form method="post" style="display:inline;" onsubmit="return confirm('Segur que vols buidar la cua?');">
                    <?php wp_nonce_field( 'arxiu_queue_action', 'arxiu_queue_nonce' ); ?>
                    <input type="hidden" name="arxiu_action" value="clear">
                    <button type="submit" class="button">🗑️ Buidar cua</button>
                </form>
                
                <form method="post" style="display:inline;">
                    <?php wp_nonce_field( 'arxiu_queue_action', 'arxiu_queue_nonce' ); ?>
                    <input type="hidden" name="arxiu_action" value="cleanup">
                    <button type="submit" class="button">🧹 Netejar antics</button>
                </form>
            </div>
        </div>
        
        <!-- LLISTA D'ITEMS -->
        <div class="arxiu-queue-items">
            <h3>📋 Items Recents</h3>
            
            <?php if ( empty( $recent_items ) ): ?>
                <p class="arxiu-empty-queue">La cua està buida. Puja imatges o afegeix-les manualment.</p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th width="80">Imatge</th>
                            <th>Fitxer</th>
                            <th width="100">Estat</th>
                            <th width="80">Intents</th>
                            <th width="150">Creat</th>
                            <th>Resultat / Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $recent_items as $item ): ?>
                            <?php
                            $thumb = wp_get_attachment_image( $item->attachment_id, [ 50, 50 ] );
                            $status_class = 'arxiu-status-' . $item->status;
                            $status_icon = [
                                'pending'    => '⏳',
                                'processing' => '🔄',
                                'completed'  => '✅',
                                'failed'     => '❌',
                                'skipped'    => '⏭️',
                            ];
                            ?>
                            <tr class="<?php echo esc_attr( $status_class ); ?>">
                                <td><?php echo esc_html( $item->id ); ?></td>
                                <td><?php echo $thumb ?: '—'; ?></td>
                                <td>
                                    <strong><?php echo esc_html( $item->attachment_title ?: 'Sense títol' ); ?></strong>
                                    <?php if ( $item->attachment_file ): ?>
                                        <br><small><?php echo esc_html( basename( $item->attachment_file ) ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="arxiu-status-badge <?php echo esc_attr( $status_class ); ?>">
                                        <?php echo esc_html( $status_icon[ $item->status ] ?? '' ); ?>
                                        <?php echo esc_html( ucfirst( $item->status ) ); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html( $item->attempts ); ?> / <?php echo esc_html( Arxiu_Queue_Manager::CONFIG['max_attempts'] ); ?></td>
                                <td>
                                    <?php echo esc_html( date( 'd/m H:i', strtotime( $item->created_at ) ) ); ?>
                                    <?php if ( $item->processing_time_ms ): ?>
                                        <br><small><?php echo esc_html( $item->processing_time_ms ); ?>ms</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $item->result_summary ): ?>
                                        <?php
                                        $summary = json_decode( $item->result_summary, true );
                                        if ( $summary ):
                                        ?>
                                            <small>
                                                <?php if ( ! empty( $summary['genre'] ) ): ?>🎭 <?php echo esc_html( $summary['genre'] ); ?><?php endif; ?>
                                                <?php if ( ! empty( $summary['year'] ) ): ?> 📅 <?php echo esc_html( $summary['year'] ); ?><?php endif; ?>
                                                <?php if ( ! empty( $summary['poi'] ) ): ?> 📍 <?php echo esc_html( $summary['poi'] ); ?><?php endif; ?>
                                                <?php if ( ! empty( $summary['tags_count'] ) ): ?> 🏷️ <?php echo esc_html( $summary['tags_count'] ); ?> tags<?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php elseif ( $item->error_message ): ?>
                                        <small class="arxiu-error-msg">⚠️ <?php echo esc_html( wp_trim_words( $item->error_message, 10 ) ); ?></small>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
.arxiu-queue-dashboard { max-width: 1200px; }
.arxiu-queue-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
.arxiu-stat-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; text-align: center; border-left: 4px solid #ccc; }
.arxiu-stat-pending { border-left-color: #f0ad4e; }
.arxiu-stat-processing { border-left-color: #5bc0de; }
.arxiu-stat-completed { border-left-color: #5cb85c; }
.arxiu-stat-failed { border-left-color: #d9534f; }
.arxiu-stat-number { display: block; font-size: 2.5em; font-weight: bold; line-height: 1; }
.arxiu-stat-label { display: block; margin-top: 5px; color: #666; }
.arxiu-quota-box, .arxiu-queue-actions, .arxiu-queue-items { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
.arxiu-quota-box h3, .arxiu-queue-actions h3, .arxiu-queue-items h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; }
.arxiu-quota-bar { background: #e9e9e9; height: 25px; border-radius: 12px; overflow: hidden; margin: 10px 0; }
.arxiu-quota-fill { background: linear-gradient(90deg, #5cb85c, #8cc63f); height: 100%; transition: width 0.3s ease; }
.arxiu-quota-remaining { color: #666; }
.arxiu-paused-notice { background: #fff3cd; border: 1px solid #ffc107; padding: 10px; border-radius: 4px; margin-top: 10px; }
.arxiu-next-run { color: #666; font-size: 0.9em; }
.arxiu-action-form { margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
.arxiu-action-row { display: flex; align-items: center; gap: 10px; }
.arxiu-action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
.arxiu-status-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.85em; }
.arxiu-status-pending { background: #fff3cd; }
.arxiu-status-processing { background: #d1ecf1; }
.arxiu-status-completed { background: #d4edda; }
.arxiu-status-failed { background: #f8d7da; }
.arxiu-status-skipped { background: #e2e3e5; }
.arxiu-error-msg { color: #856404; }
.arxiu-empty-queue { text-align: center; padding: 40px; color: #666; }
@media (max-width: 782px) { .arxiu-queue-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
