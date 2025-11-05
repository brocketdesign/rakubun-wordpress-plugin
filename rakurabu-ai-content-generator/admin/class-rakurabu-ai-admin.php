<?php
/**
 * The admin-specific functionality of the plugin
 */
class Rakurabu_AI_Admin {

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
        wp_enqueue_style($this->plugin_name, RAKURABU_AI_PLUGIN_URL . 'assets/css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, RAKURABU_AI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), $this->version, false);
        
        // Localize script with data
        wp_localize_script($this->plugin_name, 'rakurabuAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rakurabu_ai_nonce'),
            'stripe_public_key' => get_option('rakurabu_ai_stripe_public_key', '')
        ));
    }

    /**
     * Add menu items
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'Rakurabu AI Content Generator',
            'AI Content',
            'edit_posts',
            'rakurabu-ai-content',
            array($this, 'display_dashboard_page'),
            'dashicons-edit-page',
            30
        );

        add_submenu_page(
            'rakurabu-ai-content',
            'Dashboard',
            'Dashboard',
            'edit_posts',
            'rakurabu-ai-content',
            array($this, 'display_dashboard_page')
        );

        add_submenu_page(
            'rakurabu-ai-content',
            'Generate Article',
            'Generate Article',
            'edit_posts',
            'rakurabu-ai-generate-article',
            array($this, 'display_generate_article_page')
        );

        add_submenu_page(
            'rakurabu-ai-content',
            'Generate Image',
            'Generate Image',
            'edit_posts',
            'rakurabu-ai-generate-image',
            array($this, 'display_generate_image_page')
        );

        add_submenu_page(
            'rakurabu-ai-content',
            'Purchase Credits',
            'Purchase Credits',
            'edit_posts',
            'rakurabu-ai-purchase',
            array($this, 'display_purchase_page')
        );

        add_submenu_page(
            'rakurabu-ai-content',
            'Settings',
            'Settings',
            'manage_options',
            'rakurabu-ai-settings',
            array($this, 'display_settings_page')
        );
    }

    /**
     * Display dashboard page
     */
    public function display_dashboard_page() {
        $user_id = get_current_user_id();
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKURABU_AI_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Display generate article page
     */
    public function display_generate_article_page() {
        $user_id = get_current_user_id();
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKURABU_AI_PLUGIN_DIR . 'admin/partials/generate-article.php';
    }

    /**
     * Display generate image page
     */
    public function display_generate_image_page() {
        $user_id = get_current_user_id();
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKURABU_AI_PLUGIN_DIR . 'admin/partials/generate-image.php';
    }

    /**
     * Display purchase page
     */
    public function display_purchase_page() {
        $user_id = get_current_user_id();
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);
        
        include RAKURABU_AI_PLUGIN_DIR . 'admin/partials/purchase.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        // Handle settings save
        if (isset($_POST['rakurabu_ai_save_settings']) && check_admin_referer('rakurabu_ai_settings', 'rakurabu_ai_settings_nonce')) {
            update_option('rakurabu_ai_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            update_option('rakurabu_ai_stripe_public_key', sanitize_text_field($_POST['stripe_public_key']));
            update_option('rakurabu_ai_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key']));
            update_option('rakurabu_ai_article_price', floatval($_POST['article_price']));
            update_option('rakurabu_ai_image_price', floatval($_POST['image_price']));
            update_option('rakurabu_ai_articles_per_purchase', intval($_POST['articles_per_purchase']));
            update_option('rakurabu_ai_images_per_purchase', intval($_POST['images_per_purchase']));
            
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        
        include RAKURABU_AI_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * AJAX: Generate article
     */
    public function ajax_generate_article() {
        check_ajax_referer('rakurabu_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        // Check credits
        if (!Rakurabu_AI_Credits_Manager::has_credits($user_id, 'article')) {
            wp_send_json_error(array('message' => 'Insufficient article credits. Please purchase more.'));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        $create_post = isset($_POST['create_post']) && $_POST['create_post'] === 'true';

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Generate article
        $openai = new Rakurabu_AI_OpenAI();
        $result = $openai->generate_article($prompt);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        // Deduct credit
        Rakurabu_AI_Credits_Manager::deduct_credits($user_id, 'article', 1);

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
        Rakurabu_AI_Credits_Manager::log_generated_content($user_id, 'article', $prompt, $result['content'], '', $post_id);

        // Get updated credits
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);

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
        check_ajax_referer('rakurabu_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        // Check credits
        if (!Rakurabu_AI_Credits_Manager::has_credits($user_id, 'image')) {
            wp_send_json_error(array('message' => 'Insufficient image credits. Please purchase more.'));
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $size = isset($_POST['size']) ? sanitize_text_field($_POST['size']) : '1024x1024';
        $save_to_media = isset($_POST['save_to_media']) && $_POST['save_to_media'] === 'true';

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Generate image
        $openai = new Rakurabu_AI_OpenAI();
        $result = $openai->generate_image($prompt, $size);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        // Deduct credit
        Rakurabu_AI_Credits_Manager::deduct_credits($user_id, 'image', 1);

        $attachment_id = 0;
        $local_url = $result['url'];

        // Save to media library if requested
        if ($save_to_media) {
            $save_result = $openai->save_image_to_media($result['url'], 'AI Generated Image');
            if ($save_result['success']) {
                $attachment_id = $save_result['attachment_id'];
                $local_url = $save_result['url'];
            }
        }

        // Log the generation
        Rakurabu_AI_Credits_Manager::log_generated_content($user_id, 'image', $prompt, '', $local_url, $attachment_id);

        // Get updated credits
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'url' => $local_url,
            'attachment_id' => $attachment_id,
            'credits' => $credits
        ));
    }

    /**
     * AJAX: Process payment
     */
    public function ajax_process_payment() {
        check_ajax_referer('rakurabu_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $credit_type = isset($_POST['credit_type']) ? sanitize_text_field($_POST['credit_type']) : '';
        $payment_intent_id = isset($_POST['payment_intent_id']) ? sanitize_text_field($_POST['payment_intent_id']) : '';

        if (empty($credit_type) || empty($payment_intent_id)) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        // Verify payment with Stripe
        $stripe = new Rakurabu_AI_Stripe();
        $verification = $stripe->verify_payment($payment_intent_id);

        if (!$verification['success']) {
            wp_send_json_error(array('message' => 'Payment verification failed.'));
        }

        // Add credits based on type
        if ($credit_type === 'articles') {
            $credits_to_add = get_option('rakurabu_ai_articles_per_purchase', 10);
            $amount = get_option('rakurabu_ai_article_price', 5.00);
            Rakurabu_AI_Credits_Manager::add_credits($user_id, 'article', $credits_to_add);
        } else {
            $credits_to_add = get_option('rakurabu_ai_images_per_purchase', 20);
            $amount = get_option('rakurabu_ai_image_price', 2.00);
            Rakurabu_AI_Credits_Manager::add_credits($user_id, 'image', $credits_to_add);
        }

        // Log transaction
        Rakurabu_AI_Credits_Manager::log_transaction(
            $user_id,
            'purchase',
            $amount,
            $credits_to_add,
            $credit_type,
            $payment_intent_id,
            'completed'
        );

        // Get updated credits
        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'message' => 'Credits added successfully!',
            'credits' => $credits
        ));
    }

    /**
     * AJAX: Get credits
     */
    public function ajax_get_credits() {
        check_ajax_referer('rakurabu_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $credits = Rakurabu_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array('credits' => $credits));
    }
}
