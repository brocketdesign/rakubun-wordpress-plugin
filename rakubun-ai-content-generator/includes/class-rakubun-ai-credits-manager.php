<?php
/**
 * Manages user credits for articles and images via external API
 */
class Rakubun_AI_Credits_Manager {

    /**
     * External API instance
     */
    private static $external_api = null;

    /**
     * Get external API instance
     */
    private static function get_external_api() {
        if (self::$external_api === null) {
            require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
            self::$external_api = new Rakubun_AI_External_API();
        }
        return self::$external_api;
    }

    /**
     * Get user credits from external API with local fallback
     */
    public static function get_user_credits($user_id) {
        error_log('Rakubun_AI_Credits_Manager::get_user_credits() called for user ' . $user_id);
        
        $external_api = self::get_external_api();
        
        // External API is now REQUIRED - no fallback to local database
        if (!$external_api->is_connected()) {
            error_log('Rakubun: CRITICAL - External API is NOT connected. Cannot fetch credits.');
            throw new Exception('Dashboard connection failed. Please verify your plugin settings and try again.');
        }
        
        error_log('Rakubun: External API is connected');
        
        $cache_key = 'rakubun_ai_credits_' . $user_id;
        $credits = get_transient($cache_key);
        
        if ($credits === false) {
            error_log('Rakubun: Cache miss, fetching from external API');
            
            $credits = $external_api->get_user_credits($user_id);
            error_log('Rakubun: External API returned: ' . wp_json_encode($credits));
            
            if (!$credits) {
                error_log('Rakubun: CRITICAL - External API returned no credits for user ' . $user_id);
                throw new Exception('Failed to fetch credits from dashboard. Please try again or contact support.');
            }
            
            // Cache for 5 minutes to reduce API calls (no syncing - use external data directly)
            set_transient($cache_key, $credits, 5 * MINUTE_IN_SECONDS);
        } else {
            error_log('Rakubun: Cache hit for user ' . $user_id . ': ' . wp_json_encode($credits));
        }
        
        error_log('Rakubun: Returning credits from external API: ' . wp_json_encode($credits));
        return $credits;
    }

    /**
     * Get credits from local database (fallback method)
     */
    private static function get_local_credits($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        if (!$table_exists) {
            // Create the table if it doesn't exist
            self::create_credits_table();
        }
        
        // Try to get credits with rewrite_credits column, fallback if column doesn't exist
        $credits = $wpdb->get_row($wpdb->prepare(
            "SELECT article_credits, image_credits, 
             COALESCE(rewrite_credits, 0) as rewrite_credits 
             FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // If query failed (maybe rewrite_credits column doesn't exist), try without it
        if ($wpdb->last_error) {
            $credits = $wpdb->get_row($wpdb->prepare(
                "SELECT article_credits, image_credits FROM $table_name WHERE user_id = %d",
                $user_id
            ));
        }
        
        // If user doesn't have credits record, create one with free credits
        if (!$credits) {
            $insert_data = array(
                'user_id' => $user_id,
                'article_credits' => 3,
                'image_credits' => 5
            );
            
            // Check if rewrite_credits column exists
            $rewrite_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'rewrite_credits'");
            if (!empty($rewrite_column_exists)) {
                $insert_data['rewrite_credits'] = 0;
            }
            
            $wpdb->insert($table_name, $insert_data);
            
            return array(
                'article_credits' => 3,
                'image_credits' => 5,
                'rewrite_credits' => 0
            );
        }
        
        return array(
            'article_credits' => (int) $credits->article_credits,
            'image_credits' => (int) $credits->image_credits,
            'rewrite_credits' => isset($credits->rewrite_credits) ? (int) $credits->rewrite_credits : 0
        );
    }

    /**
     * Sync external API credits with local usage data
     */
    private static function sync_credits_with_local_usage($user_id, $external_credits) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        
        // Check if content table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'");
        if (!$table_exists) {
            return $external_credits; // Return external credits if no local data
        }
        
        // Count actual usage from local database
        $articles_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'article' AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        $images_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'image' AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        $rewrite_used = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rakubun_rewrite_history WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        // Calculate remaining credits based on usage
        // Default starting credits: 3 articles, 5 images, 0 rewrites
        $starting_articles = 3;
        $starting_images = 5;
        $starting_rewrites = 0;
        
        // Check if user has purchased additional credits
        $purchased_credits = self::get_purchased_credits($user_id);
        $starting_articles += $purchased_credits['article_credits'];
        $starting_images += $purchased_credits['image_credits'];
        $starting_rewrites += $purchased_credits['rewrite_credits'];
        
        return array(
            'article_credits' => max(0, $starting_articles - $articles_used),
            'image_credits' => max(0, $starting_images - $images_used),
            'rewrite_credits' => max(0, $starting_rewrites - $rewrite_used)
        );
    }

    /**
     * Get purchased credits from transactions
     */
    private static function get_purchased_credits($user_id) {
        global $wpdb;
        $transactions_table = $wpdb->prefix . 'rakubun_transactions';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$transactions_table'");
        if (!$table_exists) {
            return array('article_credits' => 0, 'image_credits' => 0, 'rewrite_credits' => 0);
        }
        
        $purchased = $wpdb->get_results($wpdb->prepare(
            "SELECT credit_type, SUM(credits_purchased) as total FROM $transactions_table 
             WHERE user_id = %d AND status = 'completed' 
             GROUP BY credit_type",
            $user_id
        ));
        
        $credits = array('article_credits' => 0, 'image_credits' => 0, 'rewrite_credits' => 0);
        
        foreach ($purchased as $purchase) {
            $key = $purchase->credit_type . '_credits';
            if (isset($credits[$key])) {
                $credits[$key] = (int) $purchase->total;
            }
        }
        
        return $credits;
    }

    /**
     * Check if user has credits
     */
    public static function has_credits($user_id, $type = 'article') {
        $credits = self::get_user_credits($user_id);
        
        if ($type === 'article') {
            return $credits['article_credits'] > 0;
        } elseif ($type === 'image') {
            return $credits['image_credits'] > 0;
        } elseif ($type === 'rewrite') {
            return $credits['rewrite_credits'] > 0;
        } else {
            return false;
        }
    }

    /**
     * Deduct credits (uses external API if connected)
     */
    public static function deduct_credits($user_id, $credit_type, $amount = 1) {
        $external_api = self::get_external_api();
        
        // Try external API first
        if ($external_api->is_connected()) {
            $result = $external_api->deduct_credits($user_id, $credit_type, $amount);
            if ($result) {
                // Clear local cache so next request will sync with local usage
                delete_transient('rakubun_ai_credits_' . $user_id);
                return true;
            }
        }
        
        // Fallback to local database
        $local_result = self::deduct_local_credits($user_id, $credit_type, $amount);
        
        // Always clear cache when credits are deducted locally
        if ($local_result) {
            delete_transient('rakubun_ai_credits_' . $user_id);
        }
        
        return $local_result;
    }

    /**
     * Deduct credits from local database (fallback method)
     */
    private static function deduct_local_credits($user_id, $credit_type, $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Validate type parameter against whitelist to prevent SQL injection
        if (!in_array($credit_type, array('article', 'image', 'rewrite'), true)) {
            return false;
        }
        
        // Use separate queries for each type to avoid dynamic column names
        if ($credit_type === 'article') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET article_credits = article_credits - %d WHERE user_id = %d AND article_credits >= %d",
                $amount,
                $user_id,
                $amount
            ));
        } elseif ($credit_type === 'image') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET image_credits = image_credits - %d WHERE user_id = %d AND image_credits >= %d",
                $amount,
                $user_id,
                $amount
            ));
        } else { // rewrite
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET rewrite_credits = rewrite_credits - %d WHERE user_id = %d AND rewrite_credits >= %d",
                $amount,
                $user_id,
                $amount
            ));
        }
        
        return $result > 0;
    }

    /**
     * Add credits to user (local database only - external API manages this)
     */
    public static function add_credits($user_id, $type = 'article', $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Validate type parameter against whitelist to prevent SQL injection
        if (!in_array($type, array('article', 'image', 'rewrite'), true)) {
            return false;
        }
        
        // Ensure user has a credits record
        $credits = self::get_local_credits($user_id);
        
        // Use separate queries for each type to avoid dynamic column names
        if ($type === 'article') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET article_credits = article_credits + %d WHERE user_id = %d",
                $amount,
                $user_id
            ));
        } elseif ($type === 'image') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET image_credits = image_credits + %d WHERE user_id = %d",
                $amount,
                $user_id
            ));
        } else { // rewrite
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET rewrite_credits = rewrite_credits + %d WHERE user_id = %d",
                $amount,
                $user_id
            ));
        }
        
        return $result > 0;
    }

    /**
     * Log transaction
     */
    public static function log_transaction($user_id, $type, $amount, $credits, $credit_type, $stripe_id = '', $status = 'completed') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_transactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'transaction_type' => $type,
                'amount' => $amount,
                'credits_purchased' => $credits,
                'credit_type' => $credit_type,
                'stripe_payment_id' => $stripe_id,
                'status' => $status
            ),
            array('%d', '%s', '%f', '%d', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Log generated content
     */
    public static function log_generated_content($user_id, $type, $prompt, $content, $image_url = '', $post_id = 0, $attachment_id = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_generated_content';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'content_type' => $type,
                'post_id' => $post_id,
                'attachment_id' => $attachment_id,
                'prompt' => $prompt,
                'generated_content' => $content,
                'image_url' => $image_url,
                'status' => 'completed'
            ),
            array('%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s')
        );
        
        // Also log to external API for analytics
        $external_api = self::get_external_api();
        if ($external_api->is_connected()) {
            $external_api->log_generation($user_id, $type, $prompt, $content, 1);
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Get analytics data for a user
     */
    public static function get_user_analytics($user_id) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        $transactions_table = $wpdb->prefix . 'rakubun_transactions';
        
        // Check if tables exist
        $content_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'");
        $transactions_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$transactions_table'");
        
        if (!$content_table_exists || !$transactions_table_exists) {
            // Return default analytics if tables don't exist
            return array(
                'total_articles' => 0,
                'total_images' => 0,
                'recent_articles' => 0,
                'recent_images' => 0,
                'total_spent' => 0,
                'monthly_usage' => array()
            );
        }
        
        // Get total generated content counts
        $total_articles = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'article' AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        $total_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'image' AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        // Get recent activity (last 7 days)
        $recent_articles = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'article' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $user_id
        )) ?: 0;
        
        $recent_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'image' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $user_id
        )) ?: 0;
        
        // Get total spending
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $transactions_table WHERE user_id = %d AND status = 'completed'",
            $user_id
        )) ?: 0;
        
        // Get monthly usage for trend
        $monthly_usage = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%%Y-%%m') as month,
                COUNT(CASE WHEN content_type = 'article' THEN 1 END) as articles,
                COUNT(CASE WHEN content_type = 'image' THEN 1 END) as images
             FROM $content_table 
             WHERE user_id = %d AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%%Y-%%m')
             ORDER BY month DESC",
            $user_id
        )) ?: array();
        
        return array(
            'total_articles' => (int) $total_articles,
            'total_images' => (int) $total_images,
            'recent_articles' => (int) $recent_articles,
            'recent_images' => (int) $recent_images,
            'total_spent' => (float) $total_spent,
            'monthly_usage' => $monthly_usage
        );
    }

    /**
     * Get recent generated content for a user
     */
    public static function get_recent_content($user_id, $limit = 10, $type = 'all') {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'");
        if (!$table_exists) {
            return array();
        }
        
        $where_clause = "WHERE user_id = %d AND status = 'completed'";
        $params = array($user_id);
        
        if ($type !== 'all' && in_array($type, array('article', 'image'))) {
            $where_clause .= " AND content_type = %s";
            $params[] = $type;
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, content_type, prompt, generated_content, image_url, post_id, created_at 
             FROM $content_table 
             $where_clause 
             ORDER BY created_at DESC 
             LIMIT %d",
            array_merge($params, array($limit))
        ));
        
        return $results ?: array();
    }

    /**
     * Get user images for gallery
     */
    public static function get_user_images($user_id, $limit = 20) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'");
        if (!$table_exists) {
            return array();
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, prompt, image_url, created_at 
             FROM $content_table 
             WHERE user_id = %d AND content_type = 'image' AND status = 'completed' AND image_url IS NOT NULL
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id, $limit
        ));
        
        return $results ?: array();
    }

    /**
     * Get all transactions for a user
     */
    public static function get_user_transactions($user_id, $limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_transactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }

    /**
     * Clean old transactions (older than 1 year)
     */
    public static function cleanup_old_data() {
        global $wpdb;
        
        // Clean old transactions
        $transactions_table = $wpdb->prefix . 'rakubun_transactions';
        $wpdb->query("DELETE FROM $transactions_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        
        // Clean old generated content (keep only metadata, remove content)
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        $wpdb->query(
            "UPDATE $content_table 
             SET generated_content = '[Archived]' 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH) 
             AND generated_content != '[Archived]'"
        );
    }

    /**
     * Get rewrite statistics for auto-rewrite page
     */
    public static function get_rewrite_statistics($user_id) {
        global $wpdb;
        $rewrite_table = $wpdb->prefix . 'rakubun_rewrite_history';
        
        // Check if rewrite history table exists
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $rewrite_table));
        if (!$table_exists) {
            // Create the table if it doesn't exist
            self::create_rewrite_history_table();
            
            // Return default stats if table was just created
            return array(
                'total_rewrites' => 0,
                'characters_added' => 0,
                'seo_improvements' => 0,
                'recent_rewrites' => array()
            );
        }
        
        // Get total rewrites with error handling
        $total_rewrites = 0;
        try {
            $total_rewrites = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $rewrite_table WHERE user_id = %d AND status = 'completed'",
                $user_id
            )) ?: 0;
        } catch (Exception $e) {
            error_log('Rakubun AI: Error getting total rewrites - ' . $e->getMessage());
        }
        
        // Get total characters added with error handling
        $characters_added = 0;
        try {
            $characters_added = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(character_change) FROM $rewrite_table WHERE user_id = %d AND status = 'completed' AND character_change > 0",
                $user_id
            )) ?: 0;
        } catch (Exception $e) {
            error_log('Rakubun AI: Error getting characters added - ' . $e->getMessage());
        }
        
        // Get total SEO improvements with error handling
        $seo_improvements = 0;
        try {
            $seo_improvements = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(seo_improvements) FROM $rewrite_table WHERE user_id = %d AND status = 'completed'",
                $user_id
            )) ?: 0;
        } catch (Exception $e) {
            error_log('Rakubun AI: Error getting SEO improvements - ' . $e->getMessage());
        }
        
        // Get recent rewrites with error handling
        $recent_rewrites = array();
        try {
            $recent_rewrites = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, p.post_title 
                 FROM $rewrite_table r 
                 LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID 
                 WHERE r.user_id = %d AND r.status = 'completed' 
                 ORDER BY r.rewrite_date DESC 
                 LIMIT 10",
                $user_id
            )) ?: array();
        } catch (Exception $e) {
            error_log('Rakubun AI: Error getting recent rewrites - ' . $e->getMessage());
            $recent_rewrites = array();
        }
        
        return array(
            'total_rewrites' => (int) $total_rewrites,
            'characters_added' => (int) $characters_added,
            'seo_improvements' => (int) $seo_improvements,
            'recent_rewrites' => $recent_rewrites
        );
    }

    /**
     * Create rewrite history table if it doesn't exist
     */
    private static function create_rewrite_history_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rakubun_rewrite_history';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            original_content longtext,
            rewritten_content longtext,
            character_change int(11) DEFAULT 0,
            seo_improvements int(11) DEFAULT 0,
            rewrite_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'completed',
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        // Log any database errors
        if ($wpdb->last_error) {
            error_log('Rakubun AI: Error creating rewrite history table - ' . $wpdb->last_error);
        }
        
        return $result;
    }

    /**
     * Create credits table if it doesn't exist
     */
    private static function create_credits_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            article_credits int(11) NOT NULL DEFAULT 3,
            image_credits int(11) NOT NULL DEFAULT 5,
            rewrite_credits int(11) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Record a rewrite activity
     */
    public static function record_rewrite($user_id, $post_id, $original_content, $rewritten_content, $seo_improvements = 0) {
        global $wpdb;
        $rewrite_table = $wpdb->prefix . 'rakubun_rewrite_history';
        
        $post = get_post($post_id);
        $character_change = strlen($rewritten_content) - strlen($original_content);
        
        $result = $wpdb->insert(
            $rewrite_table,
            array(
                'user_id' => $user_id,
                'post_id' => $post_id,
                'original_content' => $original_content,
                'rewritten_content' => $rewritten_content,
                'character_change' => $character_change,
                'seo_improvements' => $seo_improvements,
                'status' => 'completed',
                'rewrite_date' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Get posts that will be rewritten next based on current schedule
     */
    public static function get_scheduled_rewrite_posts($limit = 10, $user_id = null) {
        global $wpdb;
        
        $posts_table = $wpdb->posts;
        $rewrite_table = $wpdb->prefix . 'rakubun_rewrite_history';
        
        $schedule = get_option('rakubun_ai_rewrite_schedule', array());
        $target_post_age = intval($schedule['target_post_age'] ?? 6);
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$target_post_age} months"));
        $recent_rewrite_threshold = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Get posts that are old enough and haven't been rewritten recently
        $query = $wpdb->prepare("
            SELECT p.ID, p.post_title, p.post_modified, p.post_content,
                   COALESCE(r.rewrite_date, NULL) as last_rewrite_date
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
}