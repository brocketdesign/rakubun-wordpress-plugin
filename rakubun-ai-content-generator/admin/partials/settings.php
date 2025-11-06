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

// Initialize external API
require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
$external_api = new Rakubun_AI_External_API();

$registration_status = get_option('rakubun_ai_registration_status', 'not_registered');
$is_connected = $external_api->is_connected();
$connection_test = $external_api->test_connection();

// Handle registration request
if (isset($_POST['register_plugin']) && wp_verify_nonce($_POST['rakubun_ai_settings_nonce'], 'rakubun_ai_settings')) {
    if ($external_api->register_plugin()) {
        $registration_status = 'registered';
        $is_connected = true;
        echo '<div class="notice notice-success"><p>プラグインが正常に登録されました。</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>プラグインの登録に失敗しました。しばらく待ってから再試行してください。</p></div>';
    }
}

// Handle force sync request
if (isset($_POST['force_sync']) && wp_verify_nonce($_POST['rakubun_ai_settings_nonce'], 'rakubun_ai_settings')) {
    if ($external_api->send_analytics()) {
        echo '<div class="notice notice-success"><p>データが正常に同期されました。</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>データの同期に失敗しました。</p></div>';
    }
}

// Handle dev test requests
$dev_test_results = array();
if (wp_verify_nonce($_POST['rakubun_ai_settings_nonce'] ?? '', 'rakubun_ai_settings')) {
    if (isset($_POST['test_article_config'])) {
        $dev_test_results['article_config'] = $external_api->test_article_configuration();
    } elseif (isset($_POST['test_image_config'])) {
        $dev_test_results['image_config'] = $external_api->test_image_configuration();
    } elseif (isset($_POST['test_rewrite_config'])) {
        $dev_test_results['rewrite_config'] = $external_api->test_rewrite_configuration();
    } elseif (isset($_POST['test_stripe_config'])) {
        $dev_test_results['stripe_config'] = $external_api->test_stripe_configuration();
    }
}
?>

<div class="wrap rakubun-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rakubun_ai_settings', 'rakubun_ai_settings_nonce'); ?>
        
        <!-- Connection Status Section -->
        <h2>接続ステータス</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Rakubun管理ダッシュボード</th>
                <td>
                    <?php if ($is_connected): ?>
                        <span style="color: green; font-weight: bold;">✓ 接続済み</span>
                        <p class="description">プラグインはRakubun管理ダッシュボードに正常に接続されています。</p>
                    <?php else: ?>
                        <span style="color: red; font-weight: bold;">✗ 未接続</span>
                        <p class="description">プラグインをRakubun管理ダッシュボードに接続する必要があります。</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">API接続テスト</th>
                <td>
                    <?php if ($connection_test): ?>
                        <span style="color: green; font-weight: bold;">✓ 正常</span>
                    <?php else: ?>
                        <span style="color: red; font-weight: bold;">✗ 失敗</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">インスタンスID</th>
                <td>
                    <code><?php echo esc_html(get_option('rakubun_ai_instance_id', '未生成')); ?></code>
                    <p class="description">このWordPressサイトの一意識別子です。</p>
                </td>
            </tr>
        </table>

        <!-- Registration Section (show only if not connected) -->
        <?php if (!$is_connected): ?>
        <h2>プラグイン登録</h2>
        <table class="form-table">
            <tr>
                <th scope="row">登録</th>
                <td>
                    <input type="submit" name="register_plugin" class="button button-primary" value="Rakubun管理ダッシュボードに登録">
                    <p class="description">
                        このボタンをクリックして、プラグインをRakubun管理ダッシュボードに登録してください。<br>
                        登録後、外部ダッシュボードからクレジット管理や設定を行えるようになります。
                    </p>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <!-- Data Sync Section -->
        <h2>データ同期</h2>
        <table class="form-table">
            <tr>
                <th scope="row">アナリティクス同期</th>
                <td>
                    <input type="submit" name="force_sync" class="button" value="今すぐ同期">
                    <p class="description">
                        使用統計とアナリティクスデータを外部ダッシュボードに送信します。<br>
                        通常は自動的に1時間ごとに同期されます。
                    </p>
                </td>
            </tr>
        </table>
    </form>

    <!-- Development Testing Section -->
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
    <div style="background: #f5f5f5; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin-top: 30px;">
        <h2 style="color: #0073aa;">🔧 開発者用テストツール</h2>
        <p style="color: #666; font-style: italic;">注: このセクションはWP_DEBUGが有効な場合にのみ表示されます。生成は行わず、外部APIからの設定とキーのみをチェックします。</p>
        
        <form method="post" action="">
            <?php wp_nonce_field('rakubun_ai_settings', 'rakubun_ai_settings_nonce'); ?>
            
            <!-- Article Configuration Test -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h3>📝 記事生成設定チェック</h3>
                <p style="color: #666;">外部ダッシュボードから記事生成用のOpenAI設定を取得できるか確認します。<strong>実際には記事を生成しません。</strong></p>
                <input type="submit" name="test_article_config" class="button button-secondary" value="チェック実行">
                
                <?php if (isset($dev_test_results['article_config'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: <?php echo $dev_test_results['article_config']['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $dev_test_results['article_config']['success'] ? '#28a745' : '#dc3545'; ?>;">
                    <strong><?php echo $dev_test_results['article_config']['success'] ? '✓ 成功' : '✗ 失敗'; ?></strong>
                    <pre style="margin-top: 10px; padding: 10px; background: white; border-radius: 3px; overflow-x: auto; max-height: 200px;"><?php echo esc_html(json_encode($dev_test_results['article_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
                <?php endif; ?>
            </div>

            <!-- Image Configuration Test -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h3>🖼️ 画像生成設定チェック</h3>
                <p style="color: #666;">外部ダッシュボードから画像生成用のOpenAI設定を取得できるか確認します。<strong>実際には画像を生成しません。</strong></p>
                <input type="submit" name="test_image_config" class="button button-secondary" value="チェック実行">
                
                <?php if (isset($dev_test_results['image_config'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: <?php echo $dev_test_results['image_config']['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $dev_test_results['image_config']['success'] ? '#28a745' : '#dc3545'; ?>;">
                    <strong><?php echo $dev_test_results['image_config']['success'] ? '✓ 成功' : '✗ 失敗'; ?></strong>
                    <pre style="margin-top: 10px; padding: 10px; background: white; border-radius: 3px; overflow-x: auto; max-height: 200px;"><?php echo esc_html(json_encode($dev_test_results['image_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
                <?php endif; ?>
            </div>

            <!-- Rewrite Configuration Test -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h3>🔄 リライト設定チェック</h3>
                <p style="color: #666;">外部ダッシュボードからリライト用のOpenAI設定を取得できるか確認します。<strong>実際にはリライトを実行しません。</strong></p>
                <input type="submit" name="test_rewrite_config" class="button button-secondary" value="チェック実行">
                
                <?php if (isset($dev_test_results['rewrite_config'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: <?php echo $dev_test_results['rewrite_config']['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $dev_test_results['rewrite_config']['success'] ? '#28a745' : '#dc3545'; ?>;">
                    <strong><?php echo $dev_test_results['rewrite_config']['success'] ? '✓ 成功' : '✗ 失敗'; ?></strong>
                    <pre style="margin-top: 10px; padding: 10px; background: white; border-radius: 3px; overflow-x: auto; max-height: 200px;"><?php echo esc_html(json_encode($dev_test_results['rewrite_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
                <?php endif; ?>
            </div>

            <!-- Stripe Configuration Test -->
            <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin: 15px 0;">
                <h3>💳 Stripe設定チェック</h3>
                <p style="color: #666;">外部ダッシュボードからStripe公開キーが正しく取得できるか、および決済システムが正常に動作するか確認します。</p>
                <input type="submit" name="test_stripe_config" class="button button-secondary" value="チェック実行">
                
                <?php if (isset($dev_test_results['stripe_config'])): ?>
                <div style="margin-top: 15px; padding: 15px; background: <?php echo $dev_test_results['stripe_config']['success'] ? '#d4edda' : '#f8d7da'; ?>; border-left: 4px solid <?php echo $dev_test_results['stripe_config']['success'] ? '#28a745' : '#dc3545'; ?>;">
                    <strong><?php echo $dev_test_results['stripe_config']['success'] ? '✓ 成功' : '✗ 失敗'; ?></strong>
                    <pre style="margin-top: 10px; padding: 10px; background: white; border-radius: 3px; overflow-x: auto; max-height: 200px;"><?php echo esc_html(json_encode($dev_test_results['stripe_config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
