<?php
/**
 * Dashboard page template
 */
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap rakubun-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="rakubun-credits-overview">
        <div class="credits-box">
            <div class="credits-icon">📝</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['article_credits']); ?></h2>
                <p>Article Credits Remaining</p>
            </div>
        </div>
        
        <div class="credits-box">
            <div class="credits-icon">🖼️</div>
            <div class="credits-info">
                <h2><?php echo esc_html($credits['image_credits']); ?></h2>
                <p>Image Credits Remaining</p>
            </div>
        </div>
    </div>

    <div class="rakubun-quick-actions">
        <h2>Quick Actions</h2>
        <div class="action-buttons">
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-article'); ?>" class="button button-primary button-large">
                Generate Article
            </a>
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-image'); ?>" class="button button-primary button-large">
                Generate Image
            </a>
            <a href="<?php echo admin_url('admin.php?page=rakubun-ai-purchase'); ?>" class="button button-secondary button-large">
                Purchase Credits
            </a>
        </div>
    </div>

    <div class="rakubun-info-section">
        <h2>About Rakubun AI Content Generator</h2>
        <p>This plugin allows you to generate high-quality articles and images using OpenAI's GPT-4 and DALL-E models.</p>
        
        <h3>Free Credits</h3>
        <ul>
            <li>3 free article generations</li>
            <li>5 free image generations</li>
        </ul>
        
        <h3>Getting Started</h3>
        <ol>
            <li>Administrators: Configure your OpenAI API key and Stripe keys in <a href="<?php echo admin_url('admin.php?page=rakubun-ai-settings'); ?>">Settings</a></li>
            <li>Use your free credits to generate content</li>
            <li>Purchase additional credits when needed</li>
        </ol>
    </div>
</div>
