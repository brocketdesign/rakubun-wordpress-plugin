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
        
        // Enqueue Stripe.js library (required for card element and payment confirmation)
        wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/', array(), null, false);
        
        // Enqueue plugin script
        wp_enqueue_script($this->plugin_name, RAKUBUN_AI_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'stripe-js'), $version, false);
        
        // Force no-cache headers for admin pages
        if (is_admin()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
        
        // Initialize external API to check if plugin is connected
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();
        
        // Get Stripe public key - first try external dashboard, then local config
        $stripe_public_key = '';
        if ($external_api->is_connected()) {
            $dashboard_key = $external_api->get_stripe_config();
            if ($dashboard_key) {
                $stripe_public_key = $dashboard_key;
            }
        }
        
        // Fallback to local config if not from dashboard
        if (empty($stripe_public_key)) {
            $stripe_public_key = get_option('rakubun_ai_stripe_public_key', '');
        }
        
        // Localize script with data
        wp_localize_script($this->plugin_name, 'rakubunAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rakubun_ai_nonce'),
            'stripe_public_key' => $stripe_public_key,
            'is_connected' => $external_api->is_connected()
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
            '自動リライト',
            '自動リライト',
            'edit_posts',
            'rakubun-ai-auto-rewrite',
            array($this, 'display_auto_rewrite_page')
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
        try {
            $user_id = get_current_user_id();
            
            // Verify database tables exist before proceeding
            $this->ensure_database_tables();
            
            // Get data with error handling
            $credits = $this->get_credits_safely($user_id);
            $analytics = $this->get_analytics_safely($user_id);
            $recent_content = $this->get_recent_content_safely($user_id, 5);
            $user_images = $this->get_user_images_safely($user_id, 20);
            
            include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/dashboard.php';
        } catch (Exception $e) {
            // Log the error and show a user-friendly message
            error_log('Rakubun AI Dashboard Error: ' . $e->getMessage());
            $this->display_dashboard_error();
        }
    }

    /**
     * Display generate article page
     */
    public function display_generate_article_page() {
        try {
            $user_id = get_current_user_id();
            $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
            
            include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/generate-article.php';
        } catch (Exception $e) {
            error_log('Rakubun AI Generate Article Error: ' . $e->getMessage());
            echo '<div class="wrap"><h1>記事生成</h1><div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    /**
     * Display generate image page
     */
    public function display_generate_image_page() {
        try {
            $user_id = get_current_user_id();
            $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
            
            include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/generate-image.php';
        } catch (Exception $e) {
            error_log('Rakubun AI Generate Image Error: ' . $e->getMessage());
            echo '<div class="wrap"><h1>画像生成</h1><div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div></div>';
        }
    }

    /**
     * Display auto rewrite page
     */
    public function display_auto_rewrite_page() {
        try {
            $user_id = get_current_user_id();
            
            // Verify database tables exist before proceeding
            $this->ensure_database_tables();
            
            // Get data with error handling
            $credits = $this->get_credits_safely($user_id);
            $rewrite_stats = $this->get_rewrite_statistics_safely($user_id);
            $total_posts = wp_count_posts('post')->publish;
            $rewrite_schedule = get_option('rakubun_ai_rewrite_schedule', array());
            
            include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/auto-rewrite.php';
        } catch (Exception $e) {
            // Log the error and show a user-friendly message
            error_log('Rakubun AI Auto Rewrite Page Error: ' . $e->getMessage());
            echo '<div class="wrap"><h1>Auto Rewrite</h1><div class="notice notice-error"><p>There was an error loading the auto rewrite page. Please try again later.</p></div></div>';
        }
    }

    /**
     * Display purchase page
     */
    public function display_purchase_page() {
        try {
            $user_id = get_current_user_id();
            $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
            
            // Initialize rewrite packages with defaults
        $rewrite_packages = array(
            'basic' => array(
                'name' => 'ベーシック',
                'rewrites' => 100,
                'price' => 9800,
                'per_rewrite' => 98,
                'suitable_for' => '小〜中規模サイト向け',
                'popular' => false
            ),
            'premium' => array(
                'name' => 'プレミアム',
                'rewrites' => 500,
                'price' => 39800,
                'per_rewrite' => 80,
                'suitable_for' => '中〜大規模サイト向け',
                'discount' => '20%割引',
                'popular' => true
            ),
            'enterprise' => array(
                'name' => 'エンタープライズ',
                'rewrites' => 2000,
                'price' => 129800,
                'per_rewrite' => 65,
                'suitable_for' => '大規模サイト向け',
                'discount' => '33%割引',
                'popular' => false
            )
        );
            
            include RAKUBUN_AI_PLUGIN_DIR . 'admin/partials/purchase.php';
        } catch (Exception $e) {
            error_log('Rakubun AI Purchase Page Error: ' . $e->getMessage());
            echo '<div class="wrap"><h1>クレジット購入</h1><div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div></div>';
        }
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
        $language = isset($_POST['language']) ? sanitize_text_field($_POST['language']) : 'ja';
        $content_length = isset($_POST['content_length']) ? sanitize_text_field($_POST['content_length']) : 'medium';
        $tone = isset($_POST['tone']) ? sanitize_text_field($_POST['tone']) : 'neutral';
        $focus_keywords = isset($_POST['focus_keywords']) ? sanitize_textarea_field($_POST['focus_keywords']) : '';
        $create_post = isset($_POST['create_post']) && ($_POST['create_post'] === 'true' || $_POST['create_post'] === '1');
        $generate_tags = isset($_POST['generate_tags']) && ($_POST['generate_tags'] === 'true' || $_POST['generate_tags'] === '1');
        $categories = isset($_POST['categories']) ? array_map('intval', (array)$_POST['categories']) : array();

        if (empty($prompt)) {
            wp_send_json_error(array('message' => 'Prompt is required.'));
        }

        // Map content length to max_tokens
        $max_tokens_map = array(
            'short' => 800,
            'medium' => 1500,
            'long' => 2500
        );
        $max_tokens = isset($max_tokens_map[$content_length]) ? $max_tokens_map[$content_length] : 1500;

        // Enhance prompt with tone and focus keywords
        $enhanced_prompt = $prompt;
        
        // Add tone instruction
        $tone_instructions = array(
            'formal' => '\nトーン: フォーマルでプロフェッショナルな言語を使用してください。',
            'trustworthy' => '\nトーン: 権威的で信頼できる、専門的な知識を示す言語を使用してください。',
            'friendly' => '\nトーン: 親しみやすく会話的な言語を使用してください。',
            'witty' => '\nトーン: 会話的で楽しく、ユーモアを交えた言語を使用してください。'
        );
        
        if (isset($tone_instructions[$tone])) {
            $enhanced_prompt .= $tone_instructions[$tone];
        }
        
        // Add focus keywords instruction if provided
        if (!empty($focus_keywords)) {
            $enhanced_prompt .= '\n\nフォーカスキーワード: ' . $focus_keywords . '\n記事に自然に組み込んでください。';
        }

        // Generate article
        $openai = new Rakubun_AI_OpenAI();
        $result = $openai->generate_article($enhanced_prompt, $max_tokens, $language);

        if (!$result['success']) {
            wp_send_json_error(array('message' => $result['error']));
        }

        // Generate tags if requested
        $tags = array();
        if ($generate_tags) {
            $tag_result = $openai->generate_tags(
                !empty($result['title']) ? $result['title'] : $title,
                $result['content'],
                5,
                $language
            );
            
            if ($tag_result['success']) {
                $tags = $tag_result['tags'];
            }
        }

        // Deduct credit
        Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'article', 1);

        $post_id = 0;
        // Use provided title, generated title, or fallback
        $final_title = !empty($title) ? $title : (!empty($result['title']) ? $result['title'] : 'AI Generated Article - ' . date('Y-m-d H:i:s'));
        
        // Create post if requested
        if ($create_post) {
            $post_id = wp_insert_post(array(
                'post_title' => $final_title,
                'post_content' => $result['content'],
                'post_status' => 'draft',
                'post_author' => $user_id
            ));

            // Assign categories if post was created
            if ($post_id && !empty($categories)) {
                wp_set_post_categories($post_id, $categories, false);
            }

            // Assign tags if post was created
            if ($post_id && !empty($tags)) {
                wp_set_post_terms($post_id, $tags, 'post_tag', false);
            }
        }

        // Log the generation
        Rakubun_AI_Credits_Manager::log_generated_content($user_id, 'article', $prompt, $result['content'], '', $post_id, 0);

        // Get updated credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);

        wp_send_json_success(array(
            'content' => $result['content'],
            'title' => $final_title,
            'post_id' => $post_id,
            'tags' => $tags,
            'categories' => $categories,
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

        // Initialize external API
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();

        if (!$external_api->is_connected()) {
            wp_send_json_error(array('message' => 'Plugin is not registered with dashboard.'));
        }

        // Get user email for API call
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found.'));
        }

        // Determine package ID and credit type from the requested type
        $package_mapping = array(
            'articles' => array('package_id' => 'article_starter', 'credit_type' => 'article'),
            'images' => array('package_id' => 'image_starter', 'credit_type' => 'image'),
            'rewrite_basic' => array('package_id' => 'rewrite_starter', 'credit_type' => 'rewrite'),
            'rewrite_premium' => array('package_id' => 'rewrite_pro', 'credit_type' => 'rewrite'),
            'rewrite_enterprise' => array('package_id' => 'rewrite_business', 'credit_type' => 'rewrite'),
        );

        // Handle legacy package names
        $package_mapping['article_standard'] = array('package_id' => 'article_starter', 'credit_type' => 'article');
        $package_mapping['image_standard'] = array('package_id' => 'image_starter', 'credit_type' => 'image');

        if (!isset($package_mapping[$credit_type])) {
            wp_send_json_error(array('message' => 'Invalid credit type.'));
        }

        $package_info = $package_mapping[$credit_type];
        
        // Get package details from external API to get the amount
        $packages_response = $external_api->get_packages();
        if (!$packages_response || empty($packages_response['packages'])) {
            wp_send_json_error(array('message' => 'Unable to fetch packages from dashboard.'));
        }

        // Find the package and get its price
        $amount = null;
        $package_id = null;
        foreach ($packages_response['packages'] as $pkg) {
            if ((!empty($pkg['id']) && $pkg['id'] === $package_info['package_id']) || 
                (!empty($pkg['package_id']) && $pkg['package_id'] === $package_info['package_id'])) {
                $amount = intval($pkg['price']);
                $package_id = $pkg['id'] ?? $pkg['package_id'];
                break;
            }
        }

        // Fallback to local config if package not found in external API
        if ($amount === null) {
            if ($package_info['credit_type'] === 'article') {
                $amount = intval(get_option('rakubun_ai_article_price', 750));
                $package_id = 'article_starter';
            } elseif ($package_info['credit_type'] === 'image') {
                $amount = intval(get_option('rakubun_ai_image_price', 300));
                $package_id = 'image_starter';
            } elseif ($package_info['credit_type'] === 'rewrite') {
                // Fallback rewrite prices
                $rewrite_prices = array(
                    'rewrite_basic' => 9800,
                    'rewrite_premium' => 39800,
                    'rewrite_enterprise' => 129800
                );
                $amount = $rewrite_prices[$credit_type] ?? 9800;
                $package_id = $package_info['package_id'];
            }
        }

        // Create payment intent via external dashboard API
        $result = $external_api->create_payment_intent(
            $user_id,
            $package_info['credit_type'],
            $package_id,
            $amount
        );

        if (!$result) {
            wp_send_json_error(array('message' => 'Failed to create payment intent. Please try again.'));
        }

        wp_send_json_success(array(
            'client_secret' => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id'],
            'amount' => $result['amount'],
            'currency' => $result['currency']
        ));
    }

    /**
     * AJAX: Create Stripe Checkout Session
     * Creates a session for Stripe Checkout (professional hosted checkout)
     */
    public function ajax_create_checkout_session() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $package_id = isset($_POST['package_id']) ? sanitize_text_field($_POST['package_id']) : '';
        $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 0;

        if (empty($package_id) || $amount <= 0) {
            wp_send_json_error(array('message' => 'Invalid request parameters.'));
        }

        // Initialize external API
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();

        if (!$external_api->is_connected()) {
            wp_send_json_error(array('message' => 'Plugin is not registered with dashboard.'));
        }

        // Get user info
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error(array('message' => 'User not found.'));
        }

        // Determine credit type from package_id
        $credit_type = 'article'; // default
        if (strpos($package_id, 'image') !== false) {
            $credit_type = 'image';
        } elseif (strpos($package_id, 'rewrite') !== false) {
            $credit_type = 'rewrite';
        }

        // Get package details
        $packages_response = $external_api->get_packages();
        if (!$packages_response) {
            wp_send_json_error(array('message' => 'Unable to fetch packages from dashboard.'));
        }

        // Find package in the grouped response
        $package_data = null;
        $credit_type_map = array(
            'article' => 'articles',
            'image' => 'images',
            'rewrite' => 'rewrites'
        );
        
        $category_key = $credit_type_map[$credit_type] ?? 'articles';
        if (isset($packages_response[$category_key])) {
            foreach ($packages_response[$category_key] as $pkg) {
                if (($pkg['package_id'] ?? null) === $package_id) {
                    $package_data = $pkg;
                    break;
                }
            }
        }

        if (!$package_data) {
            wp_send_json_error(array('message' => 'Package not found.'));
        }

        // Prepare success and cancel URLs
        $success_url = add_query_arg(array(
            'page' => 'rakubun-ai-purchase',
            'session_id' => '{CHECKOUT_SESSION_ID}',
            'status' => 'success'
        ), admin_url('admin.php'));

        $cancel_url = add_query_arg(array(
            'page' => 'rakubun-ai-purchase',
            'status' => 'cancel'
        ), admin_url('admin.php'));

        // Prepare checkout session data for dashboard API
        $checkout_data = array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'credit_type' => $credit_type,
            'package_id' => $package_id,
            'amount' => intval($amount),
            'currency' => $package_data['currency'] ?? 'JPY',
            'return_url' => $success_url,
            'cancel_url' => $cancel_url
        );

        // Make request to external dashboard to create checkout session
        $api_token = get_option('rakubun_ai_api_token');
        $instance_id = get_option('rakubun_ai_instance_id');

        if (empty($api_token) || empty($instance_id)) {
            wp_send_json_error(array('message' => 'Plugin credentials not configured.'));
        }

        $response = wp_remote_post(
            'https://app.rakubun.com/api/v1/checkout/sessions',
            array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_token,
                    'Content-Type' => 'application/json',
                    'X-Instance-ID' => $instance_id
                ),
                'body' => wp_json_encode($checkout_data),
                'timeout' => 15,
                'sslverify' => true
            )
        );

        if (is_wp_error($response)) {
            error_log('Rakubun Checkout Error: ' . $response->get_error_message());
            wp_send_json_error(array('message' => 'Failed to create checkout session: ' . $response->get_error_message()));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        error_log('Rakubun Checkout Response: ' . wp_json_encode($body));

        if ($status_code !== 200 || empty($body['success'])) {
            wp_send_json_error(array(
                'message' => $body['message'] ?? 'Failed to create checkout session.'
            ));
        }

        // Dashboard returns 'url' not 'checkout_url'
        $checkout_url = $body['url'] ?? $body['checkout_url'] ?? null;
        
        if (empty($checkout_url)) {
            wp_send_json_error(array('message' => 'No checkout URL provided by dashboard.'));
        }

        wp_send_json_success(array(
            'checkout_url' => $checkout_url
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

        // Initialize external API
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();

        if (!$external_api->is_connected()) {
            wp_send_json_error(array('message' => 'Plugin is not registered with dashboard.'));
        }

        // Determine credit type for API call (rewrite_basic -> rewrite, etc.)
        $api_credit_type = 'article';
        if (strpos($credit_type, 'image') !== false) {
            $api_credit_type = 'image';
        } elseif (strpos($credit_type, 'rewrite') !== false) {
            $api_credit_type = 'rewrite';
        }

        // Confirm payment with external dashboard API
        $result = $external_api->confirm_payment($payment_intent_id, $user_id, $api_credit_type);

        if (!$result) {
            wp_send_json_error(array('message' => 'Payment confirmation failed. Please try again.'));
        }

        // Get updated credits from dashboard
        $credits = $external_api->get_user_credits($user_id);
        
        if (!$credits) {
            // Fallback to local credits if dashboard doesn't have them
            $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        }

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

    /**
     * Ensure database tables exist
     */
    private function ensure_database_tables() {
        global $wpdb;
        
        $tables_to_check = array(
            $wpdb->prefix . 'rakubun_user_credits',
            $wpdb->prefix . 'rakubun_transactions',
            $wpdb->prefix . 'rakubun_generated_content',
            $wpdb->prefix . 'rakubun_rewrite_history'
        );
        
        foreach ($tables_to_check as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                // Trigger plugin activation to create missing tables
                require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-activator.php';
                Rakubun_AI_Activator::activate();
                break;
            }
        }
    }

    /**
     * Safely get user credits with fallback
     */
    private function get_credits_safely($user_id) {
        try {
            return Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        } catch (Exception $e) {
            error_log('Rakubun AI Credits Error: ' . $e->getMessage());
            // Re-throw the exception so dashboard can display error
            throw $e;
        }
    }

    /**
     * Safely get analytics with fallback
     */
    private function get_analytics_safely($user_id) {
        try {
            return Rakubun_AI_Credits_Manager::get_user_analytics($user_id);
        } catch (Exception $e) {
            error_log('Rakubun AI Analytics Error: ' . $e->getMessage());
            return array(
                'total_articles' => 0,
                'total_images' => 0,
                'recent_articles' => 0,
                'recent_images' => 0,
                'total_spent' => 0,
                'monthly_usage' => array()
            );
        }
    }

    /**
     * Safely get recent content with fallback
     */
    private function get_recent_content_safely($user_id, $limit) {
        try {
            return Rakubun_AI_Credits_Manager::get_recent_content($user_id, $limit);
        } catch (Exception $e) {
            error_log('Rakubun AI Recent Content Error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Safely get user images with fallback
     */
    private function get_user_images_safely($user_id, $limit) {
        try {
            return Rakubun_AI_Credits_Manager::get_user_images($user_id, $limit);
        } catch (Exception $e) {
            error_log('Rakubun AI User Images Error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Safely get rewrite statistics with fallback
     */
    private function get_rewrite_statistics_safely($user_id) {
        try {
            return Rakubun_AI_Credits_Manager::get_rewrite_statistics($user_id);
        } catch (Exception $e) {
            error_log('Rakubun AI Rewrite Statistics Error: ' . $e->getMessage());
            return array(
                'total_rewrites' => 0,
                'characters_added' => 0,
                'seo_improvements' => 0,
                'recent_rewrites' => array()
            );
        }
    }

    /**
     * Display error page when dashboard fails
     */
    private function display_dashboard_error() {
        ?>
        <div class="wrap rakubun-ai-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="notice notice-error">
                <p><strong>Erreur:</strong> Il y a eu un problème lors du chargement du tableau de bord. Les tables de base de données peuvent être manquantes ou corrompues.</p>
                <p>Veuillez essayer de désactiver et réactiver le plugin pour corriger ce problème.</p>
            </div>
            
            <div class="rakubun-quick-actions">
                <h2>Actions Rapides</h2>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-article'); ?>" class="button button-primary button-large">
                        📝 Générer un article
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=rakubun-ai-generate-image'); ?>" class="button button-primary button-large">
                        🎨 Générer une image
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=rakubun-ai-settings'); ?>" class="button button-secondary button-large">
                        ⚙️ Paramètres
                    </a>
                </div>
            </div>
            
            <div class="rakubun-info-section">
                <h2>Résolution des problèmes</h2>
                <p>Si vous continuez à voir cette erreur, veuillez:</p>
                <ol>
                    <li>Désactiver le plugin depuis la page des plugins</li>
                    <li>Réactiver le plugin pour créer les tables de base de données</li>
                    <li>Vérifier que votre site WordPress a les permissions nécessaires pour créer des tables de base de données</li>
                    <li>Contacter le support si le problème persiste</li>
                </ol>
            </div>
        </div>
        <?php
    }
}
