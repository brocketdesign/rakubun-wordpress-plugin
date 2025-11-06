<?php
/**
 * Webhook Handler for External Dashboard
 * 
 * Receives and processes webhook events from the external dashboard
 * Examples: configuration updates, credit adjustments, plugin enable/disable
 */
class Rakubun_AI_Webhook_Handler {

    /**
     * Register webhook endpoint
     */
    public static function init() {
        // Register AJAX endpoint that doesn't require nonce
        add_action('wp_ajax_nopriv_rakubun_webhook', array(__CLASS__, 'handle_webhook'));
    }

    /**
     * Handle incoming webhook from dashboard
     */
    public static function handle_webhook() {
        // Get raw payload
        $payload = file_get_contents('php://input');
        
        // Verify signature
        $signature = isset($_SERVER['HTTP_X_RAKUBUN_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_RAKUBUN_SIGNATURE'])) : '';
        
        if (!Rakubun_AI_External_API::verify_webhook_signature($payload, $signature)) {
            status_header(401);
            wp_send_json_error(array('message' => 'Invalid signature'));
            wp_die();
        }

        // Parse JSON payload
        $data = json_decode($payload, true);
        if (!$data) {
            status_header(400);
            wp_send_json_error(array('message' => 'Invalid JSON'));
            wp_die();
        }

        // Process webhook based on event type
        $event = $data['event'] ?? null;
        $result = false;

        switch ($event) {
            case 'config_updated':
                $result = self::handle_config_updated($data);
                break;
                
            case 'credits_updated':
                $result = self::handle_credits_updated($data);
                break;
                
            case 'plugin_disabled':
                $result = self::handle_plugin_disabled($data);
                break;
                
            case 'plugin_enabled':
                $result = self::handle_plugin_enabled($data);
                break;
                
            case 'package_updated':
                $result = self::handle_package_updated($data);
                break;
                
            case 'test_webhook':
                $result = true; // Just return success for test
                break;
                
            default:
                status_header(400);
                wp_send_json_error(array('message' => 'Unknown event'));
                wp_die();
        }

        if ($result) {
            wp_send_json_success(array('message' => 'Webhook processed'));
        } else {
            status_header(500);
            wp_send_json_error(array('message' => 'Failed to process webhook'));
        }

        wp_die();
    }

    /**
     * Handle configuration update from dashboard
     * 
     * Dashboard has updated OpenAI config or other settings
     * Clear cache to force refresh from dashboard
     */
    private static function handle_config_updated($data) {
        // Clear cached configuration
        delete_transient('rakubun_ai_openai_config_cache');
        delete_transient('rakubun_ai_packages_cache');
        
        error_log('Rakubun AI: Configuration updated via webhook');
        return true;
    }

    /**
     * Handle user credits update from dashboard
     * 
     * Dashboard has updated credits (manual adjustment, refund, bonus, etc.)
     * Clear user's credit cache to force refresh
     */
    private static function handle_credits_updated($data) {
        if (empty($data['data']['user_email'])) {
            return false;
        }

        $user = get_user_by('email', $data['data']['user_email']);
        if ($user) {
            // Clear credit cache for this user
            $cache_key = 'rakubun_ai_credits_' . $user->ID;
            delete_transient($cache_key);
            
            error_log('Rakubun AI: Credits updated for user ' . $user->user_email . ' via webhook');
        }

        return true;
    }

    /**
     * Handle plugin disabled by dashboard admin
     * 
     * Dashboard admin has disabled this plugin instance
     * Stop accepting new generation requests
     */
    private static function handle_plugin_disabled($data) {
        update_option('rakubun_ai_status', 'disabled');
        update_option('rakubun_ai_disabled_reason', $data['data']['reason'] ?? 'Disabled by admin');
        update_option('rakubun_ai_disabled_at', current_time('mysql'));
        
        error_log('Rakubun AI: Plugin disabled via webhook');
        return true;
    }

    /**
     * Handle plugin re-enabled by dashboard admin
     * 
     * Dashboard admin has re-enabled this plugin instance
     * Resume accepting generation requests
     */
    private static function handle_plugin_enabled($data) {
        update_option('rakubun_ai_status', 'enabled');
        delete_option('rakubun_ai_disabled_reason');
        delete_option('rakubun_ai_disabled_at');
        
        error_log('Rakubun AI: Plugin enabled via webhook');
        return true;
    }

    /**
     * Handle package update from dashboard
     * 
     * Dashboard has updated credit packages (price, quantity, etc.)
     * Clear cache to show new packages to users
     */
    private static function handle_package_updated($data) {
        delete_transient('rakubun_ai_packages_cache');
        
        error_log('Rakubun AI: Packages updated via webhook');
        return true;
    }

    /**
     * Check if plugin is disabled
     */
    public static function is_plugin_disabled() {
        return get_option('rakubun_ai_status') === 'disabled';
    }

    /**
     * Get plugin disabled reason
     */
    public static function get_disabled_reason() {
        return get_option('rakubun_ai_disabled_reason', 'Unknown');
    }
}

// Initialize webhook handler
Rakubun_AI_Webhook_Handler::init();
