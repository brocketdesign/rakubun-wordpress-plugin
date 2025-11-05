<?php
/**
 * Dashboard page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-dashboard">
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
        
        <div class="credits-box">
            <div class="credits-icon">🖼️</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['image_credits']); ?></h2>
                <p>画像生成クレジット残高</p>
            </div>
        </div>
    </div>

    <div class="rakubun-quick-actions">
        <h2>クイックアクション</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-article'); ?>" class="button button-primary button-large">
                📝 記事を生成
            </a>
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-image'); ?>" class="button button-primary button-large">
                🎨 画像を生成
            </a>
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>" class="button button-secondary button-large">
                💳 クレジット購入
            </a>
        </div>
    </div>

    <!-- Analytics Section -->
    <div class="rakubun-analytics-section">
        <h2>📊 使用状況・分析</h2>
        
        <div class="analytics-cards">
            <div class="analytics-card">
                <div class="card-icon">📈</div>
                <div class="card-content">
                    <h3><?php echo esc_html($analytics['total_articles']); ?></h3>
                    <p>合計記事生成数</p>
                    <span class="recent-activity">過去7日間: <?php echo esc_html($analytics['recent_articles']); ?>件</span>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">🎨</div>
                <div class="card-content">
                    <h3><?php echo esc_html($analytics['total_images']); ?></h3>
                    <p>合計画像生成数</p>
                    <span class="recent-activity">過去7日間: <?php echo esc_html($analytics['recent_images']); ?>件</span>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">💰</div>
                <div class="card-content">
                    <h3>¥<?php echo number_format($analytics['total_spent'] ?: 0); ?></h3>
                    <p>合計支払い額</p>
                    <span class="recent-activity">クレジット購入による</span>
                </div>
            </div>
            
            <div class="analytics-card">
                <div class="card-icon">⚡</div>
                <div class="card-content">
                    <h3><?php echo esc_html($analytics['recent_articles'] + $analytics['recent_images']); ?></h3>
                    <p>今週の活動</p>
                    <span class="recent-activity">過去7日間の合計生成数</span>
                </div>
            </div>
        </div>

        <!-- Monthly Usage Chart -->
        <?php if (!empty($analytics['monthly_usage'])): ?>
        <div class="usage-chart">
            <h3>月別使用状況</h3>
            <div class="chart-container">
                <?php foreach (array_reverse($analytics['monthly_usage']) as $month_data): ?>
                    <div class="chart-bar">
                        <div class="bar-group">
                            <div class="bar articles" style="height: <?php echo min(100, $month_data->articles * 10); ?>px;" title="記事: <?php echo esc_attr($month_data->articles); ?>"></div>
                            <div class="bar images" style="height: <?php echo min(100, $month_data->images * 5); ?>px;" title="画像: <?php echo esc_attr($month_data->images); ?>"></div>
                        </div>
                        <span class="month-label"><?php echo esc_html(date('m月', strtotime($month_data->month . '-01'))); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-color articles"></span>記事</span>
                <span class="legend-item"><span class="legend-color images"></span>画像</span>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Activity -->
    <?php if (!empty($recent_content)): ?>
    <div class="rakubun-recent-activity">
        <h2>🕒 最近の生成履歴</h2>
        <div class="activity-list">
            <?php foreach ($recent_content as $item): ?>
                <div class="activity-item <?php echo esc_attr($item->content_type); ?>">
                    <div class="activity-icon">
                        <?php echo $item->content_type === 'article' ? '📝' : '🖼️'; ?>
                    </div>
                    <div class="activity-content">
                        <h4><?php echo esc_html(wp_trim_words($item->prompt, 10)); ?></h4>
                        <p><?php echo esc_html(date('Y年m月d日 H:i', strtotime($item->created_at))); ?></p>
                    </div>
                    <?php if ($item->content_type === 'article' && $item->post_id): ?>
                        <div class="activity-actions">
                            <a href="<?php echo esc_url(get_edit_post_link($item->post_id)); ?>" class="button button-small">編集</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- AI Generated Images Gallery -->
    <?php if (!empty($user_images)): ?>
    <div class="rakubun-image-gallery">
        <h2>🖼️ AI生成画像ギャラリー</h2>
        <div class="gallery-grid">
            <?php foreach ($user_images as $image): ?>
                <div class="gallery-item" data-image-id="<?php echo esc_attr($image->id); ?>">
                    <div class="image-container">
                        <img src="<?php echo esc_url($image->image_url); ?>" alt="<?php echo esc_attr($image->prompt); ?>" loading="lazy">
                        <div class="image-overlay">
                            <div class="overlay-actions">
                                <button class="btn-regenerate" data-prompt="<?php echo esc_attr($image->prompt); ?>" title="再生成">
                                    🔄
                                </button>
                                <button class="btn-view-full" data-url="<?php echo esc_url($image->image_url); ?>" title="拡大表示">
                                    🔍
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="image-info">
                        <p class="image-prompt"><?php echo esc_html(wp_trim_words($image->prompt, 8)); ?></p>
                        <span class="image-date"><?php echo esc_html(date('m/d H:i', strtotime($image->created_at))); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Regeneration Modal -->
    <div id="regenerationModal" class="regeneration-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>🔄 画像を再生成</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="regenerationForm">
                    <div class="form-group">
                        <label for="regenerate-prompt">プロンプト:</label>
                        <textarea id="regenerate-prompt" name="prompt" rows="3" placeholder="画像の説明を入力してください..."></textarea>
                    </div>
                    <div class="form-group">
                        <label for="regenerate-size">サイズ:</label>
                        <select id="regenerate-size" name="size">
                            <option value="1024x1024">正方形 (1024x1024)</option>
                            <option value="1024x1792">縦長 (1024x1792)</option>
                            <option value="1792x1024">横長 (1792x1024)</option>
                        </select>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="button button-secondary modal-close">キャンセル</button>
                        <button type="submit" class="button button-primary">
                            <span class="btn-text">再生成する</span>
                            <span class="btn-loading" style="display: none;">生成中...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Viewer Modal -->
    <div id="imageViewerModal" class="image-viewer-modal" style="display: none;">
        <div class="viewer-content">
            <button class="viewer-close">&times;</button>
            <img id="viewerImage" src="" alt="">
            
            <!-- Instructions overlay -->
            <div class="viewer-instructions">
                <div class="instruction-text">
                    <p>💡 クリックでズーム | Escキーで閉じる</p>
                </div>
            </div>
        </div>
    </div>

    <div class="rakubun-info-section">
        <h2>Rakubun AI コンテンツジェネレーターについて</h2>
        <p>このプラグインを使用すると、OpenAIのGPT-4とDALL-Eモデルを使用して高品質な記事と画像を生成できます。</p>
        
        <h3>無料クレジット</h3>
        <ul>
            <li>記事生成 3回分の無料クレジット</li>
            <li>画像生成 5回分の無料クレジット</li>
        </ul>
        
        <h3>はじめ方</h3>
        <ol>
            <li>管理者の方：<a href="<?php echo admin_url('admin.php?page=rakubun-ai-settings'); ?>">設定</a>でOpenAI APIキーとStripeキーを設定してください</li>
            <li>無料クレジットを使ってコンテンツを生成してみましょう</li>
            <li>必要に応じて追加クレジットをご購入ください</li>
        </ol>
    </div>
</div>

<!-- Debug Info (remove in production) -->
<script>
console.log('🚀 Rakubun AI Dashboard Loaded');
console.log('📄 CSS Version:', '<?php echo RAKUBUN_AI_VERSION; ?>.' + Date.now());
console.log('⚡ JS Version:', '<?php echo RAKUBUN_AI_VERSION; ?>.' + Date.now());
console.log('🎯 Gallery Items:', $('.gallery-item').length);
console.log('🔄 Regenerate Buttons:', $('.btn-regenerate').length);
console.log('🔍 View Full Buttons:', $('.btn-view-full').length);

// Force reload analytics section if needed
if ($('.rakubun-analytics-section').length === 0) {
    console.warn('⚠️ Analytics section not found - possible cache issue');
}

// Test click handler
setTimeout(function() {
    if ($('.btn-regenerate').length > 0) {
        console.log('✅ Regenerate buttons found and ready');
    } else {
        console.warn('❌ No regenerate buttons found');
    }
}, 1000);
</script>
