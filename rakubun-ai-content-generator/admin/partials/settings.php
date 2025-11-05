<?php
/**
 * Settings page template
 */
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

$openai_api_key = get_option('rakubun_ai_openai_api_key', '');
$stripe_public_key = get_option('rakubun_ai_stripe_public_key', '');
$stripe_secret_key = get_option('rakubun_ai_stripe_secret_key', '');
$article_price = get_option('rakubun_ai_article_price', 5.00);
$image_price = get_option('rakubun_ai_image_price', 2.00);
$articles_per_purchase = get_option('rakubun_ai_articles_per_purchase', 10);
$images_per_purchase = get_option('rakubun_ai_images_per_purchase', 20);
?>

<div class="wrap rakubun-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rakubun_ai_settings', 'rakubun_ai_settings_nonce'); ?>
        
        <h2>API Configuration</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="openai_api_key">OpenAI API Key *</label>
                </th>
                <td>
                    <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text">
                    <p class="description">
                        Get your API key from <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                    </p>
                </td>
            </tr>
        </table>

        <h2>Stripe Configuration</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="stripe_public_key">Stripe Publishable Key *</label>
                </th>
                <td>
                    <input type="text" id="stripe_public_key" name="stripe_public_key" value="<?php echo esc_attr($stripe_public_key); ?>" class="regular-text">
                    <p class="description">
                        Get your keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripe Dashboard</a>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="stripe_secret_key">Stripe Secret Key *</label>
                </th>
                <td>
                    <input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" class="regular-text">
                </td>
            </tr>
        </table>

        <h2>Pricing Configuration</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="article_price">Article Package Price ($)</label>
                </th>
                <td>
                    <input type="number" id="article_price" name="article_price" value="<?php echo esc_attr($article_price); ?>" step="0.01" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="articles_per_purchase">Articles per Purchase</label>
                </th>
                <td>
                    <input type="number" id="articles_per_purchase" name="articles_per_purchase" value="<?php echo esc_attr($articles_per_purchase); ?>" min="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="image_price">Image Package Price ($)</label>
                </th>
                <td>
                    <input type="number" id="image_price" name="image_price" value="<?php echo esc_attr($image_price); ?>" step="0.01" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="images_per_purchase">Images per Purchase</label>
                </th>
                <td>
                    <input type="number" id="images_per_purchase" name="images_per_purchase" value="<?php echo esc_attr($images_per_purchase); ?>" min="1" class="small-text">
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="rakubun_ai_save_settings" class="button button-primary" value="Save Settings">
        </p>
    </form>

    <div class="rakubun-settings-info">
        <h2>Setup Instructions</h2>
        <ol>
            <li>Create an account at <a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a></li>
            <li>Generate an API key and paste it above</li>
            <li>Create an account at <a href="https://stripe.com/" target="_blank">Stripe</a></li>
            <li>Get your Stripe API keys (use test keys for development)</li>
            <li>Configure your pricing preferences</li>
            <li>Save the settings</li>
        </ol>

        <h3>Free Credits</h3>
        <p>Each new user automatically receives:</p>
        <ul>
            <li>3 free article generation credits</li>
            <li>5 free image generation credits</li>
        </ul>
    </div>
</div>
