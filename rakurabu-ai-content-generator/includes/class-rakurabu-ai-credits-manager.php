<?php
/**
 * Manages user credits for articles and images
 */
class Rakurabu_AI_Credits_Manager {

    /**
     * Get user credits
     */
    public static function get_user_credits($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakurabu_user_credits';
        
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
        $table_name = $wpdb->prefix . 'rakurabu_user_credits';
        
        $column = $type === 'article' ? 'article_credits' : 'image_credits';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET $column = $column - %d WHERE user_id = %d AND $column >= %d",
            $amount,
            $user_id,
            $amount
        ));
        
        return $result > 0;
    }

    /**
     * Add credits to user
     */
    public static function add_credits($user_id, $type = 'article', $amount = 1) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakurabu_user_credits';
        
        $column = $type === 'article' ? 'article_credits' : 'image_credits';
        
        // Ensure user has a credits record
        $credits = self::get_user_credits($user_id);
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET $column = $column + %d WHERE user_id = %d",
            $amount,
            $user_id
        ));
        
        return $result > 0;
    }

    /**
     * Log transaction
     */
    public static function log_transaction($user_id, $type, $amount, $credits, $credit_type, $stripe_id = '', $status = 'completed') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakurabu_transactions';
        
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
    public static function log_generated_content($user_id, $type, $prompt, $content, $image_url = '', $post_id = 0) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'rakurabu_generated_content';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'content_type' => $type,
                'post_id' => $post_id,
                'prompt' => $prompt,
                'generated_content' => $content,
                'image_url' => $image_url,
                'status' => 'completed'
            ),
            array('%d', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        return $wpdb->insert_id;
    }
}
