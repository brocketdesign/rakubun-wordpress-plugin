<?php
/**
 * Purchase Credits page template
 */
if (!defined('WPINC')) {
    die;
}

$article_price = get_option('rakubun_ai_article_price', 750);
$image_price = get_option('rakubun_ai_image_price', 300);
$articles_per_purchase = get_option('rakubun_ai_articles_per_purchase', 10);
$images_per_purchase = get_option('rakubun_ai_images_per_purchase', 20);

// Rewrite package pricing
$rewrite_packages = array(
    'starter' => array(
        'name' => 'スターターパック',
        'rewrites' => 50,
        'price' => 3000,
        'per_rewrite' => 60,
        'suitable_for' => '〜50記事のサイト'
    ),
    'standard' => array(
        'name' => 'スタンダードパック',
        'rewrites' => 150,
        'price' => 7500,
        'per_rewrite' => 50,
        'discount' => '17%オフ',
        'suitable_for' => '〜100記事のサイト'
    ),
    'premium' => array(
        'name' => 'プレミアムパック',
        'rewrites' => 300,
        'price' => 12000,
        'per_rewrite' => 40,
        'discount' => '33%オフ',
        'suitable_for' => '100記事以上のサイト',
        'popular' => true
    ),
    'enterprise' => array(
        'name' => 'エンタープライズパック',
        'rewrites' => 500,
        'price' => 17500,
        'per_rewrite' => 35,
        'discount' => '42%オフ',
        'suitable_for' => '大規模サイト・複数サイト運営'
    )
);
?>

<div class="wrap rakubun-ai-purchase">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>現在のクレジット残高 - 記事: <strong class="credits-count-articles"><?php echo esc_html($credits['article_credits']); ?></strong> | 画像: <strong class="credits-count-images"><?php echo esc_html($credits['image_credits']); ?></strong> | リライト: <strong class="credits-count-rewrites"><?php echo esc_html($credits['rewrite_credits'] ?? 0); ?></strong></p>
    </div>

    <!-- Navigation and Explanation Section -->
    <div class="rakubun-pricing-navigation">
        <div class="pricing-explanation">
            <h2>📦 ご利用いただけるクレジットパッケージ</h2>
            <p>目的に応じて3つの異なるクレジットパッケージからお選びいただけます。各セクションでお得なパッケージプランをご用意しています。</p>
        </div>
        
        <div class="pricing-nav-tabs">
            <button class="nav-tab active" onclick="scrollToSection('basic-credits')" data-target="basic-credits">
                ✍️ 記事・画像生成
                <span class="nav-description">新しいコンテンツ作成</span>
            </button>
            <button class="nav-tab" onclick="scrollToSection('rewrite-packages')" data-target="rewrite-packages">
                🔄 リライトパッケージ
                <span class="nav-description">既存記事の改善・最適化</span>
            </button>
        </div>
    </div>

    <div id="basic-credits" class="rakubun-pricing">
        <h2>追加クレジットを購入</h2>
        
        <div class="pricing-cards">
            <div class="pricing-card">
                <h3>記事生成クレジット</h3>
                <div class="price">¥<?php echo number_format($article_price, 0); ?></div>
                <div class="credits-amount"><?php echo $articles_per_purchase; ?>記事分のクレジット</div>
                <ul class="features">
                    <li><?php echo $articles_per_purchase; ?>記事をAI生成</li>
                    <li>GPT-4搭載</li>
                    <li>高品質なコンテンツ</li>
                    <li>下書き投稿を自動作成</li>
                </ul>
                <button class="button button-primary button-large" onclick="rakubunInitiatePayment('articles', <?php echo esc_attr($article_price); ?>)">
                    今すぐ購入
                </button>
            </div>

            <div class="pricing-card">
                <h3>画像生成クレジット</h3>
                <div class="price">¥<?php echo number_format($image_price, 0); ?></div>
                <div class="credits-amount"><?php echo $images_per_purchase; ?>画像分のクレジット</div>
                <ul class="features">
                    <li><?php echo $images_per_purchase; ?>画像をAI生成</li>
                    <li>DALL-E 3搭載</li>
                    <li>高品質な画像</li>
                    <li>複数サイズに対応</li>
                </ul>
                <button class="button button-primary button-large" onclick="rakubunInitiatePayment('images', <?php echo esc_attr($image_price); ?>)">
                    今すぐ購入
                </button>
            </div>
        </div>
    </div>

    <!-- Auto Rewrite Packages Section -->
    <div id="rewrite-packages" class="rakubun-rewrite-packages">
        <h2>🔄 記事リライトパッケージ</h2>
        <p class="package-description">既存の記事をAIが自動的にリライトし、SEO効果を向上させます。大規模サイト向けの特別価格をご用意！</p>
        
        <div class="rewrite-pricing-cards">
            <?php foreach ($rewrite_packages as $package_key => $package): ?>
            <div class="rewrite-pricing-card <?php echo $package['popular'] ?? false ? 'popular' : ''; ?>">
                <?php if ($package['popular'] ?? false): ?>
                <div class="popular-badge">最人気</div>
                <?php endif; ?>
                
                <h3><?php echo esc_html($package['name']); ?></h3>
                <div class="package-price">
                    <span class="main-price">¥<?php echo number_format($package['price']); ?></span>
                    <span class="per-unit">（1リライト ¥<?php echo $package['per_rewrite']; ?>）</span>
                </div>
                
                <?php if (!empty($package['discount'])): ?>
                <div class="discount-badge"><?php echo esc_html($package['discount']); ?></div>
                <?php endif; ?>
                
                <div class="package-credits"><?php echo $package['rewrites']; ?>リライト分のクレジット</div>
                <div class="suitable-for"><?php echo esc_html($package['suitable_for']); ?></div>
                
                <ul class="package-features">
                    <li>✅ 既存記事のAIリライト</li>
                    <li>✅ SEO効果の向上</li>
                    <li>✅ キーワード最適化</li>
                    <li>✅ 構造・読みやすさ改善</li>
                    <li>✅ 自動スケジューリング対応</li>
                    <?php if ($package_key === 'premium' || $package_key === 'enterprise'): ?>
                    <li>✅ 優先サポート</li>
                    <?php endif; ?>
                    <?php if ($package_key === 'enterprise'): ?>
                    <li>✅ カスタマイズ対応</li>
                    <?php endif; ?>
                </ul>
                
                <button class="button button-primary button-large" onclick="rakubunInitiatePayment('rewrite_<?php echo esc_attr($package_key); ?>', <?php echo esc_attr($package['price']); ?>)">
                    今すぐ購入
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="rewrite-benefits">
            <h3>🚀 AIリライトのメリット</h3>
            <div class="benefits-grid">
                <div class="benefit">
                    <div class="benefit-icon">📈</div>
                    <h4>SEO効果向上</h4>
                    <p>検索エンジンに最適化された構造とキーワード配置で検索順位アップ</p>
                </div>
                <div class="benefit">
                    <div class="benefit-icon">⏰</div>
                    <h4>時間効率化</h4>
                    <p>手動での記事更新作業を自動化し、コンテンツ管理の時間を大幅短縮</p>
                </div>
                <div class="benefit">
                    <div class="benefit-icon">🎯</div>
                    <h4>品質向上</h4>
                    <p>AIが最新のライティング技術で文章の読みやすさと価値を向上</p>
                </div>
                <div class="benefit">
                    <div class="benefit-icon">🔄</div>
                    <h4>継続的更新</h4>
                    <p>定期的なリライトで常に新鮮なコンテンツを保ち、検索エンジンに評価される</p>
                </div>
            </div>
        </div>
    </div>

    <div id="rakubun-payment-form" style="display:none;">
        <h2>購入を完了する</h2>
        <div id="rakubun-stripe-card-element"></div>
        <div id="rakubun-card-errors" class="notice notice-error" style="display:none;"></div>
        <button id="rakubun-payment-submit" class="button button-primary">支払いを完了</button>
        <button class="button" onclick="rakubunCancelPayment()">キャンセル</button>
    </div>

    <div id="rakubun-payment-loading" class="rakubun-loading" style="display:none;">
        <div class="spinner is-active"></div>
        <p>決済を処理しています...</p>
    </div>

    <div id="rakubun-payment-error" class="notice notice-error" style="display:none;">
        <p></p>
    </div>

    <div id="rakubun-payment-success" class="notice notice-success" style="display:none;">
        <p>決済が完了しました！クレジットが追加されました。</p>
    </div>
</div>

<style>
/* Rewrite Packages Styling */
.rakubun-rewrite-packages {
    margin: 40px 0;
    background: #f8f9fa;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid #e5e5e5;
}

.rakubun-rewrite-packages h2 {
    text-align: center;
    margin-bottom: 10px;
    color: #333;
}

.package-description {
    text-align: center;
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
}

.rewrite-pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.rewrite-pricing-card {
    background: #fff;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.rewrite-pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.rewrite-pricing-card.popular {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.popular-badge {
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.rewrite-pricing-card h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
    color: #333;
}

.package-price {
    margin-bottom: 15px;
}

.main-price {
    font-size: 32px;
    font-weight: bold;
    color: #333;
}

.per-unit {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.discount-badge {
    background: #ff4757;
    color: white;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
    margin-bottom: 10px;
}

.package-credits {
    font-size: 16px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.suitable-for {
    font-size: 12px;
    color: #999;
    margin-bottom: 20px;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
    text-align: left;
}

.package-features li {
    padding: 5px 0;
    font-size: 14px;
    color: #555;
}

.rewrite-benefits {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    border: 1px solid #e5e5e5;
}

.rewrite-benefits h3 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.benefit {
    text-align: center;
    padding: 20px;
}

.benefit-icon {
    font-size: 48px;
    margin-bottom: 15px;
    line-height: 1;
    display: block;
}

.benefit h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #333;
}

.benefit p {
    margin: 0;
    font-size: 14px;
    color: #666;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .rewrite-pricing-cards {
        grid-template-columns: 1fr;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .pricing-nav-tabs {
        flex-direction: column;
        gap: 10px;
    }
    
    .nav-tab {
        text-align: center;
    }
}

/* Navigation Styling */
.rakubun-pricing-navigation {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    margin: 20px 0 40px 0;
    border-radius: 12px;
    text-align: center;
}

.pricing-explanation h2 {
    margin: 0 0 15px 0;
    color: white;
}

.pricing-explanation p {
    margin: 0 0 25px 0;
    font-size: 16px;
    opacity: 0.9;
}

.pricing-nav-tabs {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.nav-tab {
    background: rgba(255, 255, 255, 0.2);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
    font-size: 16px;
    text-align: left;
    min-width: 200px;
    backdrop-filter: blur(10px);
}

.nav-tab:hover,
.nav-tab.active {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.6);
    transform: translateY(-2px);
}

.nav-description {
    display: block;
    font-size: 12px;
    font-weight: normal;
    opacity: 0.8;
    margin-top: 5px;
}

/* Section spacing improvements */
#basic-credits {
    scroll-margin-top: 100px;
}

#rewrite-packages {
    scroll-margin-top: 100px;
}
</style>

<script>
function scrollToSection(sectionId) {
    // Update active tab
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`[data-target="${sectionId}"]`).classList.add('active');
    
    // Smooth scroll to section
    const section = document.getElementById(sectionId);
    if (section) {
        section.scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Add scroll spy functionality to highlight active section
window.addEventListener('scroll', function() {
    const sections = ['basic-credits', 'rewrite-packages'];
    const scrollPosition = window.scrollY + 150; // Offset for header
    
    sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
            const sectionTop = section.offsetTop;
            const sectionBottom = sectionTop + section.offsetHeight;
            
            if (scrollPosition >= sectionTop && scrollPosition < sectionBottom) {
                document.querySelectorAll('.nav-tab').forEach(tab => {
                    tab.classList.remove('active');
                });
                const activeTab = document.querySelector(`[data-target="${sectionId}"]`);
                if (activeTab) {
                    activeTab.classList.add('active');
                }
            }
        }
    });
});
</script>
