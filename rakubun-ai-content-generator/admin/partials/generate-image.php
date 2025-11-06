<?php
/**
 * Generate Image page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-generate-image">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-status">
        <p>利用可能な画像生成クレジット: <strong class="credits-count"><?php echo esc_html($credits['image_credits']); ?></strong></p>
        <?php if ($credits['image_credits'] == 0): ?>
            <p class="notice notice-warning">画像生成クレジットが不足しています。<a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>">クレジットを購入</a>してください。</p>
        <?php endif; ?>
    </div>

    <div class="rakubun-form-container">
        <form id="rakubun-generate-image-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="image_prompt">画像の説明 *</label>
                    </th>
                    <td>
                        <textarea id="image_prompt" name="prompt" rows="4" class="large-text" placeholder="例: 夕日が美しい山の風景と手前に湖がある静かな景色" required></textarea>
                        <p class="description">生成したい画像について詳しく説明してください。具体的で詳細な説明をお書きください。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_size">画像サイズ</label>
                    </th>
                    <td>
                        <select id="image_size" name="size">
                            <option value="1024x1024">正方形 (1024x1024)</option>
                            <option value="1024x1792">縦長 (1024x1792)</option>
                            <option value="1792x1024">横長 (1792x1024)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="save_to_media">メディアライブラリに保存</label>
                    </th>
                    <td>
                        <input type="checkbox" id="save_to_media" name="save_to_media" value="1" checked>
                        <label for="save_to_media">生成された画像を自動的にメディアライブラリに保存する</label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary button-large" <?php echo $credits['image_credits'] == 0 ? 'disabled' : ''; ?>>
                    🎨 画像を生成する（1クレジット消費）
                </button>
            </p>
        </form>

        <div id="rakubun-image-result" class="rakubun-result" style="display:none;">
            <h2>生成された画像</h2>
            <div id="rakubun-image-preview" class="image-preview"></div>
            <div class="result-actions">
                <a id="rakubun-image-download" href="#" class="button" download>画像をダウンロード</a>
            </div>
        </div>

        <div id="rakubun-image-loading" class="rakubun-loading" style="display:none;">
            <div class="spinner is-active"></div>
            <p>画像を生成しています... しばらくお待ちください。</p>
        </div>

        <div id="rakubun-image-error" class="notice notice-error" style="display:none;">
            <p></p>
        </div>
    </div>
</div>
