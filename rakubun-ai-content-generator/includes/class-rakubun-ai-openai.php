<?php
/**
 * OpenAI API integration for article and image generation
 */
class Rakubun_AI_OpenAI {

    /**
     * OpenAI configuration from external API
     */
    private $config;

    /**
     * API base URL
     */
    private $api_base = 'https://api.openai.com/v1';

    /**
     * External API instance
     */
    private $external_api;

    /**
     * Constructor
     */
    public function __construct() {
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $this->external_api = new Rakubun_AI_External_API();
        $this->config = $this->get_openai_config();
    }

    /**
     * Get OpenAI configuration from external API
     */
    private function get_openai_config() {
        // Check cache first
        $cache_key = 'rakubun_ai_openai_config';
        $config = get_transient($cache_key);
        
        if ($config === false) {
            if ($this->external_api->is_connected()) {
                $config = $this->external_api->get_openai_config();
                if ($config) {
                    // Cache for 1 hour
                    set_transient($cache_key, $config, HOUR_IN_SECONDS);
                }
            }
            
            // Fallback to local settings if external API fails
            if (!$config) {
                $config = array(
                    'api_key' => get_option('rakubun_ai_openai_api_key', ''),
                    'model_article' => 'gpt-4',
                    'model_image' => 'dall-e-3',
                    'max_tokens' => 2000,
                    'temperature' => 0.7
                );
            }
        }
        
        return $config;
    }

    /**
     * Generate article using GPT-4
     */
    public function generate_article($prompt, $max_tokens = null) {
        $config = $this->get_openai_config();
        
        if (empty($config['api_key'])) {
            return array(
                'success' => false,
                'error' => 'OpenAI API key is not configured in the Rakubun dashboard.'
            );
        }

        $endpoint = $this->api_base . '/chat/completions';
        
        $data = array(
            'model' => $config['model_article'] ?? 'gpt-4',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a professional content writer. Generate well-structured, engaging, and informative articles.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $max_tokens ?? $config['max_tokens'] ?? 2000,
            'temperature' => $config['temperature'] ?? 0.7
        );

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'content' => $body['choices'][0]['message']['content'],
                'usage' => isset($body['usage']) ? $body['usage'] : array()
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to generate article. Please try again.'
        );
    }

    /**
     * Generate image using DALL-E
     */
    public function generate_image($prompt, $size = '1024x1024') {
        $config = $this->get_openai_config();
        
        if (empty($config['api_key'])) {
            return array(
                'success' => false,
                'error' => 'OpenAI API key is not configured in the Rakubun dashboard.'
            );
        }

        // Validate and sanitize the prompt
        $prompt = trim($prompt);
        if (empty($prompt)) {
            return array(
                'success' => false,
                'error' => 'Prompt cannot be empty.'
            );
        }

        // Validate size parameter
        $allowed_sizes = array('1024x1024', '1024x1792', '1792x1024');
        if (!in_array($size, $allowed_sizes)) {
            $size = '1024x1024'; // Default fallback
        }

        $endpoint = $this->api_base . '/images/generations';
        
        $data = array(
            'model' => $config['model_image'] ?? 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => 'standard',
            'response_format' => 'url'
        );

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['data'][0]['url'])) {
            return array(
                'success' => true,
                'url' => $body['data'][0]['url'],
                'revised_prompt' => isset($body['data'][0]['revised_prompt']) ? $body['data'][0]['revised_prompt'] : $prompt
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to generate image. Please try again.'
        );
    }

    /**
     * Make API request
     */
    private function make_request($endpoint, $data) {
        $config = $this->get_openai_config();
        
        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $config['api_key'],
                'User-Agent' => 'Rakubun-AI-WordPress-Plugin/1.0'
            ),
            'body' => json_encode($data),
            'timeout' => 120,
            'method' => 'POST'
        );

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Log the full response for debugging
        if ($status_code !== 200) {
            error_log('OpenAI API Response: Status ' . $status_code . ', Body: ' . $body);
        }

        if ($status_code !== 200) {
            $error_body = json_decode($body, true);
            $error_message = isset($error_body['error']['message']) 
                ? $error_body['error']['message'] 
                : 'API request failed with status code: ' . $status_code;
            
            return array(
                'success' => false,
                'error' => $error_message,
                'status_code' => $status_code,
                'raw_response' => $body
            );
        }

        return array(
            'success' => true,
            'body' => $body
        );
    }

    /**
     * Download and save image to WordPress media library
     */
    public function save_image_to_media($image_url, $title = '') {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url);

        if (is_wp_error($tmp)) {
            return array(
                'success' => false,
                'error' => $tmp->get_error_message()
            );
        }

        // Extract and sanitize filename from URL
        $filename = basename(parse_url($image_url, PHP_URL_PATH));
        if (empty($filename) || strpos($filename, '.') === false) {
            $filename = 'dalle-image-' . time() . '.png';
        }
        
        // Sanitize filename to prevent path traversal
        $filename = sanitize_file_name($filename);

        $file_array = array(
            'name' => $filename,
            'tmp_name' => $tmp
        );

        $id = media_handle_sideload($file_array, 0, $title);

        if (is_wp_error($id)) {
            @unlink($file_array['tmp_name']);
            return array(
                'success' => false,
                'error' => $id->get_error_message()
            );
        }

        return array(
            'success' => true,
            'attachment_id' => $id,
            'url' => wp_get_attachment_url($id)
        );
    }
}
