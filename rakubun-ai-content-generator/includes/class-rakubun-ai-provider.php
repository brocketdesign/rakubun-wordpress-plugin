<?php
/**
 * AI Provider Manager
 * 
 * Handles provider-specific settings and configurations
 */
class Rakubun_AI_Provider {

    /**
     * Available providers with their configurations
     */
    private static $providers = array(
        'openai' => array(
            'name' => 'OpenAI',
            'base_url' => 'https://api.openai.com/v1',
            'description' => 'GPT-4, DALL-E-3',
            'models' => array(
                'article' => 'gpt-4',
                'image' => 'dall-e-3'
            ),
            'supports_images' => true,
            'supports_chat' => true
        ),
        'novita' => array(
            'name' => 'Novita AI',
            'base_url' => 'https://api.novita.ai/openai/v1',
            'description' => 'DeepSeek, Llama, and more',
            'models' => array(
                'article' => 'deepseek/deepseek-r1',
                'image' => 'dall-e-3'  // Novita also supports DALL-E-3
            ),
            'supports_images' => true,
            'supports_chat' => true
        )
    );

    /**
     * Get all available providers
     */
    public static function get_providers() {
        return self::$providers;
    }

    /**
     * Get specific provider configuration
     */
    public static function get_provider($provider_name) {
        return isset(self::$providers[$provider_name]) ? self::$providers[$provider_name] : null;
    }

    /**
     * Get current provider name
     */
    public static function get_current_provider() {
        return get_option('rakubun_ai_api_provider', 'openai');
    }

    /**
     * Get current provider configuration
     */
    public static function get_current_provider_config() {
        $provider = self::get_current_provider();
        return self::get_provider($provider);
    }

    /**
     * Get provider base URL
     */
    public static function get_provider_base_url($provider_name = null) {
        if ($provider_name === null) {
            $provider_name = self::get_current_provider();
        }
        
        $provider = self::get_provider($provider_name);
        return $provider ? $provider['base_url'] : null;
    }

    /**
     * Get default model for provider
     */
    public static function get_default_model($provider_name = null, $type = 'article') {
        if ($provider_name === null) {
            $provider_name = self::get_current_provider();
        }
        
        $provider = self::get_provider($provider_name);
        return isset($provider['models'][$type]) ? $provider['models'][$type] : null;
    }

    /**
     * Check if provider supports a feature
     */
    public static function supports($feature, $provider_name = null) {
        if ($provider_name === null) {
            $provider_name = self::get_current_provider();
        }
        
        $provider = self::get_provider($provider_name);
        if (!$provider) {
            return false;
        }
        
        $feature_key = 'supports_' . $feature;
        return isset($provider[$feature_key]) ? $provider[$feature_key] : false;
    }

    /**
     * Set provider
     */
    public static function set_provider($provider_name) {
        if (isset(self::$providers[$provider_name])) {
            update_option('rakubun_ai_api_provider', $provider_name);
            // Clear cache to force configuration reload
            delete_transient('rakubun_ai_api_config');
            return true;
        }
        return false;
    }

    /**
     * Get provider display name
     */
    public static function get_provider_display_name($provider_name = null) {
        if ($provider_name === null) {
            $provider_name = self::get_current_provider();
        }
        
        $provider = self::get_provider($provider_name);
        return $provider ? $provider['name'] : 'Unknown';
    }

    /**
     * Get provider description
     */
    public static function get_provider_description($provider_name = null) {
        if ($provider_name === null) {
            $provider_name = self::get_current_provider();
        }
        
        $provider = self::get_provider($provider_name);
        return $provider ? $provider['description'] : '';
    }

    /**
     * Validate provider name
     */
    public static function is_valid_provider($provider_name) {
        return isset(self::$providers[$provider_name]);
    }
}
