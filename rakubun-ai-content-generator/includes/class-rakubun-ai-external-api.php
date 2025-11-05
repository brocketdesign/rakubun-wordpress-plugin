<?php
/**
 * Handles communication with external Rakubun admin dashboard
 */
class Rakubun_AI_External_API {

    /**
     * Base URL for external API
     */
    private $base_url = 'https://app.rakubun.com/api/v1';

    /**
     * Plugin instance ID (unique identifier for this WordPress installation)
     */
    private $instance_id;

    /**
     * API token for authentication
     */
    private $api_token;

    /**
     * Constructor
     */
    public function __construct() {
        $this->instance_id = $this->get_or_create_instance_id();
        $this->api_token = get_option('rakubun_ai_api_token', '');
    }

    /**
     * Get or create unique instance ID for this WordPress installation
     */
    private function get_or_create_instance_id() {
        $instance_id = get_option('rakubun_ai_instance_id');
        
        if (!$instance_id) {
            $instance_id = wp_generate_uuid4();
            update_option('rakubun_ai_instance_id', $instance_id);
        }
        
        return $instance_id;
    }

    /**
     * Register this plugin instance with external dashboard
     */
    public function register_plugin() {
        $blog_info = $this->get_blog_info();
        
        $response = $this->make_request('POST', '/plugins/register', $blog_info);
        
        if ($response && isset($response['api_token'])) {
            update_option('rakubun_ai_api_token', $response['api_token']);
            update_option('rakubun_ai_registration_status', 'registered');
            $this->api_token = $response['api_token'];
            return true;
        }
        
        update_option('rakubun_ai_registration_status', 'failed');
        return false;
    }

    /**
     * Get blog information for registration
     */
    private function get_blog_info() {
        global $wpdb;
        
        // Count posts and pages
        $post_counts = wp_count_posts();
        $page_counts = wp_count_posts('page');
        
        // Count media items
        $media_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment'");
        
        // Get plugin usage statistics
        $article_generations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rakubun_generated_content WHERE content_type = 'article'"
        );
        $image_generations = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rakubun_generated_content WHERE content_type = 'image'"
        );
        
        return array(
            'instance_id' => $this->instance_id,
            'site_url' => get_site_url(),
            'site_title' => get_bloginfo('name'),
            'admin_email' => get_option('admin_email'),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => RAKUBUN_AI_VERSION,
            'php_version' => PHP_VERSION,
            'theme' => wp_get_theme()->get('Name'),
            'timezone' => wp_timezone_string(),
            'language' => get_locale(),
            'post_count' => (int) $post_counts->publish,
            'page_count' => (int) $page_counts->publish,
            'media_count' => (int) $media_count,
            'article_generations' => (int) $article_generations ?: 0,
            'image_generations' => (int) $image_generations ?: 0,
            'activation_date' => get_option('rakubun_ai_activation_date', current_time('mysql')),
            'last_activity' => current_time('mysql')
        );
    }

    /**
     * Check user credits from external API
     */
    public function get_user_credits($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $response = $this->make_request('GET', '/users/credits', array(
            'user_email' => $user->user_email,
            'user_id' => $user_id,
            'site_url' => get_site_url()
        ));

        if ($response && isset($response['credits'])) {
            return array(
                'article_credits' => (int) $response['credits']['article_credits'],
                'image_credits' => (int) $response['credits']['image_credits'],
                'rewrite_credits' => (int) $response['credits']['rewrite_credits']
            );
        }

        return false;
    }

    /**
     * Deduct credits from external API
     */
    public function deduct_credits($user_id, $credit_type, $amount = 1) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $response = $this->make_request('POST', '/users/deduct-credits', array(
            'user_email' => $user->user_email,
            'user_id' => $user_id,
            'site_url' => get_site_url(),
            'credit_type' => $credit_type,
            'amount' => $amount
        ));

        return $response && isset($response['success']) && $response['success'];
    }

    /**
     * Get OpenAI API configuration
     */
    public function get_openai_config() {
        $response = $this->make_request('GET', '/config/openai');
        
        if ($response && isset($response['api_key'])) {
            return array(
                'api_key' => $response['api_key'],
                'model_article' => $response['model_article'] ?? 'gpt-4',
                'model_image' => $response['model_image'] ?? 'dall-e-3',
                'max_tokens' => $response['max_tokens'] ?? 2000,
                'temperature' => $response['temperature'] ?? 0.7
            );
        }

        return false;
    }

    /**
     * Get package pricing from external API
     */
    public function get_packages() {
        $response = $this->make_request('GET', '/packages');
        
        if ($response && isset($response['packages'])) {
            return $response['packages'];
        }

        return array();
    }

    /**
     * Log content generation to external API
     */
    public function log_generation($user_id, $content_type, $prompt, $result, $credits_used) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $this->make_request('POST', '/analytics/generation', array(
            'user_email' => $user->user_email,
            'user_id' => $user_id,
            'site_url' => get_site_url(),
            'content_type' => $content_type,
            'prompt' => $prompt,
            'result_length' => strlen($result),
            'credits_used' => $credits_used,
            'timestamp' => current_time('mysql')
        ));

        return true;
    }

    /**
     * Send usage analytics to external API
     */
    public function send_analytics() {
        global $wpdb;
        
        // Get usage data from last sync
        $last_sync = get_option('rakubun_ai_last_analytics_sync', '');
        $where_clause = $last_sync ? "WHERE created_at > %s" : "";
        
        $analytics_data = array();
        
        // Article generations
        $query = "SELECT user_id, prompt, LENGTH(generated_content) as content_length, created_at 
                  FROM {$wpdb->prefix}rakubun_generated_content 
                  WHERE content_type = 'article'";
        if ($last_sync) {
            $query .= " AND created_at > %s";
            $articles = $wpdb->get_results($wpdb->prepare($query, $last_sync));
        } else {
            $articles = $wpdb->get_results($query);
        }
        
        // Image generations
        $query = "SELECT user_id, prompt, created_at 
                  FROM {$wpdb->prefix}rakubun_generated_content 
                  WHERE content_type = 'image'";
        if ($last_sync) {
            $query .= " AND created_at > %s";
            $images = $wpdb->get_results($wpdb->prepare($query, $last_sync));
        } else {
            $images = $wpdb->get_results($query);
        }

        $analytics_data = array(
            'site_url' => get_site_url(),
            'sync_period' => array(
                'from' => $last_sync ?: get_option('rakubun_ai_activation_date'),
                'to' => current_time('mysql')
            ),
            'articles' => $articles,
            'images' => $images,
            'total_users' => count_users()['total_users'],
            'plugin_version' => RAKUBUN_AI_VERSION
        );

        $response = $this->make_request('POST', '/analytics/usage', $analytics_data);
        
        if ($response && isset($response['success']) && $response['success']) {
            update_option('rakubun_ai_last_analytics_sync', current_time('mysql'));
            return true;
        }

        return false;
    }

    /**
     * Check if plugin is registered and connected
     */
    public function is_connected() {
        return !empty($this->api_token) && get_option('rakubun_ai_registration_status') === 'registered';
    }

    /**
     * Test connection to external API
     */
    public function test_connection() {
        $response = $this->make_request('GET', '/health');
        return $response && isset($response['status']) && $response['status'] === 'ok';
    }

    /**
     * Make HTTP request to external API
     */
    private function make_request($method, $endpoint, $data = array()) {
        $url = $this->base_url . $endpoint;
        
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Rakubun-WordPress-Plugin/' . RAKUBUN_AI_VERSION,
            'X-Instance-ID' => $this->instance_id
        );

        if ($this->api_token) {
            $headers['Authorization'] = 'Bearer ' . $this->api_token;
        }

        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30,
            'sslverify' => true
        );

        if (!empty($data)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
            } else {
                $args['body'] = json_encode($data);
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            error_log('Rakubun API Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code >= 200 && $status_code < 300) {
            return json_decode($body, true);
        }

        error_log('Rakubun API HTTP Error: ' . $status_code . ' - ' . $body);
        return false;
    }

    /**
     * Handle webhook from external dashboard
     */
    public function handle_webhook($data) {
        // Verify webhook signature if needed
        if (!$this->verify_webhook_signature($data)) {
            return false;
        }

        switch ($data['event']) {
            case 'config_updated':
                // Refresh cached configuration
                delete_transient('rakubun_ai_openai_config');
                delete_transient('rakubun_ai_packages');
                break;
                
            case 'credits_updated':
                // Clear credit cache for specific user
                if (isset($data['user_email'])) {
                    $user = get_user_by('email', $data['user_email']);
                    if ($user) {
                        delete_transient('rakubun_ai_credits_' . $user->ID);
                    }
                }
                break;
                
            case 'plugin_disabled':
                // Disable plugin functionality
                update_option('rakubun_ai_status', 'disabled');
                break;
                
            case 'plugin_enabled':
                // Enable plugin functionality
                update_option('rakubun_ai_status', 'enabled');
                break;
        }

        return true;
    }

    /**
     * Verify webhook signature (implement based on your security requirements)
     */
    private function verify_webhook_signature($data) {
        // Implement webhook signature verification if needed
        return true;
    }

    /**
     * Schedule regular analytics sync
     */
    public static function schedule_analytics_sync() {
        if (!wp_next_scheduled('rakubun_ai_sync_analytics')) {
            wp_schedule_event(time(), 'daily', 'rakubun_ai_sync_analytics');
        }
    }

    /**
     * Clear scheduled analytics sync
     */
    public static function clear_scheduled_sync() {
        wp_clear_scheduled_hook('rakubun_ai_sync_analytics');
    }
}