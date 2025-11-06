<?php
/**
 * Auto Rewrite Dashboard page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-auto-rewrite">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Credits Overview -->
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

    <!-- Recent Rewrite Activity -->
    <?php if (!empty($rewrite_stats['recent_rewrites'])): ?>
    <div class="rakubun-recent-activity">
        <h2>📋 最近のリライト履歴</h2>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>記事タイトル</th>
                    <th>実行日時</th>
                    <th>文字数変化</th>
                    <th>SEO改善</th>
                    <th>状態</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rewrite_stats['recent_rewrites'] as $rewrite): ?>
                <tr>
                    <td>
                        <a href="<?php echo get_edit_post_link($rewrite->post_id); ?>" target="_blank">
                            <?php echo esc_html($rewrite->post_title); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html(date('Y/m/d H:i', strtotime($rewrite->rewrite_date))); ?></td>
                    <td>
                        <span class="character-change <?php echo $rewrite->character_change >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $rewrite->character_change >= 0 ? '+' : ''; ?><?php echo number_format($rewrite->character_change); ?>文字
                        </span>
                    </td>
                    <td>
                        <span class="seo-improvements">
                            <?php echo esc_html($rewrite->seo_improvements); ?>項目改善
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-<?php echo esc_attr($rewrite->status); ?>">
                            <?php echo $rewrite->status === 'completed' ? '完了' : '処理中'; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
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
    color: rgba(255,255,255,1);
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

<?php
// Handle form submission
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
    
    echo '<div class="notice notice-success"><p>自動リライト設定を保存しました。</p></div>';
}
?>