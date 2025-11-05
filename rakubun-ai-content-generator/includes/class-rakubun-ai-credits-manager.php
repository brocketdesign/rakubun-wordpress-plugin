<?php
/**
 * Manages user credits for articles and images
 */
class Rakubun_AI_Credits_Manager {

    /**
     * Get user credits
     */
    public static function get_user_credits($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        $credits = $wpdb->get_row($wpdb->prepare(
            "SELECT article_credits, image_credits FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // If user doesn't have credits record, create one with free credits
        if (!$credits) {
            $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'article_credits' => 3,
                    'image_credits' => 5
                ),
                array('%d', '%d', '%d')
            );
            
            return array(
                'article_credits' => 3,
                'image_credits' => 5
            );
        }
        
        return array(
            'article_credits' => (int) $credits->article_credits,
            'image_credits' => (int) $credits->image_credits
        );
    }

    /**
     * Check if user has credits
     */
    public static function has_credits($user_id, $type = 'article') {
        $credits = self::get_user_credits($user_id);
        
        if ($type === 'article') {
            return $credits['article_credits'] > 0;
        } else {
            return $credits['image_credits'] > 0;
        }
    }

    /**
     * Deduct credits from user
     */
    public static function deduct_credits($user_id, $type = 'article', $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Validate type parameter against whitelist to prevent SQL injection
        if (!in_array($type, array('article', 'image'), true)) {
            return false;
        }
        
        // Use separate queries for each type to avoid dynamic column names
        if ($type === 'article') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET article_credits = article_credits - %d WHERE user_id = %d AND article_credits >= %d",
                $amount,
                $user_id,
                $amount
            ));
        } else {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET image_credits = image_credits - %d WHERE user_id = %d AND image_credits >= %d",
                $amount,
                $user_id,
                $amount
            ));
        }
        
        return $result > 0;
    }

    /**
     * Add credits to user
     */
    public static function add_credits($user_id, $type = 'article', $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Validate type parameter against whitelist to prevent SQL injection
        if (!in_array($type, array('article', 'image'), true)) {
            return false;
        }
        
        // Ensure user has a credits record
        $credits = self::get_user_credits($user_id);
        
        // Use separate queries for each type to avoid dynamic column names
        if ($type === 'article') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET article_credits = article_credits + %d WHERE user_id = %d",
                $amount,
                $user_id
            ));
        } else {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name SET image_credits = image_credits + %d WHERE user_id = %d",
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
        
        $wpdb->insert(
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
        
        return $wpdb->insert_id;
    }

    /**
     * Get analytics data for a user
     */
    public static function get_user_analytics($user_id) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        $transactions_table = $wpdb->prefix . 'rakubun_transactions';
        
        // Get total generated content counts
        $total_articles = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'article' AND status = 'completed'",
            $user_id
        ));
        
        $total_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'image' AND status = 'completed'",
            $user_id
        ));
        
        // Get recent activity (last 7 days)
        $recent_articles = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'article' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $user_id
        ));
        
        $recent_images = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $content_table WHERE user_id = %d AND content_type = 'image' AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            $user_id
        ));
        
        // Get total spending
        $total_spent = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM $transactions_table WHERE user_id = %d AND status = 'completed'",
            $user_id
        ));
        
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
        ));
        
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
        
        return $results;
    }

    /**
     * Get generated images for gallery
     */
    public static function get_user_images($user_id, $limit = 50) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, prompt, image_url, created_at 
             FROM $content_table 
             WHERE user_id = %d AND content_type = 'image' AND status = 'completed' AND image_url != ''
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
        
        return $results;
    }

    /**
     * Get content by ID for regeneration
     */
    public static function get_content_by_id($content_id, $user_id) {
        global $wpdb;
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $content_table WHERE id = %d AND user_id = %d",
            $content_id,
            $user_id
        ));
        
        return $result;
    }
}
