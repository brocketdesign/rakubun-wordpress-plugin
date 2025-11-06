<?php
/**
 * Purchase Credits page template
 */
if (!defined('WPINC')) {
    die;
}

// Initialize external API to get packages
require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
$external_api = new Rakubun_AI_External_API();

// Initialize all package types
$article_packages = array();
$image_packages = array();
$rewrite_packages = array();
$packages_error = null;

// Check if returning from Stripe Checkout
$checkout_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$session_id = isset($_GET['session_id']) ? sanitize_text_field($_GET['session_id']) : '';

$payment_success = false;
$payment_message = '';

if ($checkout_status === 'success' && !empty($session_id)) {
    // Verify the checkout session with the dashboard
    $user_id = get_current_user_id();
    $api_token = get_option('rakubun_ai_api_token');
    $instance_id = get_option('rakubun_ai_instance_id');

    error_log('Rakubun Checkout Verification Started:');
    error_log('  Session ID: ' . $session_id);
    error_log('  User ID: ' . $user_id);
    error_log('  Instance ID: ' . $instance_id);

    if (empty($api_token) || empty($instance_id)) {
        $payment_message = 'プラグインがまだ登録されていません。設定ページから登録してください。';
        error_log('Rakubun: Missing API credentials');
    } else {
        $verify_response = wp_remote_post(
            'https://app.rakubun.com/api/v1/checkout/verify',
            array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type' => 'application/json',
                    'X-Instance-ID' => $instance_id
                ),
                'body' => wp_json_encode(array('session_id' => $session_id)),
                'timeout' => 15,
                'sslverify' => true
            )
        );

        if (is_wp_error($verify_response)) {
            $error_message = $verify_response->get_error_message();
            error_log('Rakubun Checkout Verification Error: ' . $error_message);
            $payment_message = 'ダッシュボード通信エラー: ' . $error_message;
        } else {
            $verify_body = json_decode(wp_remote_retrieve_body($verify_response), true);
            $verify_status = wp_remote_retrieve_response_code($verify_response);

            error_log('Rakubun Checkout Response Status: ' . $verify_status);
            error_log('Rakubun Checkout Response Body: ' . wp_json_encode($verify_body));

            if ($verify_status === 200 && !empty($verify_body['success'])) {
                $payment_success = true;
                $credits_added = intval($verify_body['credits_added'] ?? 0);
                $credit_type_jp = '';
                
                switch ($verify_body['credit_type'] ?? '') {
                    case 'article':
                        $credit_type_jp = '記事';
                        break;
                    case 'image':
                        $credit_type_jp = '画像';
                        break;
                    case 'rewrite':
                        $credit_type_jp = 'リライト';
                        break;
                    default:
                        $credit_type_jp = 'クレジット';
                }

                $payment_message = sprintf(
                    'クレジットが正常に追加されました！ %d個の%sクレジットを購入しました。',
                    $credits_added,
                    $credit_type_jp
                );

                error_log('Rakubun Payment Success: ' . $payment_message);

                // Clear the transient cache to force fresh credit fetch
                error_log('Rakubun: Clearing transient cache for user ' . $user_id);
                delete_transient('rakubun_ai_credits_' . $user_id);
                
                // Immediately fetch fresh credits from API
                error_log('Rakubun: Fetching fresh credits after successful payment');
                require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-credits-manager.php';
                try {
                    $fresh_credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
                    error_log('Rakubun: Fresh credits result: ' . wp_json_encode($fresh_credits));
                    if ($fresh_credits) {
                        $credits = $fresh_credits;
                        error_log('Rakubun: Displaying updated credits - Article: ' . $credits['article_credits'] . ', Image: ' . $credits['image_credits'] . ', Rewrite: ' . ($credits['rewrite_credits'] ?? 0));
                    } else {
                        error_log('Rakubun: Fresh credits returned null or empty');
                    }
                } catch (Exception $credit_error) {
                    error_log('Rakubun: Error fetching fresh credits: ' . $credit_error->getMessage());
                    // Credits already added on external dashboard, just show success anyway
                }

                // Do NOT redirect immediately - let user see the success message and updated credits
                // The URL will be cleaned up by JavaScript after 3 seconds
                $payment_success = true;
            } elseif ($verify_status === 200 && !empty($verify_body['status']) && $verify_body['status'] === 'already_completed') {
                // Session already processed
                $payment_success = true;
                $credits_added = intval($verify_body['credits_added'] ?? 0);
                $payment_message = sprintf(
                    '✓ このセッションはすでに処理されています。%d個のクレジットが追加されました。',
                    $credits_added
                );
                error_log('Rakubun: Session already completed - ' . $session_id);
                error_log('Rakubun: Clearing transient cache for user ' . $user_id);
                delete_transient('rakubun_ai_credits_' . $user_id);
                
                // Immediately fetch fresh credits from API
                error_log('Rakubun: Fetching fresh credits after already-completed payment');
                require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-credits-manager.php';
                try {
                    $fresh_credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
                    error_log('Rakubun: Fresh credits result (already completed): ' . wp_json_encode($fresh_credits));
                    if ($fresh_credits) {
                        $credits = $fresh_credits;
                        error_log('Rakubun: Displaying updated credits - Article: ' . $credits['article_credits'] . ', Image: ' . $credits['image_credits'] . ', Rewrite: ' . ($credits['rewrite_credits'] ?? 0));
                    }
                } catch (Exception $credit_error) {
                    error_log('Rakubun: Error fetching fresh credits (already completed): ' . $credit_error->getMessage());
                }
                
                // Do NOT redirect - let user see the message and updated credits
                $payment_success = true;
            } else {
                $error_msg = $verify_body['message'] ?? 'Unknown error';
                $error_detail = $verify_body['error'] ?? '';
                error_log('Rakubun Checkout Verification Failed: [' . $error_detail . '] ' . $error_msg);
                
                if ($error_detail === 'payment_not_completed') {
                    $payment_message = 'お支払いがまだ完了していません。しばらく待ってから再度アクセスしてください。';
                } elseif ($error_detail === 'session_not_found') {
                    $payment_message = 'セッションが見つかりません。ダッシュボードで確認してください。';
                } else {
                    $payment_message = 'クレジット追加エラー: ' . $error_msg;
                }
            }
        }
    }
} elseif ($checkout_status === 'cancel') {
    $payment_message = 'チェックアウトがキャンセルされました。';
    error_log('Rakubun Checkout Cancelled');
}

// Check if connected to external dashboard
if (!$external_api->is_connected()) {
    $packages_error = 'ダッシュボードに接続されていません。プラグインをダッシュボードに接続してください。';
} else {
    // Get packages from external API
    $cache_key = 'rakubun_ai_packages_cache';
    $cached_packages = get_transient($cache_key);
    
    if ($cached_packages === false) {
        $external_packages = $external_api->get_packages();
        error_log('Rakubun Purchase - API Response: ' . wp_json_encode($external_packages));
        if ($external_packages && is_array($external_packages)) {
            $cached_packages = $external_packages;
            // Cache for 1 hour
            set_transient($cache_key, $cached_packages, HOUR_IN_SECONDS);
            error_log('Rakubun Purchase - Cached Packages: ' . wp_json_encode($cached_packages));
        } else {
            $packages_error = 'ダッシュボードからパッケージを取得できませんでした。しばらく待ってから再度お試しください。';
            error_log('Rakubun Purchase - Empty API Response');
        }
    }
    
    // Map packages from grouped structure (articles, images, rewrites)
    if ($cached_packages && empty($packages_error)) {
        if (isset($cached_packages['articles']) && is_array($cached_packages['articles'])) {
            $article_packages = $cached_packages['articles'];
            error_log('Rakubun Purchase - Articles count: ' . count($article_packages));
        }
        if (isset($cached_packages['images']) && is_array($cached_packages['images'])) {
            $image_packages = $cached_packages['images'];
            error_log('Rakubun Purchase - Images count: ' . count($image_packages));
        }
        if (isset($cached_packages['rewrites']) && is_array($cached_packages['rewrites'])) {
            // Convert rewrite packages to keyed array
            $rewrite_packages_keyed = array();
            foreach ($cached_packages['rewrites'] as $package) {
                $key = isset($package['package_id']) ? $package['package_id'] : 'rewrite_' . sanitize_title($package['name'] ?? 'package');
                $rewrite_packages_keyed[$key] = $package;
            }
            $rewrite_packages = $rewrite_packages_keyed;
            error_log('Rakubun Purchase - Rewrites count: ' . count($rewrite_packages));
        }
        error_log('Rakubun Purchase - Total packages loaded. Articles: ' . count($article_packages) . ', Images: ' . count($image_packages) . ', Rewrites: ' . count($rewrite_packages));
    }
}

// Prepare packages array for rendering
$packages = array(
    'articles' => $article_packages,
    'images' => $image_packages,
    'rewrites' => $rewrite_packages
);

$is_connected = $external_api->is_connected();
?>

<div class="wrap rakubun-ai-purchase">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>現在のクレジット残高 - 記事: <strong class="credits-count-articles"><?php echo esc_html($credits['article_credits']); ?></strong> | 画像: <strong class="credits-count-images"><?php echo esc_html($credits['image_credits']); ?></strong> | リライト: <strong class="credits-count-rewrites"><?php echo esc_html($credits['rewrite_credits'] ?? 0); ?></strong></p>
    </div>

    <?php if ($payment_success): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($payment_message); ?></p>
    </div>
    <?php elseif (!empty($payment_message)): ?>
    <div class="notice notice-warning is-dismissible">
        <p><?php echo esc_html($payment_message); ?></p>
    </div>
    <?php endif; ?>

    <?php if ($packages_error): ?>
    <div class="notice notice-error is-dismissible">
        <p><strong>パッケージ読み込みエラー:</strong> <?php echo esc_html($packages_error); ?></p>
    </div>
    <?php else: ?>

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
            <!-- Articles Packages -->
            <?php if (!empty($packages['articles'])): ?>
                <?php foreach ($packages['articles'] as $package): ?>
                    <div class="pricing-card">
                        <h3><?php echo esc_html($package['name'] ?? '記事生成クレジット'); ?></h3>
                        <div class="price">¥<?php echo number_format($package['price'], 0); ?></div>
                        <div class="credits-amount"><?php echo esc_html($package['credits']); ?>記事分のクレジット</div>
                        <ul class="features">
                            <li><?php echo esc_html($package['credits']); ?>記事をAI生成</li>
                            <li>GPT-4搭載</li>
                            <li>高品質なコンテンツ</li>
                            <li>下書き投稿を自動作成</li>
                        </ul>
                        <button class="button button-primary button-large" onclick="rakubunInitiatePayment('<?php echo esc_attr($package['package_id'] ?? 'articles'); ?>', <?php echo esc_attr($package['price']); ?>)">
                            今すぐ購入
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Images Packages -->
            <?php if (!empty($packages['images'])): ?>
                <?php foreach ($packages['images'] as $package): ?>
                    <div class="pricing-card">
                        <h3><?php echo esc_html($package['name'] ?? '画像生成クレジット'); ?></h3>
                        <div class="price">¥<?php echo number_format($package['price'], 0); ?></div>
                        <div class="credits-amount"><?php echo esc_html($package['credits']); ?>画像分のクレジット</div>
                        <ul class="features">
                            <li><?php echo esc_html($package['credits']); ?>画像をAI生成</li>
                            <li>DALL-E 3搭載</li>
                            <li>高品質な画像</li>
                            <li>複数サイズに対応</li>
                        </ul>
                        <button class="button button-primary button-large" onclick="rakubunInitiatePayment('<?php echo esc_attr($package['package_id'] ?? 'images'); ?>', <?php echo esc_attr($package['price']); ?>)">
                            今すぐ購入
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Auto Rewrite Packages Section -->
    <div id="rewrite-packages" class="rakubun-rewrite-packages">
        <h2>🔄 記事リライトパッケージ</h2>
        <p class="package-description">既存の記事をAIが自動的にリライトし、SEO効果を向上させます。大規模サイト向けの特別価格をご用意！</p>
        
        <div class="rewrite-pricing-cards">
            <?php if (!empty($packages['rewrites'])): ?>
            <?php foreach ($packages['rewrites'] as $package_key => $package): ?>
            <div class="rewrite-pricing-card <?php echo (!empty($package['is_popular']) || !empty($package['popular'])) ? 'popular' : ''; ?>">
                <?php if (!empty($package['is_popular']) || !empty($package['popular'])): ?>
                <div class="popular-badge">最人気</div>
                <?php endif; ?>
                
                <h3><?php echo esc_html($package['name']); ?></h3>
                <div class="package-price">
                    <span class="main-price">¥<?php echo number_format($package['price']); ?></span>
                    <?php if (!empty($package['credits'])): ?>
                    <span class="per-unit">（1リライト ¥<?php echo intval($package['price'] / $package['credits']); ?>）</span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($package['discount'])): ?>
                <div class="discount-badge"><?php echo esc_html($package['discount']); ?></div>
                <?php endif; ?>
                
                <div class="package-credits"><?php echo $package['credits'] ?? 'N/A'; ?>リライト分のクレジット</div>
                <div class="suitable-for"><?php echo esc_html($package['description'] ?? ''); ?></div>
                
                <ul class="package-features">
                    <li>✅ 既存記事のAIリライト</li>
                    <li>✅ SEO効果の向上</li>
                    <li>✅ キーワード最適化</li>
                    <li>✅ 構造・読みやすさ改善</li>
                    <li>✅ 自動スケジューリング対応</li>
                    <?php if ($package['is_popular'] ?? false): ?>
                    <li>✅ 優先サポート</li>
                    <?php endif; ?>
                </ul>
                
                <button class="button button-primary button-large" onclick="rakubunInitiatePayment('<?php echo esc_attr($package['package_id']); ?>', <?php echo esc_attr($package['price']); ?>)">
                    今すぐ購入
                </button>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
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

    <!-- Stripe Checkout Form (Modern Professional Checkout) -->
    <div id="rakubun-checkout-container" style="display:none;">
        <div id="rakubun-checkout-wrapper">
            <button id="rakubun-checkout-button" class="button button-primary button-large" style="width: 100%; padding: 15px;">
                🔒 Stripe で決済する
            </button>
            <button class="button" onclick="rakubunCancelCheckout()" style="width: 100%; padding: 15px; margin-top: 10px;">
                キャンセル
            </button>
        </div>
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

    <?php endif; // End of packages error check ?>
</div>

<style>
/* ===== Pricing Cards Styling (Articles/Images) ===== */
.rakubun-pricing {
    margin: 40px 0;
}

.rakubun-pricing h2 {
    text-align: center;
    margin-bottom: 30px;
    color: #333;
    font-size: 28px;
}

.pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.pricing-card {
    background: #fff;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 30px 25px;
    text-align: center;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.pricing-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    border-color: #667eea;
}

.pricing-card h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.pricing-card .price {
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 10px;
}

.pricing-card .credits-amount {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
    font-weight: 500;
}

.pricing-card .features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
    text-align: left;
}

.pricing-card .features li {
    padding: 8px 0;
    font-size: 14px;
    color: #555;
    border-bottom: 1px solid #f0f0f0;
}

.pricing-card .features li:last-child {
    border-bottom: none;
}

.pricing-card .button {
    width: 100%;
    margin-top: 15px;
}

/* ===== Rewrite Packages Section ===== */
.rakubun-rewrite-packages {
    margin: 60px 0 40px 0;
}

.rakubun-rewrite-packages h2 {
    text-align: center;
    margin-bottom: 15px;
    color: #333;
    font-size: 28px;
}

.package-description {
    text-align: center;
    font-size: 16px;
    color: #666;
    margin-bottom: 40px;
    line-height: 1.6;
}

.rewrite-pricing-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-bottom: 50px;
}

.rewrite-pricing-card {
    background: #fff;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 30px 25px;
    text-align: center;
    position: relative;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.rewrite-pricing-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    border-color: #667eea;
}

.rewrite-pricing-card.popular {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
}

.rewrite-pricing-card.popular:hover {
    box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
}

.popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 18px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.rewrite-pricing-card h3 {
    margin: 0 0 15px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.package-price {
    margin-bottom: 15px;
}

.main-price {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #667eea;
    margin-bottom: 5px;
}

.per-unit {
    display: block;
    font-size: 12px;
    color: #999;
    font-weight: normal;
}

.discount-badge {
    background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin: 10px 0 15px 0;
}

.package-credits {
    font-size: 16px;
    font-weight: 600;
    color: #667eea;
    margin-bottom: 8px;
}

.suitable-for {
    font-size: 13px;
    color: #666;
    margin-bottom: 20px;
    line-height: 1.5;
}

.package-features {
    list-style: none;
    padding: 0;
    margin: 0 0 25px 0;
    text-align: left;
}

.package-features li {
    padding: 8px 0;
    font-size: 14px;
    color: #555;
    border-bottom: 1px solid #f0f0f0;
}

.package-features li:last-child {
    border-bottom: none;
}

.rewrite-pricing-card .button {
    width: 100%;
    margin-top: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: white !important;
    padding: 12px 20px !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 16px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2) !important;
}

.rewrite-pricing-card .button:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
}

/* ===== Benefits Section ===== */
.rewrite-benefits {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 50px 40px;
    border-radius: 12px;
    margin-top: 40px;
}

.rewrite-benefits h3 {
    text-align: center;
    margin: 0 0 40px 0;
    color: white;
    font-size: 24px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
}

.benefit {
    text-align: center;
    color: white;
    padding: 20px;
}

.benefit-icon {
    font-size: 48px;
    margin-bottom: 15px;
    line-height: 1;
    display: block;
}

.benefit h4 {
    margin: 0 0 12px 0;
    font-size: 16px;
    font-weight: 600;
}

.benefit p {
    margin: 0;
    font-size: 14px;
    line-height: 1.6;
    opacity: 0.9;
}

/* ===== Navigation Styling ===== */
.rakubun-pricing-navigation {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    margin: 20px 0 40px 0;
    border-radius: 12px;
    text-align: center;
}

.pricing-explanation h2 {
    margin: 0 0 15px 0;
    color: white;
    font-size: 24px;
}

.pricing-explanation p {
    margin: 0 0 30px 0;
    font-size: 16px;
    opacity: 0.95;
    line-height: 1.6;
}

.pricing-nav-tabs {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.nav-tab {
    background: rgba(255, 255, 255, 0.15);
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
    font-size: 14px;
    text-align: left;
    min-width: 200px;
    backdrop-filter: blur(10px);
}

.nav-tab:hover,
.nav-tab.active {
    background: rgba(255, 255, 255, 0.25);
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

#basic-credits {
    scroll-margin-top: 100px;
}

#rewrite-packages {
    scroll-margin-top: 100px;
}

/* ===== Responsiveness ===== */
@media (max-width: 768px) {
    .rakubun-pricing-navigation {
        padding: 30px 20px;
    }

    .pricing-nav-tabs {
        flex-direction: column;
        gap: 12px;
    }

    .nav-tab {
        min-width: unset;
        width: 100%;
    }

    .pricing-cards,
    .rewrite-pricing-cards {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .benefits-grid {
        grid-template-columns: 1fr;
        gap: 25px;
    }

    .pricing-card,
    .rewrite-pricing-card {
        padding: 25px 20px;
    }

    .rewrite-benefits {
        padding: 40px 25px;
    }

    .pricing-card .price,
    .main-price {
        font-size: 28px;
    }

    .pricing-explanation h2,
    .rakubun-rewrite-packages h2,
    .rakubun-pricing h2 {
        font-size: 22px;
    }
}

/* ===== Payment Form Styling ===== */
#rakubun-payment-form {
    background: white;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 40px;
    margin: 40px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

#rakubun-payment-form h2 {
    margin: 0 0 30px 0;
    font-size: 24px;
    color: #333;
    text-align: center;
}

#rakubun-stripe-card-element {
    border: 1px solid #e5e5e5;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 20px;
    background: #f8f9fa;
}

.rakubun-StripeElement--focus {
    border-color: #667eea;
}

#rakubun-card-errors {
    color: #fa755a;
    margin-bottom: 20px;
    padding: 12px 15px;
    background: #fef5f5;
    border: 1px solid #fcc;
    border-radius: 6px;
}

#rakubun-payment-submit {
    width: 100%;
    padding: 12px 20px;
    font-size: 16px;
    font-weight: 600;
    margin-top: 20px;
    margin-bottom: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    border-radius: 6px;
    color: white;
    cursor: pointer;
    transition: all 0.3s ease;
}

#rakubun-payment-submit:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
}

#rakubun-payment-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

#rakubun-payment-form .button:last-child {
    width: 100%;
    padding: 12px 20px;
    font-size: 16px;
    background: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 6px;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

#rakubun-payment-form .button:last-child:hover {
    background: #e8e8e8;
    border-color: #999;
}

/* ===== Stripe Checkout Styling ===== */
#rakubun-checkout-container {
    background: white;
    border: 2px solid #e5e5e5;
    border-radius: 12px;
    padding: 40px;
    margin: 40px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

#rakubun-checkout-wrapper {
    text-align: center;
}

#rakubun-checkout-button {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: white !important;
    font-size: 16px !important;
    font-weight: 600 !important;
    padding: 15px 40px !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2) !important;
}

#rakubun-checkout-button:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
}

#rakubun-checkout-button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

#rakubun-checkout-container .button {
    background: #f0f0f0 !important;
    border: 1px solid #ddd !important;
    color: #333 !important;
    font-size: 16px !important;
    padding: 15px 40px !important;
    border-radius: 6px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
}

#rakubun-checkout-container .button:hover {
    background: #e8e8e8 !important;
    border-color: #999 !important;
}

.rakubun-loading {
    text-align: center;
    padding: 40px;
}

.rakubun-loading .spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    margin-bottom: 20px;
}

.rakubun-loading p {
    font-size: 16px;
    color: #666;
    margin: 0;
}
</style>
</style>

<script>
// If we just completed a payment, clean URL once (don't reload infinitely)
function handlePaymentSuccess() {
    const urlParams = new URLSearchParams(window.location.search);
    const sessionId = urlParams.get('session_id');
    const status = urlParams.get('status');
    
    if (status === 'success' && sessionId) {
        console.log('Payment completed, cleaning URL...');
        
        // Clean URL after showing success for 3 seconds
        setTimeout(function() {
            // Use window.history.replaceState to change URL without reloading
            const cleanUrl = '<?php echo esc_url(add_query_arg(array('page' => 'rakubun-ai-purchase'), admin_url('admin.php'))); ?>';
            window.history.replaceState({}, document.title, cleanUrl);
            
            // Reload ONCE to refresh credit display with clean URL
            location.reload();
        }, 3000);
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', function() {
    handlePaymentSuccess();
});

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
