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
    }

    /**
     * Process automatic rewriting (called by WordPress cron)
     */
    public static function process_auto_rewrite() {
        $schedule = get_option('rakubun_ai_rewrite_schedule', array());
        
        if (empty($schedule['enabled'])) {
            return;
        }

        $articles_per_batch = intval($schedule['articles_per_batch'] ?? 5);
        $target_post_age = intval($schedule['target_post_age'] ?? 6);
        
        // Get posts that need rewriting
        $posts_to_rewrite = self::get_posts_for_rewriting($articles_per_batch, $target_post_age);
        
        if (empty($posts_to_rewrite)) {
            return;
        }

        // Get admin user ID for credit tracking
        $admin_user = get_users(array('role' => 'administrator', 'number' => 1));
        $user_id = !empty($admin_user) ? $admin_user[0]->ID : 1;
        
        foreach ($posts_to_rewrite as $post) {
            // Check if user has rewrite credits
            if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
                error_log('Rakubun AI: Insufficient rewrite credits for auto-rewrite');
                break;
            }
            
            // Perform the rewrite
            $result = self::rewrite_post($post->ID, $user_id);
            
            if ($result) {
                // Deduct credits
                Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'rewrite', 1);
                
                // Log success
                error_log("Rakubun AI: Successfully rewrote post {$post->ID} - {$post->post_title}");
            } else {
                error_log("Rakubun AI: Failed to rewrite post {$post->ID} - {$post->post_title}");
            }
            
            // Small delay to avoid overwhelming the API
            sleep(2);
        }
    }

    /**
     * Get posts that need rewriting
     */
    private static function get_posts_for_rewriting($limit = 5, $months_old = 6) {
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
        $post = get_post($post_id);
        
        if (!$post) {
            return false;
        }

        $original_content = $post->post_content;
        
        // Get OpenAI API key
        $api_key = get_option('rakubun_ai_openai_api_key');
        if (empty($api_key)) {
            return false;
        }

        try {
            $openai = new Rakubun_AI_OpenAI($api_key);
            
            // Create rewriting prompt
            $prompt = self::create_rewrite_prompt($post);
            
            // Get rewritten content from OpenAI
            $rewritten_content = $openai->generate_text($prompt, 2000);
            
            if (empty($rewritten_content)) {
                return false;
            }

            // Extract SEO improvements count (simple heuristic)
            $seo_improvements = self::count_seo_improvements($original_content, $rewritten_content);
            
            // Update the post
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $rewritten_content
            ));
            
            // Generate tags if enabled
            $schedule = get_option('rakubun_ai_rewrite_schedule', array());
            if (!empty($schedule['generate_tags_enabled'])) {
                $max_tags = intval($schedule['max_tags_per_article'] ?? 3);
                // Check if user still has rewrite credits for tag generation
                if (Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
                    $tag_result = self::generate_and_assign_tags($post, $rewritten_content, $max_tags, $user_id);
                    if ($tag_result) {
                        // Deduct additional credit for tag generation (small additional cost)
                        // This is optional - you might want to include it in the rewrite cost
                        // Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'rewrite', 1);
                        error_log("Rakubun AI: Tag generation completed for post {$post->ID}");
                    }
                } else {
                    error_log("Rakubun AI: Insufficient credits for tag generation on post {$post->ID}");
                }
            }
            
            // Record the rewrite
            Rakubun_AI_Credits_Manager::record_rewrite(
                $user_id,
                $post_id,
                $original_content,
                $rewritten_content,
                $seo_improvements
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log('Rakubun AI Rewrite Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Create rewriting prompt for OpenAI
     */
    private static function create_rewrite_prompt($post) {
        $content = strip_tags($post->post_content);
        $title = $post->post_title;
        
        $prompt = "以下の記事を日本語でリライトしてください。SEO効果を向上させ、読みやすさを改善し、価値のある情報を追加してください。\n\n";
        $prompt .= "記事タイトル: {$title}\n\n";
        $prompt .= "元の記事内容:\n{$content}\n\n";
        $prompt .= "リライト要件:\n";
        $prompt .= "1. 文章の構造を改善し、見出しを最適化する\n";
        $prompt .= "2. 自然な文脈でキーワードを追加・調整する\n";
        $prompt .= "3. 読みやすさとユーザーエクスペリエンスを向上させる\n";
        $prompt .= "4. 情報の正確性を保ちながら、価値のある内容を追加する\n";
        $prompt .= "5. HTMLタグを適切に使用して構造化する\n\n";
        $prompt .= "リライトされた記事（HTMLフォーマット）:";
        
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
    private static function generate_and_assign_tags($post, $content, $max_tags, $user_id) {
        // Get OpenAI API key
        $api_key = get_option('rakubun_ai_openai_api_key');
        if (empty($api_key)) {
            return false;
        }

        try {
            $openai = new Rakubun_AI_OpenAI($api_key);
            
            // Create tag generation prompt
            $prompt = self::create_tag_generation_prompt($post, $content, $max_tags);
            
            // Get tags from OpenAI
            $tags_response = $openai->generate_text($prompt, 800);
            
            if (empty($tags_response)) {
                return false;
            }

            // Parse the tags response
            $generated_tags = self::parse_generated_tags($tags_response);
            
            if (!empty($generated_tags)) {
                // Get existing tags
                $existing_tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
                $existing_tag_names = array_map('strtolower', $existing_tags);
                
                // Add new tags that don't already exist
                $new_tags = array();
                foreach ($generated_tags as $tag_data) {
                    $tag_name = sanitize_text_field($tag_data['title']);
                    $tag_description = sanitize_textarea_field($tag_data['description']);
                    
                    // Check if tag doesn't already exist
                    if (!in_array(strtolower($tag_name), $existing_tag_names)) {
                        // Create or get the tag
                        $tag = wp_insert_term($tag_name, 'post_tag');
                        
                        if (!is_wp_error($tag)) {
                            $new_tags[] = $tag_name;
                            
                            // Update tag description if the term was newly created
                            if (!empty($tag_description)) {
                                wp_update_term($tag['term_id'], 'post_tag', array(
                                    'description' => $tag_description
                                ));
                            }
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
        check_ajax_referer('rakubun_ai_nonce', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id']);
        
        if (!$user_id || !$post_id) {
            wp_send_json_error(array('message' => 'Invalid request.'));
        }

        // Check credits
        if (!Rakubun_AI_Credits_Manager::has_credits($user_id, 'rewrite')) {
            wp_send_json_error(array('message' => 'Insufficient rewrite credits. Please purchase more.'));
        }

        // Start rewrite
        $result = self::rewrite_post($post_id, $user_id);
        
        if ($result) {
            // Deduct credits
            Rakubun_AI_Credits_Manager::deduct_credits($user_id, 'rewrite', 1);
            
            // Check if tag generation was enabled and successful
            $schedule = get_option('rakubun_ai_rewrite_schedule', array());
            $tags_generated = !empty($schedule['generate_tags_enabled']);
            
            wp_send_json_success(array(
                'message' => 'Article successfully rewritten!' . ($tags_generated ? ' Tags were also generated.' : ''),
                'post_id' => $post_id,
                'tags_generated' => $tags_generated
            ));
        } else {
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
}

// Initialize the auto rewriter
Rakubun_AI_Auto_Rewriter::init();