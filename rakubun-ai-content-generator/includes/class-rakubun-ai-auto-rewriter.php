<?php
/**
 * Handles auto-rewriting functionality
 */
class Rakubun_AI_Auto_Rewriter {

    /**
     * Initialize the auto rewriter
     */
    public static function init() {
        // Register WordPress cron hook
        add_action('rakubun_ai_auto_rewrite', array(__CLASS__, 'process_auto_rewrite'));
        
        // Add AJAX handlers
        add_action('wp_ajax_rakubun_start_manual_rewrite', array(__CLASS__, 'ajax_start_manual_rewrite'));
        add_action('wp_ajax_rakubun_get_rewrite_status', array(__CLASS__, 'ajax_get_rewrite_status'));
        add_action('wp_ajax_rakubun_test_auto_rewrite', array(__CLASS__, 'ajax_test_auto_rewrite'));
        add_action('wp_ajax_rakubun_debug_rewrite', array(__CLASS__, 'ajax_debug_rewrite'));
    }

    /**
     * Process automatic rewriting (called by WordPress cron)
     */
    public static function process_auto_rewrite() {
        error_log('Rakubun AI: Starting auto-rewrite process');
        
        $schedule = get_option('rakubun_ai_rewrite_schedule', array());
        
        if (empty($schedule['enabled'])) {
            error_log('Rakubun AI: Auto-rewrite disabled in settings');
            return;
        }

        $articles_per_batch = intval($schedule['articles_per_batch'] ?? 5);
        $target_post_age = intval($schedule['target_post_age'] ?? 6);
        
        error_log("Rakubun AI: Auto-rewrite settings - batch: {$articles_per_batch}, age: {$target_post_age} months");
        
        // Get posts that need rewriting
        $posts_to_rewrite = self::get_posts_for_rewriting($articles_per_batch, $target_post_age);
        
        if (empty($posts_to_rewrite)) {
            error_log('Rakubun AI: No posts found for rewriting');
            return;
        }

        error_log('Rakubun AI: Found ' . count($posts_to_rewrite) . ' posts for rewriting');

        // Get admin user ID for credit tracking
        $admin_user = get_users(array('role' => 'administrator', 'number' => 1));
        $user_id = !empty($admin_user) ? $admin_user[0]->ID : 1;
        
        error_log("Rakubun AI: Using user ID {$user_id} for credit tracking");
        
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($posts_to_rewrite as $post) {
            // Check if user has rewrite credits
            if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
                error_log('Rakubun AI: Insufficient rewrite credits for auto-rewrite');
                break;
            }
            
            error_log("Rakubun AI: Starting rewrite for post {$post->ID} - {$post->post_title}");
            
            // Perform the rewrite
            $result = self::rewrite_post($post->ID, $user_id);
            
            if ($result) {
                // Deduct credits
                Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'rewrite', 1);
                
                // Log success
                error_log("Rakubun AI: Successfully rewrote post {$post->ID} - {$post->post_title}");
                $success_count++;
            } else {
                error_log("Rakubun AI: Failed to rewrite post {$post->ID} - {$post->post_title}");
                $failed_count++;
            }
            
            // Small delay to avoid overwhelming the API
            sleep(2);
        }
        
        error_log("Rakubun AI: Auto-rewrite completed - Success: {$success_count}, Failed: {$failed_count}");
    }

    /**
     * Get posts that need rewriting
     */
    public static function get_posts_for_rewriting($limit = 5, $months_old = 6) {
        global $wpdb;
        
        $rewrite_table = $wpdb->prefix . 'rakubun_rewrite_history';
        $posts_table = $wpdb->prefix . 'posts';
        
        // Get posts that are old enough and haven't been rewritten recently
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$months_old} months"));
        $recent_rewrite_threshold = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_content, p.post_modified
            FROM {$posts_table} p
            LEFT JOIN {$rewrite_table} r ON p.ID = r.post_id AND r.rewrite_date > %s
            WHERE p.post_status = 'publish'
            AND p.post_type = 'post'
            AND p.post_modified < %s
            AND r.id IS NULL
            ORDER BY p.post_modified ASC
            LIMIT %d
        ", $recent_rewrite_threshold, $date_threshold, $limit);
        
        return $wpdb->get_results($query);
    }

    /**
     * Get public method to retrieve scheduled posts for display
     */
    public static function get_next_scheduled_posts($limit = 10) {
        return Rakubun_AI_Credits_Manager::get_scheduled_rewrite_posts($limit);
    }

    /**
     * Rewrite a specific post
     */
    public static function rewrite_post($post_id, $user_id) {
        error_log("Rakubun AI: Starting rewrite_post for ID: {$post_id}, User: {$user_id}");
        
        $post = get_post($post_id);
        
        if (!$post) {
            error_log("Rakubun AI: Post {$post_id} not found");
            return false;
        }

        $original_content = $post->post_content;
        error_log("Rakubun AI: Original content length: " . strlen($original_content));
        
        // Get OpenAI API key
        $api_key = get_option('rakubun_ai_openai_api_key');
        if (empty($api_key)) {
            error_log('Rakubun AI: OpenAI API key not found in options');
            return false;
        }

        try {
            error_log('Rakubun AI: Initializing OpenAI client');
            
            // Check if OpenAI class exists
            if (!class_exists('Rakubun_AI_OpenAI')) {
                error_log('Rakubun AI: OpenAI class not found');
                return false;
            }
            
            $openai = new Rakubun_AI_OpenAI();
            
            // Detect the language of the post
            $detected_language = self::detect_post_language($post);
            error_log("Rakubun AI: Detected language: {$detected_language} for post {$post_id}");
            
            // Create rewriting prompt with detected language
            $prompt = self::create_rewrite_prompt($post, $detected_language);
            error_log('Rakubun AI: Prompt created, length: ' . strlen($prompt));
            
            // Get rewritten content from OpenAI using generate_article method with detected language
            error_log("Rakubun AI: Calling OpenAI API with language: {$detected_language}");
            $result = $openai->generate_article($prompt, 2000, $detected_language);
            
            if (!$result || !isset($result['success'])) {
                error_log('Rakubun AI: OpenAI returned invalid response');
                return false;
            }
            
            if (!$result['success']) {
                error_log('Rakubun AI: OpenAI API error: ' . ($result['error'] ?? 'Unknown error'));
                return false;
            }
            
            // Extract the content from the result
            $rewritten_content = $result['content'] ?? '';
            $rewritten_title = $result['title'] ?? '';
            
            if (empty($rewritten_content)) {
                error_log('Rakubun AI: OpenAI returned empty content');
                return false;
            }

            error_log("Rakubun AI: Rewritten content length: " . strlen($rewritten_content));

            // Extract SEO improvements count (simple heuristic)
            $seo_improvements = self::count_seo_improvements($original_content, $rewritten_content);
            error_log("Rakubun AI: SEO improvements counted: {$seo_improvements}");
            
            // Update the post
            error_log("Rakubun AI: Updating post {$post_id}");
            $update_data = array(
                'ID' => $post_id,
                'post_content' => $rewritten_content
            );
            
            // Update title if one was generated and it's different
            if (!empty($rewritten_title) && $rewritten_title !== $post->post_title) {
                $update_data['post_title'] = $rewritten_title;
                error_log("Rakubun AI: Also updating title to: {$rewritten_title}");
            }
            
            $update_result = wp_update_post($update_data);
            
            if (is_wp_error($update_result)) {
                error_log('Rakubun AI: wp_update_post failed: ' . $update_result->get_error_message());
                return false;
            }
            
            // Generate tags if enabled
            $schedule = get_option('rakubun_ai_rewrite_schedule', array());
            if (!empty($schedule['generate_tags_enabled'])) {
                $max_tags = intval($schedule['max_tags_per_article'] ?? 3);
                error_log("Rakubun AI: Tag generation enabled, max tags: {$max_tags}");
                // Check if user still has rewrite credits for tag generation
                if (Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
                    $tag_result = self::generate_and_assign_tags($post, $rewritten_content, $max_tags, $user_id, $detected_language);
                    if ($tag_result) {
                        error_log("Rakubun AI: Tag generation completed for post {$post->ID}");
                    } else {
                        error_log("Rakubun AI: Tag generation failed for post {$post->ID}");
                    }
                } else {
                    error_log("Rakubun AI: Insufficient credits for tag generation on post {$post->ID}");
                }
            }
            
            // Record the rewrite
            error_log("Rakubun AI: Recording rewrite in history");
            $record_result = Rakubun_AI_Credits_Manager::record_rewrite(
                $user_id,
                $post_id,
                $original_content,
                $rewritten_content,
                $seo_improvements
            );
            
            if (!$record_result) {
                error_log('Rakubun AI: Failed to record rewrite in history');
            }
            
            error_log("Rakubun AI: Rewrite completed successfully for post {$post_id}");
            return true;
            
        } catch (Exception $e) {
            error_log('Rakubun AI Rewrite Error: ' . $e->getMessage());
            error_log('Rakubun AI Rewrite Error Stack: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Detect the language of a post using AI
     */
    private static function detect_post_language($post) {
        $content = strip_tags($post->post_content);
        $title = $post->post_title;
        
        // Use only the first 1000 characters for efficiency
        $sample_text = $title . ' ' . substr($content, 0, 1000);
        
        try {
            $openai = new Rakubun_AI_OpenAI();
            
            // Create language detection prompt
            $detection_prompt = self::create_language_detection_prompt($sample_text);
            
            // Use a small token limit for quick detection
            $result = $openai->generate_article($detection_prompt, 100, 'en');
            
            if (!$result || !isset($result['success']) || !$result['success']) {
                error_log('Rakubun AI: Language detection failed, defaulting to English');
                return 'en';
            }
            
            // Extract language code from the response
            $response_content = $result['content'] ?? '';
            $language_code = self::parse_language_detection_response($response_content);
            
            error_log("Rakubun AI: AI detected language '{$language_code}' for post {$post->ID}");
            
            return $language_code;
            
        } catch (Exception $e) {
            error_log('Rakubun AI Language Detection Error: ' . $e->getMessage());
            return 'en'; // Fallback to English
        }
    }

    /**
     * Create language detection prompt for AI
     */
    private static function create_language_detection_prompt($text) {
        $prompt = "Analyze the following text and identify its language. Respond with ONLY the ISO 639-1 language code (2 letters) in this exact format:\n\n";
        $prompt .= "LANGUAGE: [code]\n\n";
        $prompt .= "Common language codes:\n";
        $prompt .= "- en (English)\n";
        $prompt .= "- fr (French)\n";
        $prompt .= "- es (Spanish)\n";
        $prompt .= "- de (German)\n";
        $prompt .= "- it (Italian)\n";
        $prompt .= "- pt (Portuguese)\n";
        $prompt .= "- ru (Russian)\n";
        $prompt .= "- ja (Japanese)\n";
        $prompt .= "- ko (Korean)\n";
        $prompt .= "- zh (Chinese)\n";
        $prompt .= "- ar (Arabic)\n";
        $prompt .= "- hi (Hindi)\n";
        $prompt .= "- nl (Dutch)\n";
        $prompt .= "- sv (Swedish)\n";
        $prompt .= "- no (Norwegian)\n";
        $prompt .= "- da (Danish)\n";
        $prompt .= "- pl (Polish)\n";
        $prompt .= "- tr (Turkish)\n";
        $prompt .= "\n";
        $prompt .= "Text to analyze:\n";
        $prompt .= $text;
        
        return $prompt;
    }

    /**
     * Parse the language detection response from AI
     */
    private static function parse_language_detection_response($response) {
        // Look for "LANGUAGE: xx" pattern
        if (preg_match('/LANGUAGE:\s*([a-z]{2})/i', $response, $matches)) {
            $code = strtolower($matches[1]);
            
            // Validate it's a reasonable language code
            $valid_codes = array('en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh', 'ar', 'hi', 'nl', 'sv', 'no', 'da', 'pl', 'tr', 'fi', 'el', 'he', 'th', 'vi', 'id', 'ms', 'tl', 'sw', 'cs', 'sk', 'hu', 'ro', 'bg', 'hr', 'sr', 'sl', 'et', 'lv', 'lt', 'mt', 'is', 'ga', 'cy', 'eu', 'ca', 'gl', 'br', 'co', 'lb', 'fo', 'kl');
            
            if (in_array($code, $valid_codes)) {
                return $code;
            }
        }
        
        // Fallback: try to extract any 2-letter code from the response
        if (preg_match('/\b([a-z]{2})\b/i', $response, $matches)) {
            $code = strtolower($matches[1]);
            // Only return common language codes to avoid false positives
            $common_codes = array('en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'ja', 'ko', 'zh', 'ar', 'nl', 'pl', 'tr');
            if (in_array($code, $common_codes)) {
                return $code;
            }
        }
        
        // Default fallback
        return 'en';
    }

    /**
     * Create rewriting prompt for OpenAI - works for any language
     */
    private static function create_rewrite_prompt($post, $language = 'en') {
        $content = strip_tags($post->post_content);
        $title = $post->post_title;
        
        // Get language name for the prompt
        $language_names = array(
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish', 
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'zh' => 'Chinese',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'no' => 'Norwegian',
            'da' => 'Danish',
            'pl' => 'Polish',
            'tr' => 'Turkish',
            'fi' => 'Finnish',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
            'id' => 'Indonesian',
            'ms' => 'Malay',
            'tl' => 'Filipino',
            'cs' => 'Czech',
            'sk' => 'Slovak',
            'hu' => 'Hungarian',
            'ro' => 'Romanian',
            'bg' => 'Bulgarian',
            'hr' => 'Croatian',
            'sr' => 'Serbian',
            'sl' => 'Slovenian',
            'et' => 'Estonian',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian'
        );
        
        $language_name = $language_names[$language] ?? 'the original language';
        
        // Create a universal prompt that works for any language
        $prompt = "You are a professional SEO content writer and editor. Rewrite the following article to improve its SEO effectiveness, readability, and overall quality while preserving its original language and meaning.\n\n";
        
        $prompt .= "CRITICAL INSTRUCTION: The article is written in {$language_name} (language code: {$language}). You MUST rewrite it in the EXACT SAME LANGUAGE. Do not translate it to any other language.\n\n";
        
        $prompt .= "ARTICLE INFORMATION:\n";
        $prompt .= "Title: {$title}\n\n";
        $prompt .= "Content:\n{$content}\n\n";
        
        $prompt .= "REWRITING REQUIREMENTS:\n";
        $prompt .= "1. Maintain the original language ({$language_name}) throughout the entire rewrite\n";
        $prompt .= "2. Improve text structure and optimize headings for better SEO\n";
        $prompt .= "3. Add relevant keywords naturally within the content\n";
        $prompt .= "4. Enhance readability and user experience\n";
        $prompt .= "5. Preserve factual accuracy while adding valuable information where appropriate\n";
        $prompt .= "6. Use proper HTML formatting (headings, lists, bold text, etc.)\n";
        $prompt .= "7. Ensure the content flows naturally and maintains the original tone\n";
        $prompt .= "8. Keep the same subject matter and core message\n\n";
        
        $prompt .= "OUTPUT FORMAT: Provide the rewritten article content ready for publication. Do not include any explanations or meta-commentary - just the improved article content.\n\n";
        
        return $prompt;
    }

    /**
     * Count SEO improvements (simple heuristic)
     */
    private static function count_seo_improvements($original, $rewritten) {
        $improvements = 0;
        
        // Count new heading tags
        $original_headings = preg_match_all('/<h[1-6][^>]*>/i', $original);
        $rewritten_headings = preg_match_all('/<h[1-6][^>]*>/i', $rewritten);
        if ($rewritten_headings > $original_headings) {
            $improvements += ($rewritten_headings - $original_headings);
        }
        
        // Count new strong/em tags
        $original_emphasis = preg_match_all('/<(strong|em|b|i)[^>]*>/i', $original);
        $rewritten_emphasis = preg_match_all('/<(strong|em|b|i)[^>]*>/i', $rewritten);
        if ($rewritten_emphasis > $original_emphasis) {
            $improvements += 1;
        }
        
        // Count length improvement
        if (strlen($rewritten) > strlen($original) * 1.1) {
            $improvements += 1;
        }
        
        // Count new lists
        $original_lists = preg_match_all('/<(ul|ol)[^>]*>/i', $original);
        $rewritten_lists = preg_match_all('/<(ul|ol)[^>]*>/i', $rewritten);
        if ($rewritten_lists > $original_lists) {
            $improvements += 1;
        }
        
        return max(1, $improvements); // At least 1 improvement
    }

    /**
     * Generate and assign tags to a post
     */
    private static function generate_and_assign_tags($post, $content, $max_tags, $user_id, $language = 'en') {
        try {
            $openai = new Rakubun_AI_OpenAI();
            
            // Use the built-in generate_tags method with detected language
            $clean_content = wp_strip_all_tags($content);
            error_log("Rakubun AI: Generating tags in language: {$language}");
            $result = $openai->generate_tags($post->post_title, $clean_content, $max_tags, $language);
            
            if (!$result || !isset($result['success']) || !$result['success']) {
                error_log('Rakubun AI: Tag generation failed: ' . ($result['error'] ?? 'Unknown error'));
                return false;
            }

            $generated_tags = $result['tags'] ?? array();
            
            if (!empty($generated_tags)) {
                // Get existing tags
                $existing_tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
                $existing_tag_names = array_map('strtolower', $existing_tags);
                
                // Add new tags that don't already exist
                $new_tags = array();
                foreach ($generated_tags as $tag_name) {
                    $tag_name = sanitize_text_field($tag_name);
                    
                    // Check if tag doesn't already exist
                    if (!in_array(strtolower($tag_name), $existing_tag_names)) {
                        // Create or get the tag
                        $tag = wp_insert_term($tag_name, 'post_tag');
                        
                        if (!is_wp_error($tag)) {
                            $new_tags[] = $tag_name;
                        }
                    }
                }
                
                // Assign tags to the post (append to existing tags)
                if (!empty($new_tags)) {
                    wp_set_post_tags($post->ID, array_merge($existing_tags, $new_tags));
                    
                    // Log the tag generation
                    error_log("Rakubun AI: Generated " . count($new_tags) . " tags for post {$post->ID}: " . implode(', ', $new_tags));
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('Rakubun AI Tag Generation Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create tag generation prompt for OpenAI
     */
    private static function create_tag_generation_prompt($post, $content, $max_tags) {
        $clean_content = wp_strip_all_tags($content);
        $title = $post->post_title;
        
        $prompt = "以下の記事内容に基づいて、SEOに効果的な日本語のタグを{$max_tags}個生成してください。各タグには「タイトル」と「説明」を含めてください。\n\n";
        $prompt .= "記事タイトル: {$title}\n\n";
        $prompt .= "記事内容:\n" . substr($clean_content, 0, 1500) . "\n\n";
        $prompt .= "要件:\n";
        $prompt .= "1. 記事の主要テーマを反映したタグを生成する\n";
        $prompt .= "2. SEO効果が期待できるキーワードを含める\n";
        $prompt .= "3. 各タグは2-3語程度の短いフレーズにする\n";
        $prompt .= "4. 説明は1-2文で簡潔に\n\n";
        $prompt .= "以下のJSON形式で出力してください:\n";
        $prompt .= "[\n";
        $prompt .= "  {\n";
        $prompt .= "    \"title\": \"タグ名\",\n";
        $prompt .= "    \"description\": \"タグの説明\"\n";
        $prompt .= "  }\n";
        $prompt .= "]\n\n";
        $prompt .= "生成されたタグ:";
        
        return $prompt;
    }

    /**
     * Parse generated tags from OpenAI response
     */
    private static function parse_generated_tags($response) {
        // Try to extract JSON from the response
        $json_start = strpos($response, '[');
        $json_end = strrpos($response, ']');
        
        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response, $json_start, $json_end - $json_start + 1);
            $tags = json_decode($json_string, true);
            
            if (is_array($tags)) {
                return array_filter($tags, function($tag) {
                    return isset($tag['title']) && isset($tag['description']) && 
                           !empty($tag['title']) && !empty($tag['description']);
                });
            }
        }
        
        // Fallback: try to parse line by line
        $lines = explode("\n", $response);
        $tags = array();
        $current_tag = array();
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[\-\*]?\s*(.+?)[:：]\s*(.+)$/', $line, $matches)) {
                if (count($tags) < 5) { // Max 5 tags
                    $tags[] = array(
                        'title' => trim($matches[1]),
                        'description' => trim($matches[2])
                    );
                }
            }
        }
        
        return $tags;
    }

    /**
     * AJAX handler for manual rewrite
     */
    public static function ajax_start_manual_rewrite() {
        error_log('Rakubun AI: Manual rewrite AJAX handler called');
        
        try {
            check_ajax_referer('rakubun_ai_nonce', 'nonce');
        } catch (Exception $e) {
            error_log('Rakubun AI: Nonce verification failed: ' . $e->getMessage());
            wp_send_json_error(array('message' => 'Security check failed.'));
        }

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);
        
        error_log("Rakubun AI: Manual rewrite - User ID: {$user_id}, Post ID: {$post_id}");
        
        if (!$user_id || !$post_id) {
            error_log('Rakubun AI: Invalid user ID or post ID');
            wp_send_json_error(array('message' => 'Invalid request parameters.'));
        }

        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            error_log("Rakubun AI: Post {$post_id} not found");
            wp_send_json_error(array('message' => 'Post not found.'));
        }

        // Check credits
        if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
            error_log('Rakubun AI: Insufficient rewrite credits');
            wp_send_json_error(array('message' => 'Insufficient rewrite credits. Please purchase more.'));
        }

        // Check OpenAI API key
        $api_key = get_option('rakubun_ai_openai_api_key');
        if (empty($api_key)) {
            error_log('Rakubun AI: OpenAI API key not found');
            wp_send_json_error(array('message' => 'OpenAI API key not configured.'));
        }

        error_log("Rakubun AI: Starting rewrite for post {$post_id} - {$post->post_title}");

        // Start rewrite
        $result = self::rewrite_post($post_id, $user_id);
        
        if ($result) {
            // Deduct credits
            Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'rewrite', 1);
            
            // Check if tag generation was enabled and successful
            $schedule = get_option('rakubun_ai_rewrite_schedule', array());
            $tags_generated = !empty($schedule['generate_tags_enabled']);
            
            error_log("Rakubun AI: Manual rewrite successful for post {$post_id}");
            
            wp_send_json_success(array(
                'message' => 'Article successfully rewritten!' . ($tags_generated ? ' Tags were also generated.' : ''),
                'post_id' => $post_id,
                'tags_generated' => $tags_generated
            ));
        } else {
            error_log("Rakubun AI: Manual rewrite failed for post {$post_id}");
            wp_send_json_error(array('message' => 'Failed to rewrite article. Please try again.'));
        }
    }

    /**
     * AJAX handler for getting rewrite status
     */
    public static function ajax_get_rewrite_status() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User not authenticated.'));
        }

        $rewrite_stats = Rakubun_AI_Credits_Manager::get_rewrite_statistics($user_id);
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        
        wp_send_json_success(array(
            'stats' => $rewrite_stats,
            'credits' => $credits
        ));
    }

    /**
     * AJAX handler for testing auto rewrite cron
     */
    public static function ajax_test_auto_rewrite() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        // Check if auto rewrite is enabled
        $schedule = get_option('rakubun_ai_rewrite_schedule', array());
        if (empty($schedule['enabled'])) {
            wp_send_json_error(array('message' => '自動リライト機能が無効になっています。'));
        }

        // Check if there are posts to rewrite
        $articles_per_batch = intval($schedule['articles_per_batch'] ?? 5);
        $target_post_age = intval($schedule['target_post_age'] ?? 6);
        $posts_to_rewrite = self::get_posts_for_rewriting($articles_per_batch, $target_post_age);
        
        if (empty($posts_to_rewrite)) {
            wp_send_json_error(array('message' => 'リライト対象の記事がありません。設定を確認してください。'));
        }

        // Check credits
        $required_credits = min(count($posts_to_rewrite), $articles_per_batch);
        $user_credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        if (($user_credits['rewrite_credits'] ?? 0) < $required_credits) {
            wp_send_json_error(array('message' => 'クレジットが不足しています。必要クレジット: ' . $required_credits . '、現在のクレジット: ' . ($user_credits['rewrite_credits'] ?? 0)));
        }

        // Execute the auto rewrite process
        try {
            self::process_auto_rewrite();
            
            wp_send_json_success(array(
                'message' => '自動リライト処理を実行しました。',
                'processed_count' => count($posts_to_rewrite),
                'credits_used' => $required_credits
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'エラーが発生しました: ' . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for debugging rewrite system
     */
    public static function ajax_debug_rewrite() {
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        
        if (!$user_id || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }

        $debug_info = array();
        
        // Check OpenAI class
        $debug_info['openai_class_exists'] = class_exists('Rakubun_AI_OpenAI');
        
        // Check OpenAI API key
        $api_key = get_option('rakubun_ai_openai_api_key');
        $debug_info['api_key_configured'] = !empty($api_key);
        $debug_info['api_key_length'] = strlen($api_key ?? '');
        
        // Check user credits
        $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
        $debug_info['user_credits'] = $credits;
        
        // Check schedule settings
        $schedule = get_option('rakubun_ai_rewrite_schedule', array());
        $debug_info['schedule_settings'] = $schedule;
        
        // Check if there are posts to rewrite
        $posts_to_rewrite = self::get_posts_for_rewriting(5, 6);
        $debug_info['posts_available'] = count($posts_to_rewrite);
        
        // Check cron
        $next_cron = wp_next_scheduled('rakubun_ai_auto_rewrite');
        $debug_info['next_cron'] = $next_cron ? date('Y-m-d H:i:s', $next_cron) : 'Not scheduled';
        
        // Test OpenAI connection
        try {
            $openai = new Rakubun_AI_OpenAI();
            $test_result = $openai->generate_article('Test prompt for debugging', 50, 'ja');
            $debug_info['openai_test'] = array(
                'success' => $test_result['success'] ?? false,
                'error' => $test_result['error'] ?? null
            );
        } catch (Exception $e) {
            $debug_info['openai_test'] = array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
        
        wp_send_json_success($debug_info);
    }
}

// Initialize the auto rewriter
Rakubun_AI_Auto_Rewriter::init();