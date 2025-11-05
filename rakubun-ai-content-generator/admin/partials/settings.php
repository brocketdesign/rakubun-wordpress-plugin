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
?>

<div class="wrap rakubun-ai-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('rakubun_ai_settings', 'rakubun_ai_settings_nonce'); ?>
        
        <h2>接続ステータス</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Rakubun管理ダッシュボード</th>
                <td>
                    <?php if ($is_connected): ?>
                        <span style="color: green;">✓ 接続済み</span>
                        <p class="description">プラグインはRakubun管理ダッシュボードに正常に接続されています。</p>
                    <?php else: ?>
                        <span style="color: red;">✗ 未接続</span>
                        <p class="description">プラグインをRakubun管理ダッシュボードに接続する必要があります。</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">API接続テスト</th>
                <td>
                    <?php if ($connection_test): ?>
                        <span style="color: green;">✓ 正常</span>
                    <?php else: ?>
                        <span style="color: red;">✗ 失敗</span>
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

        <h2>外部管理設定</h2>
        <table class="form-table">
            <tr>
                <th scope="row">設定管理</th>
                <td>
                    <p class="description">
                        すべての設定（OpenAI APIキー、パッケージ価格、Stripe設定など）は、
                        <a href="https://app.rakubun.com" target="_blank">Rakubun管理ダッシュボード</a>で管理されます。
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">データ同期</th>
                <td>
                    <input type="submit" name="force_sync" class="button" value="今すぐ同期">
                    <p class="description">
                        使用統計とアナリティクスデータを外部ダッシュボードに送信します。<br>
                        通常は自動的に1日1回同期されます。
                    </p>
                </td>
            </tr>
        </table>

        <?php if ($is_connected): ?>
        <h2>現在の設定状況</h2>
        <table class="form-table">
            <tr>
                <th scope="row">設定管理</th>
                <td>
                    <a href="https://app.rakubun.com" target="_blank" class="button button-primary">
                        Rakubun管理ダッシュボードを開く
                    </a>
                    <p class="description">
                        外部ダッシュボードで以下の設定を管理できます：
                    </p>
                    <ul>
                        <li>OpenAI APIキーの設定</li>
                        <li>パッケージ価格の設定</li>
                        <li>ユーザークレジットの管理</li>
                        <li>使用統計の確認</li>
                        <li>複数サイトの一元管理</li>
                    </ul>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <h2>プラグイン情報</h2>
        <table class="form-table">
            <tr>
                <th scope="row">管理方法</th>
                <td>
                    <p>このプラグインは外部管理システムを使用しており、以下のような利点があります：</p>
                    <ul>
                        <li><strong>一元管理</strong>: 複数のWordPressサイトを一つのダッシュボードで管理</li>
                        <li><strong>セキュリティ</strong>: APIキーは外部で安全に管理され、サイトには保存されません</li>
                        <li><strong>リアルタイム</strong>: 設定変更は即座に反映されます</li>
                        <li><strong>アナリティクス</strong>: 詳細な使用統計と分析データ</li>
                        <li><strong>簡単設定</strong>: 複雑な設定は不要です</li>
                    </ul>
                </td>
            </tr>
        </table>
    </form>

    <div class="rakubun-settings-info">
        <h2>使用手順</h2>
        <ol>
            <li>上記の「Rakubun管理ダッシュボードに登録」ボタンをクリック</li>
            <li><a href="https://app.rakubun.com" target="_blank">Rakubun管理ダッシュボード</a>にアクセス</li>
            <li>OpenAI APIキーとその他の設定を構成</li>
            <li>プラグインでAIコンテンツの生成を開始</li>
        </ol>

        <h3>無料クレジット</h3>
        <p>新規ユーザーには自動的に以下が付与されます：</p>
        <ul>
            <li>記事生成 3回分の無料クレジット</li>
            <li>画像生成 5回分の無料クレジット</li>
        </ul>

        <h3>サポート</h3>
        <p>
            設定やご利用に関してご質問がある場合は、
            <a href="https://app.rakubun.com/support" target="_blank">サポートページ</a>をご確認ください。
        </p>
    </div>
</div>
