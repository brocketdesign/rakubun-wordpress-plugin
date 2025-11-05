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
?>

<div class="wrap rakubun-ai-purchase">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>現在のクレジット残高 - 記事: <strong class="credits-count-articles"><?php echo esc_html($credits['article_credits']); ?></strong> | 画像: <strong class="credits-count-images"><?php echo esc_html($credits['image_credits']); ?></strong></p>
    </div>

    <div class="rakubun-pricing">
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
