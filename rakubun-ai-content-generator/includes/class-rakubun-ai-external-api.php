<?php
/**
 * External Dashboard API Client
 * 
 * Communicates with external dashboard at app.rakubun.com
 * Dashboard is the single source of truth for credits and payments
 */
class Rakubun_AI_External_API {

    private $base_url = 'https://app.rakubun.com/api/v1';
    private $instance_id;
    private $api_token;
    private $site_url;
    private $plugin_version;

    public function __construct() {
        $this->instance_id = get_option('rakubun_ai_instance_id');
        $this->api_token = get_option('rakubun_ai_api_token');
        $this->site_url = get_site_url();
        $this->plugin_version = defined('RAKUBUN_AI_VERSION') ? RAKUBUN_AI_VERSION : '1.0.0';
    }

    public function is_connected() {
        return !empty($this->api_token) && !empty($this->instance_id);
    }

    public function test_connection() {
        if (!$this->is_connected()) {
            return false;
        }

        // Try to fetch packages as a simple connection test
        $response = $this->make_request('GET', '/packages');
        return !empty($response['success']);
    }

    public function register_plugin() {
        if (empty($this->instance_id)) {
            $this->instance_id = wp_generate_uuid4();
            update_option('rakubun_ai_instance_id', $this->instance_id);
        }

        $data = array(
            'instance_id' => $this->instance_id,
            'site_url' => $this->site_url,
            'site_title' => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => $this->plugin_version,
            'php_version' => phpversion(),
            'theme' => wp_get_theme()->get('Name'),
            'timezone' => wp_timezone_string(),
            'language' => get_locale(),
            'post_count' => intval(wp_count_posts()->publish ?? 0),
            'page_count' => intval(wp_count_posts('page')->publish ?? 0),
            'activation_date' => current_time('mysql'),
            'last_activity' => current_time('mysql')
        );

        $response = $this->make_request('POST', '/plugins/register', $data, false);

        if (!empty($response['success']) && !empty($response['api_token'])) {
            $this->api_token = $response['api_token'];
            update_option('rakubun_ai_api_token', $this->api_token);
            update_option('rakubun_ai_instance_id', $this->instance_id);
            if (!empty($response['webhook_secret'])) {
                update_option('rakubun_ai_webhook_secret', $response['webhook_secret']);
            }
            error_log('Rakubun AI: Registered with dashboard. Instance: ' . $this->instance_id);
            return true;
        }

        error_log('Rakubun AI: Registration failed: ' . wp_json_encode($response));
        return false;
    }

    public function get_user_credits($user_id) {
        if (!$this->is_connected()) {
            error_log('Rakubun: Not connected to external API');
            return null;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            error_log('Rakubun: User not found: ' . $user_id);
            return null;
        }

        error_log('Rakubun: Fetching credits for user ' . $user_id . ' (' . $user->user_email . ')');
        
        $response = $this->make_request('GET', '/users/credits', array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'site_url' => $this->site_url
        ));

        error_log('Rakubun: API Response for /users/credits: ' . wp_json_encode($response));

        if (!empty($response['success']) && !empty($response['credits'])) {
            $credits = array(
                'article_credits' => intval($response['credits']['article_credits'] ?? 0),
                'image_credits' => intval($response['credits']['image_credits'] ?? 0),
                'rewrite_credits' => intval($response['credits']['rewrite_credits'] ?? 0)
            );
            error_log('Rakubun: Credits fetched successfully: ' . wp_json_encode($credits));
            return $credits;
        }

        error_log('Rakubun: API did not return credits data. Response: ' . wp_json_encode($response));
        return null;
    }

    public function deduct_credits($user_id, $credit_type, $amount = 1) {
        if (!$this->is_connected()) {
            return null;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return null;
        }

        $response = $this->make_request('POST', '/users/deduct-credits', array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'site_url' => $this->site_url,
            'credit_type' => $credit_type,
            'amount' => intval($amount)
        ));

        if (!empty($response['success'])) {
            return array(
                'remaining_credits' => $response['remaining_credits'] ?? array(),
                'transaction_id' => $response['transaction_id'] ?? null
            );
        }

        return null;
    }

    public function get_packages() {
        if (!$this->is_connected()) {
            return null;
        }

        // Always fetch fresh packages - never use cache
        // Packages contain sensitive pricing info that must always be current
        $response = $this->make_request('GET', '/packages');

        if (!empty($response['success']) && !empty($response['packages'])) {
            return $response['packages'];
        }

        return null;
    }

    /**
     * Get provider-specific configuration including API key and models
     * This is the preferred method to get complete provider configuration
     * 
     * @param string $provider The provider name (e.g., 'openai', 'novita')
     * @return array|null Configuration array with api_key, model_article, model_image, etc.
     */
    public function get_provider_config($provider = null) {
        if (!$this->is_connected()) {
            return null;
        }

        // Use provided provider or fallback to default
        $provider = !empty($provider) ? $provider : 'openai';
        
        // Don't cache provider config to ensure we always get fresh API keys
        $response = $this->make_request('GET', '/config/provider', array('provider' => $provider));

        if (!empty($response['success']) && !empty($response)) {
            $config = array(
                'api_key' => $response['api_key'] ?? '',
                'api_provider' => $response['provider'] ?? $provider,
                'model_article' => $response['model_article'] ?? '',
                'model_image' => $response['model_image'] ?? '',
                'max_tokens' => $response['max_tokens'] ?? 2000,
                'temperature' => $response['temperature'] ?? 0.7,
                'base_url' => $response['base_url'] ?? ''
            );
            
            // Log if we get valid config with API key
            if (!empty($config['api_key'])) {
                error_log('Rakubun AI: Provider config retrieved for ' . $provider . ' with API key');
            } else {
                error_log('Rakubun AI: Provider config retrieved for ' . $provider . ' but NO API key found');
            }
            
            return $config;
        }

        error_log('Rakubun AI: Failed to get provider config. Response: ' . wp_json_encode($response));
        return null;
    }

    /**
     * Get OpenAI configuration (Deprecated - use get_provider_config instead)
     * Maintained for backwards compatibility
     */
    public function get_openai_config() {
        if (!$this->is_connected()) {
            return null;
        }

        $cache_key = 'rakubun_ai_openai_config_cache';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request('GET', '/config/article');

        if (!empty($response['success']) && !empty($response['config'])) {
            // Cache for 1 hour
            set_transient($cache_key, $response['config'], HOUR_IN_SECONDS);
            return $response['config'];
        }

        return null;
    }

    public function get_image_config() {
        if (!$this->is_connected()) {
            return null;
        }

        $cache_key = 'rakubun_ai_image_config_cache';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request('GET', '/config/image');

        if (!empty($response['success']) && !empty($response['config'])) {
            // Cache for 1 hour
            set_transient($cache_key, $response['config'], HOUR_IN_SECONDS);
            return $response['config'];
        }

        return null;
    }

    public function get_rewrite_config() {
        if (!$this->is_connected()) {
            return null;
        }

        $cache_key = 'rakubun_ai_rewrite_config_cache';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request('GET', '/config/rewrite');

        if (!empty($response['success']) && !empty($response['config'])) {
            // Cache for 1 hour
            set_transient($cache_key, $response['config'], HOUR_IN_SECONDS);
            return $response['config'];
        }

        return null;
    }

    public function get_stripe_config() {
        if (!$this->is_connected()) {
            return null;
        }

        $cache_key = 'rakubun_ai_stripe_config_cache';
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $response = $this->make_request('GET', '/config/stripe');

        if (!empty($response['success']) && !empty($response['public_key'])) {
            // Cache for 24 hours since config doesn't change often
            set_transient($cache_key, $response['public_key'], DAY_IN_SECONDS);
            return $response['public_key'];
        }

        return null;
    }

    public function create_payment_intent($user_id, $credit_type, $package_id, $amount) {
        if (!$this->is_connected()) {
            return null;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return null;
        }

        $response = $this->make_request('POST', '/payments/create-intent', array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'site_url' => $this->site_url,
            'credit_type' => $credit_type,
            'package_id' => $package_id,
            'amount' => intval($amount),
            'currency' => 'JPY',
            'instance_id' => $this->instance_id
        ));

        if (!empty($response['success'])) {
            return array(
                'client_secret' => $response['client_secret'] ?? null,
                'payment_intent_id' => $response['payment_intent_id'] ?? null,
                'amount' => intval($response['amount'] ?? $amount),
                'currency' => $response['currency'] ?? 'JPY'
            );
        }

        return null;
    }

    public function confirm_payment($payment_intent_id, $user_id, $credit_type) {
        if (!$this->is_connected()) {
            return null;
        }

        $response = $this->make_request('POST', '/payments/confirm', array(
            'payment_intent_id' => $payment_intent_id,
            'user_id' => $user_id,
            'credit_type' => $credit_type,
            'site_url' => $this->site_url,
            'instance_id' => $this->instance_id
        ));

        if (!empty($response['success'])) {
            return array(
                'success' => true,
                'credits_added' => intval($response['credits_added'] ?? 0),
                'transaction_id' => $response['transaction_id'] ?? null,
                'remaining_credits' => $response['remaining_credits'] ?? array()
            );
        }

        return null;
    }

    public function log_generation($user_id, $content_type, $prompt = '', $result_length = 0, $credits_used = 1) {
        if (!$this->is_connected()) {
            return false;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $data = array(
            'user_id' => $user_id,
            'user_email' => $user->user_email,
            'site_url' => $this->site_url,
            'content_type' => $content_type,
            'prompt' => substr($prompt, 0, 500),
            'result_length' => intval($result_length),
            'credits_used' => intval($credits_used),
            'timestamp' => current_time('mysql')
        );

        wp_remote_post(
            $this->base_url . '/analytics/generation',
            array(
                'blocking' => false,
                'sslverify' => true,
                'timeout' => 5,
                'headers' => $this->get_headers(),
                'body' => wp_json_encode($data)
            )
        );

        return true;
    }

    public function send_analytics() {
        if (!$this->is_connected()) {
            return false;
        }

        global $wpdb;
        $one_hour_ago = date('Y-m-d H:i:s', time() - HOUR_IN_SECONDS);

        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        $generations = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, content_type, prompt, CHAR_LENGTH(generated_content) as result_length, created_at
            FROM $content_table
            WHERE created_at >= %s AND status = 'completed'
            LIMIT 200
        ", $one_hour_ago));

        $trans_table = $wpdb->prefix . 'rakubun_transactions';
        $transactions = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, transaction_type, credit_type, credits_purchased as amount, created_at
            FROM $trans_table
            WHERE created_at >= %s AND status = 'completed'
            LIMIT 100
        ", $one_hour_ago));

        $data = array(
            'site_url' => $this->site_url,
            'instance_id' => $this->instance_id,
            'sync_period' => array(
                'from' => $one_hour_ago,
                'to' => current_time('mysql')
            ),
            'generations' => $generations ?: array(),
            'transactions' => $transactions ?: array(),
            'total_users' => count_users()['total_users'] ?? 0,
            'plugin_version' => $this->plugin_version
        );

        $response = $this->make_request('POST', '/analytics/usage', $data);

        if (!empty($response['success'])) {
            update_option('rakubun_ai_last_sync', current_time('mysql'));
            return true;
        }

        return false;
    }

    public static function schedule_analytics_sync() {
        if (!wp_next_scheduled('rakubun_ai_sync_analytics')) {
            wp_schedule_event(time(), 'hourly', 'rakubun_ai_sync_analytics');
        }
    }

    private function get_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->api_token,
            'Content-Type' => 'application/json',
            'X-Instance-ID' => $this->instance_id,
            'User-Agent' => 'Rakubun-WordPress-Plugin/' . $this->plugin_version
        );
    }

    private function make_request($method, $endpoint, $data = array(), $require_auth = true) {
        $url = $this->base_url . $endpoint;

        $args = array(
            'method' => $method,
            'timeout' => 15,
            'sslverify' => true
        );

        if ($require_auth && $this->is_connected()) {
            $args['headers'] = $this->get_headers();
        } else {
            $args['headers'] = array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'Rakubun-WordPress-Plugin/' . $this->plugin_version
            );
        }

        if ($method === 'GET') {
            if (!empty($data)) {
                $url = add_query_arg($data, $url);
            }
        } else {
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('Rakubun API Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status_code >= 400) {
            error_log('Rakubun API Error [' . $status_code . ']: ' . $body);
        }

        return $decoded ?: array('success' => false);
    }

    public static function verify_webhook_signature($payload, $signature) {
        $secret = get_option('rakubun_ai_webhook_secret');
        if (empty($secret)) {
            return false;
        }
        $hash = hash_hmac('sha256', $payload, $secret);
        return hash_equals($hash, $signature);
    }

    /**
     * Development Test: Article Configuration
     * Tests if article generation configuration is available from dashboard
     * Does NOT generate any articles
     */
    public function test_article_configuration() {
        if (!$this->is_connected()) {
            return array(
                'success' => false,
                'error' => 'not_connected',
                'message' => 'Plugin is not connected to dashboard'
            );
        }

        $response = $this->make_request('GET', '/config/article');
        
        if (!$response || empty($response['success'])) {
            return array(
                'success' => false,
                'error' => 'api_error',
                'message' => 'Failed to fetch article configuration',
                'response' => $response
            );
        }

        return array(
            'success' => true,
            'message' => 'Article configuration retrieved successfully',
            'config' => $response['config'] ?? array(),
            'models' => $response['models'] ?? array(),
            'has_api_key' => !empty($response['config']['api_key']),
            'model' => $response['config']['model'] ?? 'unknown'
        );
    }

    /**
     * Development Test: Image Configuration
     * Tests if image generation configuration is available from dashboard
     * Does NOT generate any images
     */
    public function test_image_configuration() {
        if (!$this->is_connected()) {
            return array(
                'success' => false,
                'error' => 'not_connected',
                'message' => 'Plugin is not connected to dashboard'
            );
        }

        $response = $this->make_request('GET', '/config/image');
        
        if (!$response || empty($response['success'])) {
            return array(
                'success' => false,
                'error' => 'api_error',
                'message' => 'Failed to fetch image configuration',
                'response' => $response
            );
        }

        return array(
            'success' => true,
            'message' => 'Image configuration retrieved successfully',
            'config' => $response['config'] ?? array(),
            'models' => $response['models'] ?? array(),
            'has_api_key' => !empty($response['config']['api_key']),
            'model' => $response['config']['model'] ?? 'unknown'
        );
    }

    /**
     * Development Test: Rewrite Configuration
     * Tests if rewrite/auto-rewriter configuration is available from dashboard
     * Does NOT execute any rewrites
     */
    public function test_rewrite_configuration() {
        if (!$this->is_connected()) {
            return array(
                'success' => false,
                'error' => 'not_connected',
                'message' => 'Plugin is not connected to dashboard'
            );
        }

        $response = $this->make_request('GET', '/config/rewrite');
        
        if (!$response || empty($response['success'])) {
            return array(
                'success' => false,
                'error' => 'api_error',
                'message' => 'Failed to fetch rewrite configuration',
                'response' => $response
            );
        }

        return array(
            'success' => true,
            'message' => 'Rewrite configuration retrieved successfully',
            'config' => $response['config'] ?? array(),
            'models' => $response['models'] ?? array(),
            'has_api_key' => !empty($response['config']['api_key']),
            'model' => $response['config']['model'] ?? 'unknown'
        );
    }

    /**
     * Development Test: Current Model Configuration
     * Tests configuration for the currently selected API provider
     */
    public function test_current_model_configuration() {
        if (!$this->is_connected()) {
            return array(
                'success' => false,
                'error' => 'not_connected',
                'message' => 'Plugin is not connected to dashboard'
            );
        }

        $api_provider = get_option('rakubun_ai_api_provider', 'openai');
        
        // Pass the selected provider as a query parameter
        $response = $this->make_request('GET', '/config/provider', array('provider' => $api_provider));
        
        if (!$response || empty($response['success'])) {
            return array(
                'success' => false,
                'error' => 'api_error',
                'message' => 'Failed to fetch provider configuration',
                'requested_provider' => $api_provider,
                'response' => $response
            );
        }

        return array(
            'success' => true,
            'message' => 'Provider configuration retrieved successfully',
            'requested_provider' => $api_provider,
            'active_provider' => $response['provider'] ?? 'unknown',
            'provider_name' => $response['provider_name'] ?? 'Unknown',
            'config' => array(
                'api_key' => $response['api_key'] ?? 'not_available',
                'model_article' => $response['model_article'] ?? 'not_configured',
                'model_image' => $response['model_image'] ?? 'not_configured',
                'max_tokens' => $response['max_tokens'] ?? null,
                'temperature' => $response['temperature'] ?? null,
                'base_url' => $response['base_url'] ?? null
            ),
            'has_api_key' => !empty($response['api_key']),
            'api_key_preview' => !empty($response['api_key']) ? substr($response['api_key'], 0, 10) . '...' : 'not_set'
        );
    }

    /**
     * Development Test: Stripe Configuration
     * Tests if Stripe is properly configured and ready for payments
     */
    public function test_stripe_configuration() {
        if (!$this->is_connected()) {
            return array(
                'success' => false,
                'error' => 'not_connected',
                'message' => 'Plugin is not connected to dashboard'
            );
        }

        $stripe_key = $this->get_stripe_config();
        
        if (!$stripe_key) {
            return array(
                'success' => false,
                'error' => 'no_stripe_key',
                'message' => 'Stripe public key not available from dashboard'
            );
        }

        // Verify the key format (Stripe public keys start with pk_)
        $is_valid_format = strpos($stripe_key, 'pk_') === 0;
        
        return array(
            'success' => true,
            'message' => 'Stripe configuration is ready',
            'stripe_public_key' => substr($stripe_key, 0, 10) . '...' . substr($stripe_key, -4), // Masked for security
            'is_valid_format' => $is_valid_format,
            'status' => $is_valid_format ? 'ready' : 'invalid_format'
        );
    }

    /**
     * Clear all Rakubun plugin caches
     * Useful when dashboard configurations or packages have been updated
     */
    public static function clear_all_caches() {
        $cache_keys = array(
            'rakubun_ai_packages_cache',
            'rakubun_ai_openai_config_cache',
            'rakubun_ai_image_config_cache',
            'rakubun_ai_rewrite_config_cache',
            'rakubun_ai_stripe_config_cache'
        );

        foreach ($cache_keys as $key) {
            delete_transient($key);
        }

        error_log('Rakubun: All caches cleared');
        return true;
    }
}
