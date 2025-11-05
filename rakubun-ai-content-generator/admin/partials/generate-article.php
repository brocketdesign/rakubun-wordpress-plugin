<?php
/**
 * Generate Article page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-generate-article">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>利用可能な記事生成クレジット: <strong class="credits-count"><?php echo esc_html($credits['article_credits']); ?></strong></p>
        <?php if ($credits['article_credits'] == 0): ?>
            <p class="notice notice-warning">記事生成クレジットが不足しています。<a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>">クレジットを購入</a>してください。</p>
        <?php endif; ?>
    </div>

    <div class="rakubun-form-container">
        <form id="rakubun-generate-article-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="article_title">記事タイトル（任意）</label>
                    </th>
                    <td>
                        <input type="text" id="article_title" name="title" class="regular-text" placeholder="空白の場合は自動生成されます">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="article_prompt">記事の内容 *</label>
                    </th>
                    <td>
                        <textarea id="article_prompt" name="prompt" rows="6" class="large-text" placeholder="例: メンタルヘルスにおける瞑想の効果について包括的な記事を書いてください" required></textarea>
                        <p class="description">生成したい記事の内容を説明してください。トピック、トーン、重要なポイントについて具体的に記述してください。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create_post">投稿を作成</label>
                    </th>
                    <td>
                        <input type="checkbox" id="create_post" name="create_post" value="1" checked>
                        <label for="create_post">生成されたコンテンツで下書き投稿を自動作成する</label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary button-large" <?php echo $credits['article_credits'] == 0 ? 'disabled' : ''; ?>>
                    📝 記事を生成する（1クレジット消費）
                </button>
            </p>
        </form>

        <div id="rakubun-article-result" class="rakubun-result" style="display:none;">
            <h2>生成された記事</h2>
            <div id="rakubun-article-content" class="generated-content"></div>
            <div class="result-actions">
                <button type="button" class="button" onclick="rakubunCopyContent('rakubun-article-content')">クリップボードにコピー</button>
            </div>
        </div>

        <div id="rakubun-article-loading" class="rakubun-loading" style="display:none;">
            <div class="spinner is-active"></div>
            <p>記事を生成しています... しばらくお待ちください。</p>
        </div>

        <div id="rakubun-article-error" class="notice notice-error" style="display:none;">
            <p></p>
        </div>
    </div>
</div>
