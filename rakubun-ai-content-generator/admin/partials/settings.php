<?php
/**
 * Settings page template
 */
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die('このページにアクセスする権限がありません。');
}

$openai_api_key = get_option('rakubun_ai_openai_api_key', '');
$stripe_public_key = get_option('rakubun_ai_stripe_public_key', '');
$stripe_secret_key = get_option('rakubun_ai_stripe_secret_key', '');
$article_price = get_option('rakubun_ai_article_price', 750);
$image_price = get_option('rakubun_ai_image_price', 300);
$articles_per_purchase = get_option('rakubun_ai_articles_per_purchase', 10);
$images_per_purchase = get_option('rakubun_ai_images_per_purchase', 20);
?>

<div class="wrap rakubun-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rakubun_ai_settings', 'rakubun_ai_settings_nonce'); ?>
        
        <h2>API設定</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="openai_api_key">OpenAI APIキー *</label>
                </th>
                <td>
                    <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr($openai_api_key); ?>" class="regular-text">
                    <p class="description">
                        <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>からAPIキーを取得してください
                    </p>
                </td>
            </tr>
        </table>

        <h2>Stripe設定</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="stripe_public_key">Stripe 公開可能キー *</label>
                </th>
                <td>
                    <input type="text" id="stripe_public_key" name="stripe_public_key" value="<?php echo esc_attr($stripe_public_key); ?>" class="regular-text">
                    <p class="description">
                        <a href="https://dashboard.stripe.com/apikeys" target="_blank">Stripeダッシュボード</a>からキーを取得してください
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="stripe_secret_key">Stripe シークレットキー *</label>
                </th>
                <td>
                    <input type="password" id="stripe_secret_key" name="stripe_secret_key" value="<?php echo esc_attr($stripe_secret_key); ?>" class="regular-text">
                </td>
            </tr>
        </table>

        <h2>料金設定</h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="article_price">記事パッケージ価格 (¥)</label>
                </th>
                <td>
                    <input type="number" id="article_price" name="article_price" value="<?php echo esc_attr($article_price); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="articles_per_purchase">購入あたりの記事数</label>
                </th>
                <td>
                    <input type="number" id="articles_per_purchase" name="articles_per_purchase" value="<?php echo esc_attr($articles_per_purchase); ?>" min="1" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="image_price">画像パッケージ価格 (¥)</label>
                </th>
                <td>
                    <input type="number" id="image_price" name="image_price" value="<?php echo esc_attr($image_price); ?>" step="1" min="0" class="small-text">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="images_per_purchase">購入あたりの画像数</label>
                </th>
                <td>
                    <input type="number" id="images_per_purchase" name="images_per_purchase" value="<?php echo esc_attr($images_per_purchase); ?>" min="1" class="small-text">
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="rakubun_ai_save_settings" class="button button-primary" value="設定を保存">
        </p>
    </form>

    <div class="rakubun-settings-info">
        <h2>セットアップ手順</h2>
        <ol>
            <li><a href="https://platform.openai.com/" target="_blank">OpenAI Platform</a>でアカウントを作成</li>
            <li>APIキーを生成して上記に貼り付け</li>
            <li><a href="https://stripe.com/" target="_blank">Stripe</a>でアカウントを作成</li>
            <li>Stripe APIキーを取得（開発用にはテストキーを使用）</li>
            <li>料金設定を構成</li>
            <li>設定を保存</li>
        </ol>

        <h3>無料クレジット</h3>
        <p>新規ユーザーには自動的に以下が付与されます：</p>
        <ul>
            <li>記事生成 3回分の無料クレジット</li>
            <li>画像生成 5回分の無料クレジット</li>
        </ul>
    </div>
</div>
