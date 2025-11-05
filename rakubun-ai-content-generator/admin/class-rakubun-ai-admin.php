<?php
/**
 * The admin-specific functionality of the plugin
 */
class Rakubun_AI_Admin {

    /**
     * The ID of this plugin
     */
    private $plugin_name;

    /**
     * The version of this plugin
     */
    private $version;

    /**
     * Initialize the class
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area
     */
    public function enqueue_styles() {
        // Add timestamp for cache busting
        $version = $this->version . '.' . time();
        wp_enqueue_style($this->plugin_name, RAKUBUN_AI_PLUGIN_URL . 'assets/css/admin.css', array(), $version, 'all');
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        // Add timestamp for cache busting
        $version = $this->version . '.' . time();
        
        // Enqueue Stripe.js library
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, false);
        
        // Enqueue plugin script
        wp_enqueue_script($this->plugin_name, RAKUBUN_AI_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'stripe-js'), $version, false);
        
        // Force no-cache headers for admin pages
        if (is_admin()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // Localize script with data
        wp_localize_script($this->plugin_name, 'rakubunAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rakubun_ai_nonce'),
            'stripe_public_key' => get_option('rakubun_ai_stripe_public_key', '')
        ));
    }

    /**
     * Add menu items
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Rakubun AI コンテンツジェネレーター',
            'AI コンテンツ',
            'edit_posts',
            'rakubun-ai-content',
            array($this, 'display_dashboard_page'),
            'dashicons-edit-page',
            30
        );

        add_submenu_page(
            'rakubun-ai-content',
            'ダッシュボード',
            'ダッシュボード',
            'edit_posts',
            'rakubun-ai-content',
            array($this, 'display_dashboard_page')
        );

        add_submenu_page(
            'rakubun-ai-content',
            '記事生成',
            '記事生成',
            'edit_posts',
            'rakubun-ai-generate-article',
            array($this, 'display_generate_article_page')
        );

        add_submenu_page(
            'rakubun-ai-content',
            '画像生成',
            '画像生成',
            'edit_posts',
            'rakubun-ai-generate-image',
            array($this, 'display_generate_image_page')
        );

        add_submenu_page(
            'rakubun-ai-content',
            'クレジット購入',
            'クレジット購入',
            'edit_posts',
            'rakubun-ai-purchase',
            array($this, 'display_purchase_page')
        );

        add_submenu_page(
            'rakubun-ai-content',
            '設定',
            '設定',
            'manage_options',
            'rakubun-ai-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard_page() {
        $user_id = get_current_user_id();
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        $analytics = Rakubun_AI_Credits_Manager::get_user_analytics($user_id);
        $recent_content = Rakubun_AI_Credits_Manager::get_recent_content($user_id, 5);
        $user_images = Rakubun_AI_Credits_Manager::get_user_images($user_id, 20);
        
        include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display generate article page
     */
    public function display_generate_article_page() {
        $user_id = get_current_user_id();
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/generate-article.php';
    }

    /**
     * Display generate image page
     */
    public function display_generate_image_page() {
        $user_id = get_current_user_id();
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/generate-image.php';
    }

    /**
     * Display purchase page
     */
    public function display_purchase_page() {
        $user_id = get_current_user_id();
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/purchase.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Handle settings save
        if (isset($_POST['rakubun_ai_save_settings']) && check_admin_referer('rakubun_ai_settings', 'rakubun_ai_settings_nonce')) {
            update_option('rakubun_ai_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            update_option('rakubun_ai_stripe_public_key', sanitize_text_field($_POST['stripe_public_key']));
            update_option('rakubun_ai_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
            update_option('rakubun_ai_article_price', intval($_POST['article_price']));
            update_option('rakubun_ai_image_price', intval($_POST['image_price']));
            update_option('rakubun_ai_articles_per_purchase', intval($_POST['articles_per_purchase']));
            update_option('rakubun_ai_images_per_purchase', intval($_POST['images_per_purchase']));
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * AJAX: Generate article
     */
    public function ajax_generate_article() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        // Check credits
        if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'article')) {
            wp_send_json_error(array('message' => 'Insufficient article credits. Please purchase more.'));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $create_post = isset($_POST['create_post']) && ($_POST['create_post'] === 'true' || $_POST['create_post'] === '1');

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Generate article
        $openai = new Rakubun_AI_OpenAI();
        $result = $openai->generate_article($prompt);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        // Deduct credit
        Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'article', 1);

        $post_id = 0;
        
        // Create post if requested
        if ($create_post) {
            $post_title = !empty($title) ? $title : 'AI Generated Article - ' . date('Y-m-d H:i:s');
            $post_id = wp_insert_post(array(
                'post_title' => $post_title,
                'post_content' => $result['content'],
                'post_status' => 'draft',
                'post_author' => $user_id
            ));
        }

        // Log the generation
        Rakubun_AI_Credits_Manager::log_generated_content($user_id, 'article', $prompt, $result['content'], '', $post_id, 0);

        // Get updated credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'content' => $result['content'],
            'post_id' => $post_id,
            'credits' => $credits
        ));
    }

    /**
     * AJAX: Generate image
     */
    public function ajax_generate_image() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        // Check credits
        if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'image')) {
            wp_send_json_error(array('message' => 'Insufficient image credits. Please purchase more.'));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '1024x1024';
        $save_to_media = isset($_POST['save_to_media']) && ($_POST['save_to_media'] === 'true' || $_POST['save_to_media'] === '1');

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Validate prompt length (DALL-E 3 has limits)
        if (strlen($prompt) > 4000) {
            wp_send_json_error(array('message' => 'Prompt is too long. Please keep it under 4000 characters.'));
        }

        // Log the attempt for debugging
        error_log('Rakubun AI: Attempting to generate image with prompt: ' . substr($prompt, 0, 100) . '...');

        // Generate image
        $openai = new Rakubun_AI_OpenAI();
        $result = $openai->generate_image($prompt, $size);

        if (!$result['success']) {
            error_log('Rakubun AI: Image generation failed: ' . $result['error']);
            wp_send_json_error(array('message' => $result['error']));
        }

        // Deduct credit
        Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'image', 1);

        $attachment_id = 0;
        $local_url = $result['url'];

        // Save to media library if requested
        if ($save_to_media) {
            $save_result = $openai->save_image_to_media($result['url'], 'AI Generated Image');
            if ($save_result['success']) {
                $attachment_id = $save_result['attachment_id'];
                $local_url = $save_result['url'];
            } else {
                error_log('Rakubun AI: Failed to save image to media library: ' . $save_result['error']);
                // Don't fail the whole operation, just log the error
            }
        }

        // Log the generation
        Rakubun_AI_Credits_Manager::log_generated_content($user_id, 'image', $prompt, '', $local_url, 0, $attachment_id);

        // Get updated credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'url' => $local_url,
            'attachment_id' => $attachment_id,
            'credits' => $credits
        ));
    }

    /**
     * AJAX: Create payment intent
     */
    public function ajax_create_payment_intent() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $credit_type = isset($_POST['credit_type']) ? sanitize_text_field($_POST['credit_type']) : '';

        if (empty($credit_type)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        // Validate credit type
        if (!in_array($credit_type, array('articles', 'images'), true)) {
            wp_send_json_error(array('message' => 'Invalid credit type.'));
        }

        // Get pricing info
        if ($credit_type === 'articles') {
            $amount = intval(get_option('rakubun_ai_article_price', 750));
            $credits = intval(get_option('rakubun_ai_articles_per_purchase', 10));
        } else {
            $amount = intval(get_option('rakubun_ai_image_price', 300));
            $credits = intval(get_option('rakubun_ai_images_per_purchase', 20));
        }

        // Create payment intent
        $stripe = new Rakubun_AI_Stripe();
        $result = $stripe->create_payment_intent($amount, 'jpy', array(
            'user_id' => $user_id,
            'credit_type' => $credit_type,
            'credits' => $credits
        ));

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        wp_send_json_success(array(
            'client_secret' => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id']
        ));
    }

    /**
     * AJAX: Process payment
     */
    public function ajax_process_payment() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $credit_type = isset($_POST['credit_type']) ? sanitize_text_field($_POST['credit_type']) : '';
        $payment_intent_id = isset($_POST['payment_intent_id']) ? sanitize_text_field($_POST['payment_intent_id']) : '';

        if (empty($credit_type) || empty($payment_intent_id)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        // Validate credit type
        if (!in_array($credit_type, array('articles', 'images'), true)) {
            wp_send_json_error(array('message' => 'Invalid credit type.'));
        }

        // Validate payment intent ID format (Stripe format: pi_...)
        if (!preg_match('/^pi_[a-zA-Z0-9_]+$/', $payment_intent_id)) {
            wp_send_json_error(array('message' => 'Invalid payment intent ID format.'));
        }

        // Verify payment with Stripe
        $stripe = new Rakubun_AI_Stripe();
        $verification = $stripe->verify_payment($payment_intent_id);

        if (!$verification['success']) {
            wp_send_json_error(array('message' => 'Payment verification failed.'));
        }

        // Verify the payment amount and metadata match our expectations
        $payment = $verification['payment'];
        if (!isset($payment['metadata']['user_id']) || $payment['metadata']['user_id'] != $user_id) {
            wp_send_json_error(array('message' => 'Payment metadata mismatch.'));
        }

        // Add credits based on type
        if ($credit_type === 'articles') {
            $credits_to_add = intval(get_option('rakubun_ai_articles_per_purchase', 10));
            $amount = intval(get_option('rakubun_ai_article_price', 750));
            Rakubun_AI_Credits_Manager::add_credits($user_id, 'article', $credits_to_add);
        } else {
            $credits_to_add = intval(get_option('rakubun_ai_images_per_purchase', 20));
            $amount = intval(get_option('rakubun_ai_image_price', 300));
            Rakubun_AI_Credits_Manager::add_credits($user_id, 'image', $credits_to_add);
        }

        // Log transaction
        Rakubun_AI_Credits_Manager::log_transaction(
            $user_id,
            'purchase',
            $amount,
            $credits_to_add,
            $credit_type,
            $payment_intent_id,
            'completed'
        );

        // Get updated credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'message' => 'Credits added successfully!',
            'credits' => $credits
        ));
    }

    /**
     * AJAX: Get credits
     */
    public function ajax_get_credits() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array('credits' => $credits));
    }

    /**
     * AJAX: Regenerate image with new parameters
     */
    public function ajax_regenerate_image() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        // Check credits
        if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'image')) {
            wp_send_json_error(array('message' => 'Insufficient image credits. Please purchase more.'));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '1024x1024';

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Validate size
        $valid_sizes = array('1024x1024', '1024x1792', '1792x1024');
        if (!in_array($size, $valid_sizes)) {
            $size = '1024x1024';
        }

        // Validate prompt length (DALL-E 3 has limits)
        if (strlen($prompt) > 4000) {
            wp_send_json_error(array('message' => 'Prompt is too long. Please keep it under 4000 characters.'));
        }

        // Log the attempt for debugging
        error_log('Rakubun AI: Attempting to regenerate image with prompt: ' . substr($prompt, 0, 100) . '...');

        // Generate image
        $openai = new Rakubun_AI_OpenAI();
        $result = $openai->generate_image($prompt, $size);

        if (!$result['success']) {
            error_log('Rakubun AI: Image regeneration failed: ' . $result['error']);
            wp_send_json_error(array('message' => $result['error']));
        }

        // Deduct credits
        if (!Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'image', 1)) {
            wp_send_json_error(array('message' => 'Failed to deduct credits.'));
        }

        $attachment_id = 0;
        $local_url = $result['url'];

        // Always save to media library for regenerated images (since they appear in gallery)
        $save_result = $openai->save_image_to_media($result['url'], 'AI Generated Image (Regenerated)');
        if ($save_result['success']) {
            $attachment_id = $save_result['attachment_id'];
            $local_url = $save_result['url'];
        } else {
            error_log('Rakubun AI: Failed to save regenerated image to media library: ' . $save_result['error']);
            // Don't fail the whole operation, just log the error
        }

        // Log generated content
        $content_id = Rakubun_AI_Credits_Manager::log_generated_content(
            $user_id,
            'image',
            $prompt,
            '',
            $local_url,
            $attachment_id
        );

        // Get updated credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'image_url' => $local_url,
            'url' => $local_url,
            'attachment_id' => $attachment_id,
            'content_id' => $content_id,
            'credits' => $credits,
            'message' => 'Image regenerated successfully!'
        ));
    }

    /**
     * AJAX: Get analytics data
     */
    public function ajax_get_analytics() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $analytics = Rakubun_AI_Credits_Manager::get_user_analytics($user_id);
        $recent_content = Rakubun_AI_Credits_Manager::get_recent_content($user_id, 10);

        wp_send_json_success(array(
            'analytics' => $analytics,
            'recent_content' => $recent_content
        ));
    }
}
