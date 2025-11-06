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

        <div class="credits-box">
            <div class="credits-icon">🔄</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['rewrite_credits'] ?? 0); ?></h2>
                <p>リライトクレジット残高</p>
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
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-auto-rewrite'); ?>" class="button button-primary button-large">
                🔄 自動リライト
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
        <h2>🚀 Rakubun AI で、コンテンツ制作を加速させましょう</h2>
        <p>最新のAI技術（GPT-4とDALL-E 3）を活用して、プロフェッショナルな記事と高品質な画像を効率的に生成できます。あなたの創造性を最大限に活かし、コンテンツ制作の時間を大幅短縮できます。</p>
        
        <h3>✨ あなたの無料チケット</h3>
        <p>今すぐ始められます！以下の無料クレジットでお試しください：</p>
        <ul>
            <li><strong>📝 記事生成：5回分</strong> - 複雑に構成されたプロフェッショナルな記事を生成</li>
            <li><strong>🖼️ 画像生成：10回分</strong> - 3つのサイズから選べる独自の高品質画像</li>
            <li><strong>🔄 リライト：3回分</strong> - 既存の投稿をAIで自動改善・最適化</li>
        </ul>
        
        <h3>🎯 さっそく始めてみましょう</h3>
        <ol>
            <li><strong>📝 記事を作成：</strong>「<a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-article'); ?>">記事を生成</a>」で、ブログ記事や商品説明を一瞬で作成</li>
            <li><strong>🎨 画像を生成：</strong>「<a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-image'); ?>">画像を生成</a>」で、アイキャッチ画像やサムネイルを自動作成</li>
            <li><strong>🔄 投稿を改善：</strong>「<a href="<?php echo admin_url('admin.php?page=rakubun-ai-auto-rewrite'); ?>">自動リライト</a>」で、既存コンテンツをSEO最適化</li>
            <li><strong>💳 もっと使う：</strong>無料クレジットを使い切ったら、「<a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>">クレジット購入</a>」で追加購入</li>
        </ol>
        
        <h3>💡 こんなことができます</h3>
        <ul>
            <li>✅ ブログ記事、商品説明、ハウツーガイドを数秒で生成</li>
            <li>✅ 自動下書き作成で、編集・公開の準備もスムーズ</li>
            <li>✅ WordPressメディアライブラリに画像を直接保存</li>
            <li>✅ スケジュール設定で定期的な自動リライト</li>
            <li>✅ セキュアな支払い処理（Stripe対応）</li>
        </ul>
    </div>
</div>

<?php
// Temporary debug information - remove this after fixing the issue
if (defined('WP_DEBUG') && WP_DEBUG) {
    global $wpdb;
    $debug_user_id = get_current_user_id();
    $debug_table_name = $wpdb->prefix . 'rakubun_user_credits';
    
    echo "<div style='background: #f0f0f0; padding: 20px; margin: 20px 0; border: 1px solid #ccc;'>";
    echo "<h3>🐛 Debug Information (will be removed)</h3>";
    echo "<p><strong>User ID:</strong> " . $debug_user_id . "</p>";
    
    // Check user record
    $debug_user_record = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $debug_table_name WHERE user_id = %d", 
        $debug_user_id
    ));
    
    if ($debug_user_record) {
        echo "<p><strong>Database Record:</strong></p>";
        echo "<ul>";
        echo "<li>Article Credits: " . $debug_user_record->article_credits . "</li>";
        echo "<li>Image Credits: " . $debug_user_record->image_credits . "</li>";
        echo "<li>Rewrite Credits: " . ($debug_user_record->rewrite_credits ?? 'Column missing') . "</li>";
        echo "<li>Created: " . $debug_user_record->created_at . "</li>";
        echo "<li>Updated: " . $debug_user_record->updated_at . "</li>";
        echo "</ul>";
    } else {
        echo "<p><strong>No database record found for this user</strong></p>";
    }
    
    // Show method result
    $debug_credits = Rakubun_AI_Credits_Manager::get_user_credits($debug_user_id);
    echo "<p><strong>get_user_credits() returns:</strong></p>";
    echo "<ul>";
    echo "<li>Article Credits: " . $debug_credits['article_credits'] . "</li>";
    echo "<li>Image Credits: " . $debug_credits['image_credits'] . "</li>";
    echo "<li>Rewrite Credits: " . $debug_credits['rewrite_credits'] . "</li>";
    echo "</ul>";
    echo "</div>";
}
?>

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
