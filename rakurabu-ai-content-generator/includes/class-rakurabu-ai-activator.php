<?php
/**
 * Fired during plugin activation
 */
class Rakurabu_AI_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rakurabu_user_credits';
        
        // Create table for tracking user credits
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            article_credits int(11) NOT NULL DEFAULT 3,
            image_credits int(11) NOT NULL DEFAULT 5,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create transactions table
        $transactions_table = $wpdb->prefix . 'rakurabu_transactions';
        $sql_transactions = "CREATE TABLE IF NOT EXISTS $transactions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            transaction_type varchar(50) NOT NULL,
            amount decimal(10,2) NOT NULL,
            credits_purchased int(11) NOT NULL,
            credit_type varchar(20) NOT NULL,
            stripe_payment_id varchar(255),
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql_transactions);
        
        // Create generated content table
        $content_table = $wpdb->prefix . 'rakurabu_generated_content';
        $sql_content = "CREATE TABLE IF NOT EXISTS $content_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_type varchar(20) NOT NULL,
            post_id bigint(20),
            prompt text,
            generated_content longtext,
            image_url varchar(500),
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql_content);
        
        // Set default options
        add_option('rakurabu_ai_openai_api_key', '');
        add_option('rakurabu_ai_stripe_public_key', '');
        add_option('rakurabu_ai_stripe_secret_key', '');
        add_option('rakurabu_ai_article_price', '5.00');
        add_option('rakurabu_ai_image_price', '2.00');
        add_option('rakurabu_ai_articles_per_purchase', '10');
        add_option('rakurabu_ai_images_per_purchase', '20');
    }
}
