<?php
/**
 * Generate Article page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakurabu-ai-generate-article">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakurabu-credits-status">
        <p>Available Article Credits: <strong class="credits-count"><?php echo esc_html($credits['article_credits']); ?></strong></p>
        <?php if ($credits['article_credits'] == 0): ?>
            <p class="notice notice-warning">You have no article credits remaining. <a href="<?php echo admin_url('admin.php?page=rakurabu-ai-purchase'); ?>">Purchase more credits</a></p>
        <?php endif; ?>
    </div>

    <div class="rakurabu-form-container">
        <form id="rakurabu-generate-article-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="article_title">Article Title (Optional)</label>
                    </th>
                    <td>
                        <input type="text" id="article_title" name="title" class="regular-text" placeholder="Leave empty for auto-generated title">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="article_prompt">Article Prompt *</label>
                    </th>
                    <td>
                        <textarea id="article_prompt" name="prompt" rows="6" class="large-text" placeholder="Example: Write a comprehensive article about the benefits of meditation for mental health" required></textarea>
                        <p class="description">Describe the article you want to generate. Be specific about topic, tone, and key points.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="create_post">Create Post</label>
                    </th>
                    <td>
                        <input type="checkbox" id="create_post" name="create_post" value="1" checked>
                        <label for="create_post">Automatically create a draft post with the generated content</label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary button-large" <?php echo $credits['article_credits'] == 0 ? 'disabled' : ''; ?>>
                    Generate Article (1 Credit)
                </button>
            </p>
        </form>

        <div id="rakurabu-article-result" class="rakurabu-result" style="display:none;">
            <h2>Generated Article</h2>
            <div id="rakurabu-article-content" class="generated-content"></div>
            <div class="result-actions">
                <button type="button" class="button" onclick="rakurabuCopyContent('rakurabu-article-content')">Copy to Clipboard</button>
            </div>
        </div>

        <div id="rakurabu-article-loading" class="rakurabu-loading" style="display:none;">
            <div class="spinner is-active"></div>
            <p>Generating your article... This may take a minute.</p>
        </div>

        <div id="rakurabu-article-error" class="notice notice-error" style="display:none;">
            <p></p>
        </div>
    </div>
</div>
