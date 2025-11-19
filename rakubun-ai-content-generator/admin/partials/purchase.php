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
    // Always fetch fresh packages from external API
    // Packages contain sensitive pricing info that must always be current
    $external_packages = $external_api->get_packages();
    error_log('Rakubun Purchase - API Response: ' . wp_json_encode($external_packages));
    
    if ($external_packages && is_array($external_packages)) {
        $cached_packages = $external_packages;
        error_log('Rakubun Purchase - Fresh Packages: ' . wp_json_encode($cached_packages));
    } else {
        $packages_error = 'ダッシュボードからパッケージを取得できませんでした。しばらく待ってから再度お試しください。';
        error_log('Rakubun Purchase - Empty API Response');
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
    <?php endif; ?>

    <!-- Main Heading Section -->
    <?php if (!$packages_error): ?>
    <div class="rakubun-pricing-header">
        <h2>📦 ご利用いただけるクレジットパッケージ</h2>
        <p>目的に応じて3つの異なるクレジットパッケージからお選びいただけます。各セクションでお得なパッケージプランをご用意しています。</p>
    </div>

    <!-- SECTION 1: Article Packages -->
    <div id="article-packages" class="rakubun-package-section">
        <div class="section-header">
            <h2>✍️ 記事生成パッケージ</h2>
            <p>新しいコンテンツを素早く生成。AI搭載で高品質な記事を自動作成</p>
        </div>
        
        <div class="pricing-cards">
            <?php if (!empty($packages['articles'])): ?>
                <?php foreach ($packages['articles'] as $package): ?>
                    <div class="pricing-card <?php echo (!empty($package['is_popular']) || !empty($package['popular'])) ? 'popular' : ''; ?>">
                        <?php if (!empty($package['is_popular']) || !empty($package['popular'])): ?>
                        <div class="popular-badge">最人気</div>
                        <?php endif; ?>
                        
                        <h3><?php echo esc_html($package['name'] ?? '記事生成クレジット'); ?></h3>
                        
                        <div class="package-price">
                            <span class="main-price">¥<?php echo number_format($package['price'], 0); ?></span>
                        </div>
                        
                        <?php if (!empty($package['discount'])): ?>
                        <div class="discount-badge"><?php echo esc_html($package['discount']); ?></div>
                        <?php endif; ?>
                        
                        <div class="credits-amount"><?php echo esc_html($package['credits']); ?>記事分のクレジット</div>
                        
                        <?php if (!empty($package['description'])): ?>
                        <div class="package-description"><?php echo esc_html($package['description']); ?></div>
                        <?php endif; ?>
                        
                        <ul class="package-features">
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
        </div>
    </div>

    <!-- SECTION 2: Image Packages -->
    <div id="image-packages" class="rakubun-package-section">
        <div class="section-header">
            <h2>🖼️ 画像生成パッケージ</h2>
            <p>高品質な画像を素早く生成。複数のスタイルに対応した画像作成</p>
        </div>
        
        <div class="pricing-cards">
            <?php if (!empty($packages['images'])): ?>
                <?php foreach ($packages['images'] as $package): ?>
                    <div class="pricing-card <?php echo (!empty($package['is_popular']) || !empty($package['popular'])) ? 'popular' : ''; ?>">
                        <?php if (!empty($package['is_popular']) || !empty($package['popular'])): ?>
                        <div class="popular-badge">最人気</div>
                        <?php endif; ?>
                        
                        <h3><?php echo esc_html($package['name'] ?? '画像生成クレジット'); ?></h3>
                        
                        <div class="package-price">
                            <span class="main-price">¥<?php echo number_format($package['price'], 0); ?></span>
                        </div>
                        
                        <?php if (!empty($package['discount'])): ?>
                        <div class="discount-badge"><?php echo esc_html($package['discount']); ?></div>
                        <?php endif; ?>
                        
                        <div class="credits-amount"><?php echo esc_html($package['credits']); ?>画像分のクレジット</div>
                        
                        <?php if (!empty($package['description'])): ?>
                        <div class="package-description"><?php echo esc_html($package['description']); ?></div>
                        <?php endif; ?>
                        
                        <ul class="package-features">
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

    <!-- SECTION 3: Rewrite Packages -->
    <div id="rewrite-packages" class="rakubun-package-section">
        <div class="section-header">
            <h2>🔄 記事リライトパッケージ</h2>
            <p>既存の記事をAIが自動的にリライトし、SEO効果を向上させます。大規模サイト向けの特別価格をご用意</p>
        </div>
        
        <div class="pricing-cards">
            <?php if (!empty($packages['rewrites'])): ?>
            <?php foreach ($packages['rewrites'] as $package_key => $package): ?>
            <div class="pricing-card <?php echo (!empty($package['is_popular']) || !empty($package['popular'])) ? 'popular' : ''; ?>">
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
                
                <div class="credits-amount"><?php echo $package['credits'] ?? 'N/A'; ?>リライト分のクレジット</div>
                
                <?php if (!empty($package['description'])): ?>
                <div class="package-description"><?php echo esc_html($package['description']); ?></div>
                <?php endif; ?>
                
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
/* ===== Main Header ===== */
.rakubun-pricing-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 50px 40px;
    margin: 20px 0 50px 0;
    border-radius: 12px;
    text-align: center;
}

.rakubun-pricing-header h2 {
    margin: 0 0 15px 0;
    font-size: 32px;
    font-weight: 700;
    line-height: 1.3;
}

.rakubun-pricing-header p {
    margin: 0;
    font-size: 16px;
    opacity: 0.95;
    line-height: 1.6;
}

/* ===== Package Section (Articles, Images, Rewrites) ===== */
.rakubun-package-section {
    margin: 50px 0;
}

.section-header {
    text-align: center;
    margin-bottom: 40px;
}

.section-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 600;
    color: #333;
}

.section-header p {
    margin: 0;
    font-size: 16px;
    color: #666;
    line-height: 1.6;
}

/* ===== Unified Pricing Cards ===== */
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
    display: flex;
    flex-direction: column;
}

.pricing-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
    border-color: #667eea;
}

.pricing-card.popular {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.02) 0%, rgba(118, 75, 162, 0.02) 100%);
}

.pricing-card.popular:hover {
    box-shadow: 0 12px 28px rgba(102, 126, 234, 0.2);
}

/* Popular Badge */
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
    white-space: nowrap;
}

/* Card Title */
.pricing-card h3 {
    margin: 15px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

/* Price Section */
.package-price {
    margin: 15px 0;
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

/* Discount Badge */
.discount-badge {
    background: linear-gradient(135deg, #ff4757 0%, #ff6348 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
    margin: 10px 0;
    box-shadow: 0 2px 8px rgba(255, 71, 87, 0.2);
}

/* Credits Amount */
.credits-amount {
    font-size: 14px;
    color: #667eea;
    margin: 10px 0;
    font-weight: 600;
}

/* Package Description */
.package-description {
    font-size: 13px;
    color: #666;
    margin: 12px 0 20px 0;
    line-height: 1.5;
    flex-grow: 1;
}

/* Package Features List */
.package-features {
    list-style: none;
    padding: 0;
    margin: 20px 0;
    text-align: left;
}

.package-features li {
    padding: 8px 0;
    font-size: 14px;
    color: #555;
    border-bottom: 1px solid #f0f0f0;
    line-height: 1.4;
}

.package-features li:last-child {
    border-bottom: none;
}

/* Button */
.pricing-card .button {
    width: 100%;
    margin-top: auto;
    padding: 12px 20px !important;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    border: none !important;
    color: white !important;
    border-radius: 6px !important;
    font-weight: 600 !important;
    font-size: 15px !important;
    cursor: pointer !important;
    transition: all 0.3s ease !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2) !important;
}

.pricing-card .button:hover:not(:disabled) {
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3) !important;
}

.pricing-card .button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

/* ===== Responsiveness ===== */
@media (max-width: 768px) {
    .rakubun-pricing-header {
        padding: 40px 25px;
        margin: 20px 0 40px 0;
    }

    .rakubun-pricing-header h2 {
        font-size: 24px;
    }

    .rakubun-pricing-header p {
        font-size: 14px;
    }

    .section-header h2 {
        font-size: 22px;
    }

    .section-header p {
        font-size: 14px;
    }

    .pricing-cards {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .pricing-card {
        padding: 25px 20px;
    }

    .main-price {
        font-size: 28px;
    }


    .package-features li {
        font-size: 13px;
        padding: 6px 0;
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
</script>
