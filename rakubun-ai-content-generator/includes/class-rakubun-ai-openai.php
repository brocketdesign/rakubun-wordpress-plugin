<?php
/**
 * OpenAI API integration for article and image generation
 * Supports both OpenAI and Novita AI providers
 */
class Rakubun_AI_OpenAI {

    /**
     * API configuration from external API
     */
    private $config;

    /**
     * API provider (openai or novita)
     */
    private $provider;

    /**
     * API base URL
     */
    private $api_base;

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
        $this->config = $this->get_api_config();
        $this->set_api_base();
    }

    /**
     * Set API base URL based on provider
     */
    private function set_api_base() {
        switch ($this->provider) {
            case 'novita':
                $this->api_base = 'https://api.novita.ai/openai/v1';
                break;
            case 'openai':
            default:
                $this->api_base = 'https://api.openai.com/v1';
                break;
        }
    }

    /**
     * Get API configuration from external API
     * Fetches provider-specific configuration including API key and models
     */
    private function get_api_config() {
        // Get provider from local settings
        $local_provider = get_option('rakubun_ai_api_provider', 'openai');
        $this->provider = $local_provider;
        
        // Check cache first (5 minutes - shorter duration to get fresh API keys)
        $cache_key = 'rakubun_ai_api_config_' . $local_provider;
        $config = get_transient($cache_key);
        
        if ($config === false) {
            if ($this->external_api->is_connected()) {
                // Use the new /config/provider endpoint which returns both API key and models
                $config = $this->external_api->get_provider_config($local_provider);
                if ($config) {
                    // Cache for 5 minutes (shorter to get fresh keys if updated on dashboard)
                    set_transient($cache_key, $config, 5 * MINUTE_IN_SECONDS);
                }
            }
            
            // Fallback to local settings if external API fails
            if (!$config) {
                error_log('Rakubun AI: Using fallback local configuration for provider: ' . $local_provider);
                $config = array(
                    'api_key' => get_option('rakubun_ai_openai_api_key', ''),
                    'api_provider' => $local_provider,
                    'model_article' => get_option('rakubun_ai_model_article', 'gpt-4'),
                    'model_image' => get_option('rakubun_ai_model_image', 'dall-e-3'),
                    'max_tokens' => 2000,
                    'temperature' => 0.7
                );
            }
        }
        
        // Validate that we have the required fields
        if (is_array($config)) {
            // Ensure provider is set correctly
            $config['api_provider'] = $local_provider;
            
            // Apply defaults for missing fields
            if (empty($config['model_article'])) {
                $config['model_article'] = 'gpt-4';
            }
            if (empty($config['model_image'])) {
                $config['model_image'] = 'dall-e-3';
            }
            if (empty($config['max_tokens'])) {
                $config['max_tokens'] = 2000;
            }
            if (empty($config['temperature'])) {
                $config['temperature'] = 0.7;
            }
        }
        
        return $config;
    }

    /**
     * Generate article using GPT-4 or Novita model
     */
    public function generate_article($prompt, $max_tokens = null, $language = 'ja') {
        $config = $this->get_api_config();
        
        if (empty($config['api_key'])) {
            return array(
                'success' => false,
                'error' => 'API key is not configured in the Rakubun dashboard.'
            );
        }

        $endpoint = $this->api_base . '/chat/completions';
        
        // Map language codes to language names and instructions
        $language_map = array(
            'ja' => array('name' => 'Japanese', 'instruction' => 'Generate the article in Japanese (日本語).'),
            'en' => array('name' => 'English', 'instruction' => 'Generate the article in English.'),
            'zh' => array('name' => 'Traditional Chinese', 'instruction' => 'Generate the article in Traditional Chinese (繁體中文).'),
            'es' => array('name' => 'Spanish', 'instruction' => 'Generate the article in Spanish (Español).'),
            'fr' => array('name' => 'French', 'instruction' => 'Generate the article in French (Français).'),
            'de' => array('name' => 'German', 'instruction' => 'Generate the article in German (Deutsch).'),
            'ko' => array('name' => 'Korean', 'instruction' => 'Generate the article in Korean (한국어).')
        );
        
        // Default to Japanese if language is not recognized
        if (!isset($language_map[$language])) {
            $language = 'ja';
        }
        
        $language_instruction = $language_map[$language]['instruction'];
        
        // Create a system prompt that requests both title and content with markdown formatting
        $title_instruction = '';
        if ($language === 'ja') {
            $title_instruction = "\n\n必ず以下の形式で返してください:\n\n<title>\nSEOフレンドリーなタイトル（50-60文字）\n</title>\n\n<content>\n本文内容（Markdown形式で、## または ### を使用してセクションを分ける、**太字**、リストなど）\n</content>\n\nMarkdown形式の例:\n## セクションタイトル\n本文\n\n### サブセクション\n- リスト項目1\n- リスト項目2\n\n**太字テキスト** または *斜体テキスト*";
        } elseif ($language === 'en') {
            $title_instruction = "\n\nYou MUST return the response in the following format:\n\n<title>\nSEO-friendly title (50-60 characters)\n</title>\n\n<content>\nArticle body content (Use Markdown format with ## or ### for sections, **bold**, lists, etc.)\n</content>\n\nMarkdown example:\n## Section Title\nBody text\n\n### Subsection\n- List item 1\n- List item 2\n\n**Bold text** or *Italic text*";
        } else {
            // For other languages, use English format as fallback
            $title_instruction = "\n\nYou MUST return the response in the following format:\n\n<title>\nSEO-friendly title (50-60 characters)\n</title>\n\n<content>\nArticle body content (Use Markdown format with ## or ### for sections, **bold**, lists, etc.)\n</content>\n\nMarkdown example:\n## Section Title\nBody text\n\n### Subsection\n- List item 1\n- List item 2\n\n**Bold text** or *Italic text*";
        }
        
        $data = array(
            'model' => $config['model_article'] ?? 'gpt-4',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a professional SEO content writer. Generate well-structured, engaging, and informative articles. ' . $language_instruction . $title_instruction
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $max_tokens ?? $config['max_tokens'] ?? 2000,
            'temperature' => $config['temperature'] ?? 0.7
        );

        // Log the provider and model being used
        error_log('Rakubun AI - Article Generation: Provider=' . $this->provider . ', Model=' . $data['model']);

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['choices'][0]['message']['content'])) {
            $content = $body['choices'][0]['message']['content'];
            $title = '';
            
            // Parse title and content from the response
            $title_match = preg_match('/<title>\s*(.+?)\s*<\/title>/s', $content, $title_matches);
            $content_match = preg_match('/<content>\s*(.+?)\s*<\/content>/s', $content, $content_matches);
            
            if ($title_match && !empty($title_matches[1])) {
                $title = trim($title_matches[1]);
                $content = $content_match && !empty($content_matches[1]) ? trim($content_matches[1]) : $content;
                
                // Remove the XML tags from content if they're still there
                $content = preg_replace('/<title>.+?<\/title>/s', '', $content);
                $content = preg_replace('/<content>|<\/content>/s', '', $content);
            }
            
            // Convert markdown to HTML for better formatting
            $html_content = $this->markdown_to_html($content);
            
            return array(
                'success' => true,
                'title' => $title,
                'content' => $html_content,
                'usage' => isset($body['usage']) ? $body['usage'] : array()
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to generate article. Please try again.'
        );
    }

    /**
     * Generate image using DALL-E or Novita image model
     */
    public function generate_image($prompt, $size = '1024x1024') {
        $config = $this->get_api_config();
        
        if (empty($config['api_key'])) {
            return array(
                'success' => false,
                'error' => 'API key is not configured in the Rakubun dashboard.'
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

        // If provider is Novita, use Novita-specific async flow
        if ($this->provider === 'novita') {
            require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-novita-image.php';
            $novita = new Rakubun_AI_Novita_Image($config['api_key']);
            $result = $novita->generate_image($prompt, $size, 15, 180);
            // Keep return structure consistent
            if ($result['success']) {
                return array(
                    'success' => true,
                    'url' => $result['url'],
                    'revised_prompt' => isset($result['revised_prompt']) ? $result['revised_prompt'] : $prompt
                );
            }
            return $result;
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

        // Log the provider and model being used
        error_log('Rakubun AI - Image Generation: Provider=' . $this->provider . ', Model=' . $data['model']);

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
     * Convert Markdown to HTML
     */
    private function markdown_to_html($markdown) {
        // Protect code blocks
        $markdown = preg_replace_callback('/```[\s\S]*?```/', function($matches) {
            return '___CODE_BLOCK_' . uniqid() . '___';
        }, $markdown);
        
        $code_blocks = array();
        $markdown = preg_replace_callback('/```[\s\S]*?```/', function($matches) use (&$code_blocks) {
            $key = '___CODE_BLOCK_' . uniqid() . '___';
            $code_blocks[$key] = '<pre><code>' . htmlspecialchars(trim($matches[0], '`')) . '</code></pre>';
            return $key;
        }, $markdown);
        
        // Convert ## headings to <h2>
        $markdown = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $markdown);
        
        // Convert ### headings to <h3>
        $markdown = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
        
        // Convert #### headings to <h4>
        $markdown = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $markdown);
        
        // Convert # headings to <h1> (but avoid title)
        $markdown = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $markdown);
        
        // Convert **bold** to <strong>
        $markdown = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $markdown);
        
        // Convert __bold__ to <strong>
        $markdown = preg_replace('/__(.+?)__/s', '<strong>$1</strong>', $markdown);
        
        // Convert *italic* to <em>
        $markdown = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $markdown);
        
        // Convert _italic_ to <em>
        $markdown = preg_replace('/_(.+?)_/s', '<em>$1</em>', $markdown);
        
        // Convert unordered lists
        $markdown = preg_replace_callback('/^- (.+)$/m', function($matches) {
            return '<li>' . $matches[1] . '</li>';
        }, $markdown);
        
        // Wrap consecutive list items in <ul>
        $markdown = preg_replace('/<li>(.+?)<\/li>(\s*<li>)/s', '<ul><li>$1</li>$2', $markdown);
        $markdown = preg_replace('/(<li>.+?<\/li>)(\s*(?!<li>))/s', '$1</ul>$2', $markdown);
        
        // Convert ordered lists
        $markdown = preg_replace_callback('/^\d+\. (.+)$/m', function($matches) {
            return '<li>' . $matches[1] . '</li>';
        }, $markdown);
        
        // Wrap consecutive ordered list items in <ol>
        $markdown = preg_replace('/<li>(.+?)<\/li>(\s*<li>)/s', '<ol><li>$1</li>$2', $markdown);
        $markdown = preg_replace('/(<li>.+?<\/li>)(\s*(?!<li>))/s', '$1</ol>$2', $markdown);
        
        // Convert line breaks to paragraphs
        $paragraphs = preg_split('/\n\n+/', trim($markdown));
        $html = '';
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if (!empty($para)) {
                // Don't wrap headings, lists, or code blocks in <p>
                if (!preg_match('/^(<h[1-6]>|<ul>|<ol>|<li>|<pre>|<code>)/', $para)) {
                    $para = '<p>' . nl2br($para) . '</p>';
                }
                $html .= $para . "\n";
            }
        }
        
        // Restore code blocks
        foreach ($code_blocks as $key => $code) {
            $html = str_replace($key, $code, $html);
        }
        
        return $html;
    }

    /**
     * Make API request
     */
    private function make_request($endpoint, $data) {
        $config = $this->get_api_config();
        $provider_name = $this->provider === 'novita' ? 'Novita' : 'OpenAI';
        
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
            error_log("{$provider_name} API Error: " . $response->get_error_message());
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        // Log the full response for debugging
        if ($status_code !== 200) {
            error_log("{$provider_name} API Response: Status " . $status_code . ', Body: ' . $body);
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

    /**
     * Generate tags for article using GPT or Novita model
     */
    public function generate_tags($title, $content, $max_tags = 5, $language = 'ja') {
        $config = $this->get_api_config();
        
        if (empty($config['api_key'])) {
            return array(
                'success' => false,
                'error' => 'API key is not configured in the Rakubun dashboard.'
            );
        }

        $endpoint = $this->api_base . '/chat/completions';
        
        // Prepare language-specific prompt
        $lang_prompt = '';
        if ($language === 'ja') {
            $lang_prompt = "最大{$max_tags}個の関連するタグを日本語で生成してください。";
        } else {
            $lang_prompt = "Generate up to {$max_tags} relevant tags in English.";
        }
        
        // Create prompt for tag generation
        $tag_prompt = "以下の記事タイトルと内容から、{$lang_prompt}\n\nタイトル: {$title}\n\n内容:\n{$content}\n\nタグは、カンマで区切られたシンプルなリストで返してください。例: タグ1, タグ2, タグ3\nタグのみを返してください。説明やその他のテキストは不要です。";
        
        $data = array(
            'model' => $config['model_article'] ?? 'gpt-4',
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are a content tagging expert. Generate concise, relevant tags for articles.'
                ),
                array(
                    'role' => 'user',
                    'content' => $tag_prompt
                )
            ),
            'max_tokens' => 200,
            'temperature' => 0.5
        );

        $response = $this->make_request($endpoint, $data);

        if (!$response['success']) {
            return $response;
        }

        $body = json_decode($response['body'], true);

        if (isset($body['choices'][0]['message']['content'])) {
            $tags_text = $body['choices'][0]['message']['content'];
            
            // Parse the comma-separated tags
            $tags = array_map('trim', explode(',', $tags_text));
            
            // Remove empty tags and limit to max_tags
            $tags = array_filter($tags);
            $tags = array_slice($tags, 0, $max_tags);
            
            return array(
                'success' => true,
                'tags' => $tags
            );
        }

        return array(
            'success' => false,
            'error' => 'Failed to generate tags. Please try again.'
        );
    }

    /**
     * Get the current API provider
     */
    public function get_provider() {
        return $this->provider;
    }

    /**
     * Get the current API base URL
     */
    public function get_api_base() {
        return $this->api_base;
    }

    /**
     * Get the current configuration
     */
    public function get_config() {
        return $this->config;
    }
}
