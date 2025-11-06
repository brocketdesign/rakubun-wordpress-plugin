<?php
/**
 * Auto Rewrite Dashboard page template
 */
if (!defined('WPINC')) {
    die;
}

// Handle form submission BEFORE any output is rendered
if (isset($_POST['save_rewrite_schedule']) && check_admin_referer('rakubun_ai_schedule_rewrite', 'rakubun_ai_schedule_nonce')) {
    $schedule_data = array(
        'enabled' => !empty($_POST['rewrite_enabled']),
        'frequency' => sanitize_text_field($_POST['rewrite_frequency']),
        'articles_per_batch' => intval($_POST['articles_per_batch']),
        'target_post_age' => intval($_POST['target_post_age']),
        'generate_tags_enabled' => !empty($_POST['generate_tags_enabled']),
        'max_tags_per_article' => intval($_POST['max_tags_per_article'])
    );
    
    update_option('rakubun_ai_rewrite_schedule', $schedule_data);
    
    // Setup or clear WordPress cron job
    if ($schedule_data['enabled']) {
        if (!wp_next_scheduled('rakubun_ai_auto_rewrite')) {
            $frequency_map = array(
                'daily' => 'daily',
                'weekly' => 'weekly', 
                'monthly' => 'monthly'
            );
            wp_schedule_event(time(), $frequency_map[$schedule_data['frequency']], 'rakubun_ai_auto_rewrite');
        }
    } else {
        wp_clear_scheduled_hook('rakubun_ai_auto_rewrite');
    }
    
    // Reload the schedule data so the form shows updated values
    $rewrite_schedule = get_option('rakubun_ai_rewrite_schedule', array());
    
    // Show success message
    $settings_saved = true;
} else {
    $settings_saved = false;
}
?>

<div class="wrap rakubun-ai-auto-rewrite">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if ($settings_saved): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e('自動リライト設定を保存しました。', 'rakubun-ai-content-generator'); ?></p>
    </div>
    <?php endif; ?>
    <div class="rakubun-credits-overview">
        <div class="credits-box">
            <div class="credits-icon">📝</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['article_credits']); ?></h2>
                <p>記事生成クレジット残高</p>
            </div>
        </div>
        
        <div class="credits-box rewrite-credits">
            <div class="credits-icon">🔄</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['rewrite_credits'] ?? 0); ?></h2>
                <p>リライトクレジット残高</p>
            </div>
        </div>
    </div>

    <!-- Article Statistics Section -->
    <div class="rakubun-article-stats-section">
        <h2>📊 記事統計</h2>
        
        <div class="stats-cards">
            <div class="stats-card">
                <div class="card-icon">📄</div>
                <div class="card-content">
                    <h3><?php echo esc_html($total_posts); ?></h3>
                    <p>サイト全体の記事数</p>
                    <span class="stat-detail">公開済み記事</span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="card-icon">🔄</div>
                <div class="card-content">
                    <h3><?php echo esc_html($rewrite_stats['total_rewrites'] ?? 0); ?></h3>
                    <p>リライト済み記事数</p>
                    <span class="stat-detail">累計リライト実行数</span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="card-icon">📈</div>
                <div class="card-content">
                    <h3>+<?php echo number_format($rewrite_stats['characters_added'] ?? 0); ?></h3>
                    <p>追加文字数</p>
                    <span class="stat-detail">リライトによる文字数増加</span>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="card-icon">⚡</div>
                <div class="card-content">
                    <h3><?php echo esc_html($rewrite_stats['seo_improvements'] ?? 0); ?></h3>
                    <p>SEO改善項目</p>
                    <span class="stat-detail">メタディスクリプション、見出し最適化等</span>
                </div>
            </div>
        </div>
    </div>

    <!-- SEO Benefits Explanation Section -->
    <div class="rakubun-seo-benefits-section">
        <h2>🚀 AIリライトのSEO効果</h2>
        
        <div class="seo-benefits-content">
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">🎯</div>
                    <h3>キーワード最適化</h3>
                    <p>AIが最新のSEOトレンドに基づいて、自然な文脈でキーワードを追加・調整します。</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">📝</div>
                    <h3>コンテンツ品質向上</h3>
                    <p>文章の構造を改善し、読みやすさとユーザーエクスペリエンスを向上させます。</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🔍</div>
                    <h3>メタ情報最適化</h3>
                    <p>タイトル、メタディスクリプション、見出しタグを検索エンジン向けに最適化します。</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">📊</div>
                    <h3>定期的なフレッシュ化</h3>
                    <p>Googleが重視するコンテンツの新鮮さを保ち、検索順位の維持・向上を図ります。</p>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">🏷️</div>
                    <h3>スマートタグ生成</h3>
                    <p>記事内容に基づいて関連性の高いタグを自動生成。SEO効果を高め、記事の分類・検索性を向上させます。</p>
                </div>
            </div>
            
            <div class="cta-section">
                <p><strong>100記事以上のサイト向け特別パッケージ</strong>をご用意しています！</p>
                <a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>" class="button button-primary button-large">
                    💎 リライトパッケージを見る
                </a>
            </div>
        </div>
    </div>

    <!-- Auto Rewrite Schedule Section -->
    <div class="rakubun-schedule-section">
        <h2>⏰ 自動リライト設定</h2>
        
        <div class="schedule-form-container">
            <form method="post" action="" id="auto-rewrite-schedule-form">
                <?php wp_nonce_field('rakubun_ai_schedule_rewrite', 'rakubun_ai_schedule_nonce'); ?>
                
                <div class="form-cards">
                    <div class="form-card">
                        <div class="card-header">
                            <h3>基本設定</h3>
                        </div>
                        <div class="card-content">
                            <div class="form-field">
                                <label class="toggle-label">
                                    <input type="checkbox" id="rewrite_enabled" name="rewrite_enabled" value="1" <?php checked(!empty($rewrite_schedule['enabled'])); ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-text">自動リライト機能を有効にする</span>
                                </label>
                                <p class="field-description">この機能を有効にすると、設定したスケジュールで記事の自動リライトが実行されます。</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="card-header">
                            <h3>スケジュール設定</h3>
                        </div>
                        <div class="card-content">
                            <div class="form-field">
                                <label for="rewrite_frequency">実行頻度</label>
                                <select id="rewrite_frequency" name="rewrite_frequency" class="form-select">
                                    <option value="daily" <?php selected($rewrite_schedule['frequency'] ?? '', 'daily'); ?>>毎日</option>
                                    <option value="weekly" <?php selected($rewrite_schedule['frequency'] ?? '', 'weekly'); ?>>毎週</option>
                                    <option value="monthly" <?php selected($rewrite_schedule['frequency'] ?? '', 'monthly'); ?>>毎月</option>
                                </select>
                                <p class="field-description">どの頻度で自動リライトを実行するかを選択してください。</p>
                            </div>

                            <div class="form-field">
                                <label for="articles_per_batch">1回あたりの記事数</label>
                                <input type="number" id="articles_per_batch" name="articles_per_batch" value="<?php echo esc_attr($rewrite_schedule['articles_per_batch'] ?? 5); ?>" min="1" max="50" class="form-input">
                                <p class="field-description">1回の実行で処理する記事数を設定してください（1-50記事）。</p>
                            </div>

                            <div class="form-field">
                                <label for="target_post_age">対象記事の最小経過期間</label>
                                <div class="input-group">
                                    <input type="number" id="target_post_age" name="target_post_age" value="<?php echo esc_attr($rewrite_schedule['target_post_age'] ?? 6); ?>" min="1" max="60" class="form-input">
                                    <span class="input-suffix">ヶ月以上前の記事</span>
                                </div>
                                <p class="field-description">リライト対象とする記事の最小経過期間を設定してください。</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="card-header">
                            <h3>タグ生成設定</h3>
                        </div>
                        <div class="card-content">
                            <div class="form-field">
                                <label class="toggle-label">
                                    <input type="checkbox" id="generate_tags_enabled" name="generate_tags_enabled" value="1" <?php checked(!empty($rewrite_schedule['generate_tags_enabled'])); ?>>
                                    <span class="toggle-switch"></span>
                                    <span class="toggle-text">記事タグも自動生成する</span>
                                </label>
                                <p class="field-description">この機能を有効にすると、リライト時に記事に関連するタグも自動で生成します。</p>
                            </div>

                            <div class="form-field" id="tag-generation-options" style="<?php echo empty($rewrite_schedule['generate_tags_enabled']) ? 'display: none;' : ''; ?>">
                                <label for="max_tags_per_article">1記事あたりの最大タグ数</label>
                                <input type="number" id="max_tags_per_article" name="max_tags_per_article" value="<?php echo esc_attr($rewrite_schedule['max_tags_per_article'] ?? 3); ?>" min="1" max="5" class="form-input">
                                <p class="field-description">1記事につき生成するタグの最大数を設定してください（1-5個）。各タグにはタイトルと説明が含まれます。</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_rewrite_schedule" class="button button-primary button-large">設定を保存</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Credit Warning Section -->
    <?php 
        $next_batch_count = intval($rewrite_schedule['articles_per_batch'] ?? 5);
        $has_sufficient_credits = $credits['rewrite_credits'] >= $next_batch_count;
    ?>
    <?php if ($rewrite_schedule['enabled'] && !$has_sufficient_credits): ?>
    <div class="rakubun-credit-warning">
        <div class="warning-icon">⚠️</div>
        <div class="warning-content">
            <h3>クレジット不足</h3>
            <p>
                次の自動リライト実行までに <strong><?php echo esc_html($next_batch_count - $credits['rewrite_credits']); ?> 個</strong>のクレジットが不足しています。<br>
                自動リライトが予定通り実行されるために、リライトクレジットを購入してください。
            </p>
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>" class="button button-primary">
                💎 クレジットを購入する
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scheduled Rewrite Queue -->
    <?php 
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-auto-rewriter.php';
        $next_scheduled_posts = Rakubun_AI_Auto_Rewriter::get_next_scheduled_posts(10);
    ?>
    <div class="rakubun-scheduled-queue">
        <h2>📅 次回実行予定の記事</h2>
        
        <?php if ($rewrite_schedule['enabled']): ?>
            <div class="queue-info">
                <p>
                    <strong>設定:</strong> 
                    <?php 
                        $frequency_label = array(
                            'daily' => '毎日',
                            'weekly' => '毎週',
                            'monthly' => '毎月'
                        );
                        echo esc_html($frequency_label[$rewrite_schedule['frequency']] ?? '不明');
                    ?> / 
                    1回あたり <strong><?php echo esc_html($next_batch_count); ?></strong>記事 / 
                    最小経過期間 <strong><?php echo esc_html(intval($rewrite_schedule['target_post_age'] ?? 6)); ?></strong>ヶ月
                </p>
            </div>

            <?php if (!empty($next_scheduled_posts)): ?>
                <div class="queue-table-container">
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th>優先順</th>
                                <th>記事タイトル</th>
                                <th>最終更新日</th>
                                <th>経過期間</th>
                                <th>文字数</th>
                                <th>アクション</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $batch_count = min($next_batch_count, count($next_scheduled_posts));
                            foreach ($next_scheduled_posts as $index => $post): 
                                $post_modified = new DateTime($post->post_modified);
                                $now = new DateTime();
                                $interval = $now->diff($post_modified);
                                $days_old = $interval->days;
                                $is_in_batch = $index < $batch_count;
                                $priority_class = $is_in_batch ? 'in-batch' : 'queued';
                            ?>
                            <tr class="queue-row <?php echo esc_attr($priority_class); ?>">
                                <td class="priority-cell">
                                    <?php if ($is_in_batch): ?>
                                        <span class="priority-badge next">次回</span>
                                    <?php else: ?>
                                        <span class="priority-badge queued"><?php echo esc_html($index + 1); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" target="_blank">
                                        <?php echo esc_html(substr($post->post_title, 0, 60)); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(date('Y/m/d', strtotime($post->post_modified))); ?></td>
                                <td>
                                    <span class="days-old">
                                        <?php 
                                        if ($days_old >= 365) {
                                            echo floor($days_old / 365) . '年' . floor(($days_old % 365) / 30) . 'ヶ月前';
                                        } elseif ($days_old >= 30) {
                                            echo floor($days_old / 30) . 'ヶ月' . ($days_old % 30) . '日前';
                                        } else {
                                            echo esc_html($days_old) . '日前';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(strlen($post->post_content)); ?> 文字</td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small" target="_blank">
                                        編集
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="queue-summary">
                    <p>
                        次回の実行では、上記の<strong><?php echo esc_html($batch_count); ?></strong>件の記事がリライト対象となります。
                        <?php if (count($next_scheduled_posts) > $batch_count): ?>
                            その後、<strong><?php echo esc_html(count($next_scheduled_posts) - $batch_count); ?></strong>件の記事が順番待ちしています。
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="no-queue-message">
                    <p>⚠️ 現在、リライト対象の記事がありません。</p>
                    <p>以下の理由が考えられます:</p>
                    <ul>
                        <li>記事の最小経過期間設定が長すぎる可能性があります</li>
                        <li>すべての対象記事が既にリライト済みの可能性があります</li>
                        <li>公開済み記事が十分にない可能性があります</li>
                    </ul>
                    <p>設定を見直すか、より多くの記事を追加してください。</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="disabled-message">
                <p>自動リライト機能が無効に設定されています。</p>
                <p>上記の「基本設定」セクションで機能を有効にしてください。</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rakubun-ai-auto-rewrite .rakubun-credits-overview {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
}

.rakubun-ai-auto-rewrite .credits-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    flex: 1;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rakubun-ai-auto-rewrite .credits-box.rewrite-credits {
    border: none;
}

.rakubun-ai-auto-rewrite .credits-icon {
    font-size: 48px;
    margin-right: 20px;
}

.rakubun-ai-auto-rewrite .credits-info h2 {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .credits-info p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.rakubun-ai-auto-rewrite .credits-box.rewrite-credits .credits-info p {
    font-weight: 500;
}

.rakubun-ai-auto-rewrite .stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rakubun-ai-auto-rewrite .stats-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rakubun-ai-auto-rewrite .card-icon {
    font-size: 36px;
    margin-right: 20px;
    min-width: 50px;
    text-align: center;
}

.rakubun-ai-auto-rewrite .card-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.rakubun-ai-auto-rewrite .card-content p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}

.rakubun-ai-auto-rewrite .stat-detail {
    font-size: 12px;
    color: #999;
}

/* Credit Warning Styling */
.rakubun-ai-auto-rewrite .rakubun-credit-warning {
    background: #fff8f0;
    border-left: 4px solid #ff9800;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0 30px 0;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(255, 152, 0, 0.1);
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning .warning-icon {
    font-size: 36px;
    flex-shrink: 0;
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning .warning-content {
    flex: 1;
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning h3 {
    margin: 0 0 10px 0;
    color: #ff6f00;
    font-size: 18px;
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning p {
    margin: 0 0 15px 0;
    color: #666;
    line-height: 1.5;
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning strong {
    color: #d32f2f;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .rakubun-credit-warning .button {
    margin-top: 10px;
}

/* Schedule Section Styling */
.rakubun-schedule-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 0;
    margin: 40px 0 30px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rakubun-schedule-section h2 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    margin: 0;
    padding: 20px 30px;
    border-radius: 8px 8px 0 0;
    font-size: 18px;
}

.schedule-form-container {
    padding: 30px;
}

.form-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.form-card {
    background: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    overflow: hidden;
}

.card-header {
    background: #667eea;
    color: white;
    padding: 15px 20px;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.card-content {
    padding: 20px;
}

.form-field {
    margin-bottom: 20px;
}

.form-field:last-child {
    margin-bottom: 0;
}

.form-field label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #333;
}

.toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-weight: 600;
    margin-bottom: 0 !important;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    background: #ccc;
    border-radius: 17px;
    margin-right: 15px;
    transition: background 0.3s ease;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    flex-shrink: 0;
}

.toggle-switch::before {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 28px;
    height: 28px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.rakubun-ai-auto-rewrite input[type="checkbox"]:checked + .toggle-switch {
    background: #667eea;
}

.rakubun-ai-auto-rewrite input[type="checkbox"]:checked + .toggle-switch::before {
    transform: translateX(26px);
}

.rakubun-ai-auto-rewrite input[type="checkbox"] {
    display: none;
}

.toggle-text {
    color: #333;
    font-size: 14px;
    line-height: 1.4;
}

.form-select,
.form-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e5e5;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.3s;
    background: white;
}

.form-select:focus,
.form-input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.input-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

.input-group .form-input {
    flex: 0 0 120px;
}

.input-suffix {
    color: #666;
    font-size: 14px;
}

.field-description {
    margin: 8px 0 0 0;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.form-actions {
    text-align: center;
    padding: 20px 0;
    border-top: 1px solid #e5e5e5;
    margin-top: 20px;
}

.form-actions .button {
    padding: 12px 30px;
    font-size: 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.form-actions .button-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.form-actions .button-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.rakubun-ai-auto-rewrite .benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.rakubun-ai-auto-rewrite .benefit-item {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.rakubun-ai-auto-rewrite .benefit-icon {
    font-size: 48px;
    margin-bottom: 15px;
    line-height: 1;
    display: block;
}

.rakubun-ai-auto-rewrite .benefit-item h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #333;
}

.rakubun-ai-auto-rewrite .benefit-item p {
    margin: 0;
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

.rakubun-ai-auto-rewrite .cta-section {
    background: #f0f8ff;
    border: 2px solid #007cba;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.rakubun-ai-auto-rewrite .cta-section p {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: #333;
}

/* Scheduled Queue Styling */
.rakubun-ai-auto-rewrite .rakubun-scheduled-queue {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin: 30px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rakubun-ai-auto-rewrite .rakubun-scheduled-queue h2 {
    margin-top: 0;
    color: #333;
    font-size: 20px;
    margin-bottom: 20px;
}

.rakubun-ai-auto-rewrite .queue-info {
    background: #f0f8ff;
    border-left: 3px solid #0073aa;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.rakubun-ai-auto-rewrite .queue-info p {
    margin: 0;
    font-size: 14px;
    color: #333;
    line-height: 1.6;
}

.rakubun-ai-auto-rewrite .queue-table-container {
    overflow-x: auto;
    margin-bottom: 20px;
}

.rakubun-ai-auto-rewrite .rakubun-scheduled-queue .widefat {
    margin-bottom: 0;
}

.rakubun-ai-auto-rewrite .queue-row {
    transition: background-color 0.2s;
}

.rakubun-ai-auto-rewrite .queue-row.in-batch {
    background-color: #e8f5e9;
}

.rakubun-ai-auto-rewrite .queue-row.queued:hover {
    background-color: #f5f5f5;
}

.rakubun-ai-auto-rewrite .priority-cell {
    text-align: center;
    font-weight: 600;
}

.rakubun-ai-auto-rewrite .priority-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .priority-badge.next {
    background: #4caf50;
    color: white;
}

.rakubun-ai-auto-rewrite .priority-badge.queued {
    background: #e0e0e0;
    color: #333;
}

.rakubun-ai-auto-rewrite .days-old {
    background: #f5f5f5;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.rakubun-ai-auto-rewrite .queue-summary {
    background: #fafafa;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px;
    margin-top: 20px;
}

.rakubun-ai-auto-rewrite .queue-summary p {
    margin: 0;
    font-size: 14px;
    color: #555;
    line-height: 1.6;
}

.rakubun-ai-auto-rewrite .no-queue-message {
    background: #fff3e0;
    border: 1px solid #ffe0b2;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    color: #e65100;
}

.rakubun-ai-auto-rewrite .no-queue-message p {
    margin: 10px 0;
    font-size: 14px;
}

.rakubun-ai-auto-rewrite .no-queue-message ul {
    text-align: left;
    display: inline-block;
    margin: 10px 0;
}

.rakubun-ai-auto-rewrite .no-queue-message li {
    margin: 5px 0;
}

.rakubun-ai-auto-rewrite .disabled-message {
    background: #f3e5f5;
    border: 1px solid #e1bee7;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    color: #6a1b9a;
}

.rakubun-ai-auto-rewrite .disabled-message p {
    margin: 10px 0;
    font-size: 14px;
}

/* Recent Activity Styling */
.rakubun-ai-auto-rewrite .rakubun-recent-activity {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 30px;
    margin: 30px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.rakubun-ai-auto-rewrite .rakubun-recent-activity h2 {
    margin-top: 0;
    color: #333;
    font-size: 20px;
    margin-bottom: 20px;
}

.rakubun-ai-auto-rewrite .character-change.positive {
    color: #46b450;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .character-change.negative {
    color: #dc3232;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .seo-improvements {
    color: #0073aa;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.rakubun-ai-auto-rewrite .status-completed {
    background: #d4edda;
    color: #155724;
}

.rakubun-ai-auto-rewrite .status-processing {
    background: #fff3cd;
    color: #856404;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const generateTagsToggle = document.getElementById('generate_tags_enabled');
    const tagOptions = document.getElementById('tag-generation-options');
    
    if (generateTagsToggle && tagOptions) {
        generateTagsToggle.addEventListener('change', function() {
            if (this.checked) {
                tagOptions.style.display = 'block';
            } else {
                tagOptions.style.display = 'none';
            }
        });
    }
});
</script>