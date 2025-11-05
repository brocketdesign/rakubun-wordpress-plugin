<?php
/**
 * Purchase Credits page template
 */
if (!defined('WPINC')) {
    die;
}

$article_price = get_option('rakurabu_ai_article_price', 5.00);
$image_price = get_option('rakurabu_ai_image_price', 2.00);
$articles_per_purchase = get_option('rakurabu_ai_articles_per_purchase', 10);
$images_per_purchase = get_option('rakurabu_ai_images_per_purchase', 20);
?>

<div class="wrap rakurabu-ai-purchase">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakurabu-credits-status">
        <p>Current Credits - Articles: <strong class="credits-count-articles"><?php echo esc_html($credits['article_credits']); ?></strong> | Images: <strong class="credits-count-images"><?php echo esc_html($credits['image_credits']); ?></strong></p>
    </div>

    <div class="rakurabu-pricing">
        <h2>Purchase Additional Credits</h2>
        
        <div class="pricing-cards">
            <div class="pricing-card">
                <h3>Article Credits</h3>
                <div class="price">$<?php echo number_format($article_price, 2); ?></div>
                <div class="credits-amount"><?php echo $articles_per_purchase; ?> Article Credits</div>
                <ul class="features">
                    <li>Generate <?php echo $articles_per_purchase; ?> AI articles</li>
                    <li>Powered by GPT-4</li>
                    <li>High-quality content</li>
                    <li>Auto-create draft posts</li>
                </ul>
                <button class="button button-primary button-large" onclick="rakurabuInitiatePayment('articles', <?php echo $article_price; ?>)">
                    Purchase Now
                </button>
            </div>

            <div class="pricing-card">
                <h3>Image Credits</h3>
                <div class="price">$<?php echo number_format($image_price, 2); ?></div>
                <div class="credits-amount"><?php echo $images_per_purchase; ?> Image Credits</div>
                <ul class="features">
                    <li>Generate <?php echo $images_per_purchase; ?> AI images</li>
                    <li>Powered by DALL-E 3</li>
                    <li>High-quality images</li>
                    <li>Multiple sizes available</li>
                </ul>
                <button class="button button-primary button-large" onclick="rakurabuInitiatePayment('images', <?php echo $image_price; ?>)">
                    Purchase Now
                </button>
            </div>
        </div>
    </div>

    <div id="rakurabu-payment-form" style="display:none;">
        <h2>Complete Your Purchase</h2>
        <div id="rakurabu-stripe-card-element"></div>
        <div id="rakurabu-card-errors" class="notice notice-error" style="display:none;"></div>
        <button id="rakurabu-payment-submit" class="button button-primary">Complete Payment</button>
        <button class="button" onclick="rakurabuCancelPayment()">Cancel</button>
    </div>

    <div id="rakurabu-payment-loading" class="rakurabu-loading" style="display:none;">
        <div class="spinner is-active"></div>
        <p>Processing payment...</p>
    </div>

    <div id="rakurabu-payment-error" class="notice notice-error" style="display:none;">
        <p></p>
    </div>

    <div id="rakurabu-payment-success" class="notice notice-success" style="display:none;">
        <p>Payment successful! Your credits have been added.</p>
    </div>
</div>
