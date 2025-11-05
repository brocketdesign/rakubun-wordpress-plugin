<?php
/**
 * Generate Image page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakurabu-ai-generate-image">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakurabu-credits-status">
        <p>Available Image Credits: <strong class="credits-count"><?php echo esc_html($credits['image_credits']); ?></strong></p>
        <?php if ($credits['image_credits'] == 0): ?>
            <p class="notice notice-warning">You have no image credits remaining. <a href="<?php echo admin_url('admin.php?page=rakurabu-ai-purchase'); ?>">Purchase more credits</a></p>
        <?php endif; ?>
    </div>

    <div class="rakurabu-form-container">
        <form id="rakurabu-generate-image-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="image_prompt">Image Prompt *</label>
                    </th>
                    <td>
                        <textarea id="image_prompt" name="prompt" rows="4" class="large-text" placeholder="Example: A peaceful mountain landscape at sunset with a lake in the foreground" required></textarea>
                        <p class="description">Describe the image you want to generate. Be descriptive and specific.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="image_size">Image Size</label>
                    </th>
                    <td>
                        <select id="image_size" name="size">
                            <option value="1024x1024">Square (1024x1024)</option>
                            <option value="1024x1792">Portrait (1024x1792)</option>
                            <option value="1792x1024">Landscape (1792x1024)</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="save_to_media">Save to Media Library</label>
                    </th>
                    <td>
                        <input type="checkbox" id="save_to_media" name="save_to_media" value="1" checked>
                        <label for="save_to_media">Automatically save the generated image to your media library</label>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary button-large" <?php echo $credits['image_credits'] == 0 ? 'disabled' : ''; ?>>
                    Generate Image (1 Credit)
                </button>
            </p>
        </form>

        <div id="rakurabu-image-result" class="rakurabu-result" style="display:none;">
            <h2>Generated Image</h2>
            <div id="rakurabu-image-preview" class="image-preview"></div>
            <div class="result-actions">
                <a id="rakurabu-image-download" href="#" class="button" download>Download Image</a>
            </div>
        </div>

        <div id="rakurabu-image-loading" class="rakurabu-loading" style="display:none;">
            <div class="spinner is-active"></div>
            <p>Generating your image... This may take a minute.</p>
        </div>

        <div id="rakurabu-image-error" class="notice notice-error" style="display:none;">
            <p></p>
        </div>
    </div>
</div>
