<?php
/**
 * Fired during plugin activation
 */
class Rakubun_AI_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'rakubun_user_credits';
        
        // Create table for tracking user credits
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
        
        // Create transactions table
        $transactions_table = $wpdb->prefix . 'rakubun_transactions';
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
        $content_table = $wpdb->prefix . 'rakubun_generated_content';
        $sql_content = "CREATE TABLE IF NOT EXISTS $content_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            content_type varchar(20) NOT NULL,
            post_id bigint(20),
            attachment_id bigint(20),
            prompt text,
            generated_content longtext,
            image_url varchar(500),
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql_content);
        
        // Create rewrite statistics table
        $rewrite_table = $wpdb->prefix . 'rakubun_rewrite_history';
        $sql_rewrite = "CREATE TABLE IF NOT EXISTS $rewrite_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            post_title varchar(255),
            original_content longtext,
            rewritten_content longtext,
            character_change int(11) DEFAULT 0,
            seo_improvements int(11) DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'completed',
            rewrite_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY rewrite_date (rewrite_date)
        ) $charset_collate;";
        
        dbDelta($sql_rewrite);
        
        // Migration: Add rewrite_credits column if it doesn't exist
        $rewrite_column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table_name LIKE 'rewrite_credits'");
        if (empty($rewrite_column_exists)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN rewrite_credits int(11) NOT NULL DEFAULT 0 AFTER image_credits");
        }
        
        // Migration: Add attachment_id column if it doesn't exist
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $content_table LIKE 'attachment_id'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $content_table ADD COLUMN attachment_id bigint(20) AFTER post_id");
        }
        
        // Set default options (keep for backward compatibility but not used)
        add_option('rakubun_ai_openai_api_key', '');
        add_option('rakubun_ai_stripe_public_key', '');
        add_option('rakubun_ai_stripe_secret_key', '');
        add_option('rakubun_ai_article_price', '750');
        add_option('rakubun_ai_image_price', '300');
        add_option('rakubun_ai_articles_per_purchase', '10');
        add_option('rakubun_ai_images_per_purchase', '20');
        
        // Set activation date
        add_option('rakubun_ai_activation_date', current_time('mysql'));
        
        // Initialize external API registration
        self::initialize_external_connection();
        
        // Migration: Update existing USD prices to JPY
        self::migrate_currency_to_jpy();
        
        // Schedule analytics sync
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        Rakubun_AI_External_API::schedule_analytics_sync();
    }
    
    /**
     * Initialize connection with external dashboard
     * Attempts immediate registration on plugin activation
     */
    private static function initialize_external_connection() {
        // Set initial registration status
        add_option('rakubun_ai_registration_status', 'not_registered');
        add_option('rakubun_ai_status', 'enabled');
        
        // Generate unique instance ID if not exists
        $instance_id = get_option('rakubun_ai_instance_id');
        if (!$instance_id) {
            $instance_id = wp_generate_uuid4();
            add_option('rakubun_ai_instance_id', $instance_id);
        }
        
        // Attempt immediate registration during activation
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();
        
        if ($external_api->register_plugin()) {
            // Registration successful
            update_option('rakubun_ai_registration_status', 'registered');
            error_log('Rakubun AI: Plugin auto-registered with dashboard on activation');
        } else {
            // Registration failed, schedule a retry in the background
            // The admin can also manually trigger registration from settings page
            update_option('rakubun_ai_registration_status', 'pending');
            wp_schedule_single_event(time() + 300, 'rakubun_ai_attempt_registration');
            error_log('Rakubun AI: Initial registration attempt failed, scheduled retry');
        }
    }
    
    /**
     * Attempt registration with external dashboard (background task)
     * Called as a fallback if immediate registration fails
     */
    public static function attempt_registration() {
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        $external_api = new Rakubun_AI_External_API();
        
        if ($external_api->register_plugin()) {
            // Registration successful
            update_option('rakubun_ai_registration_status', 'registered');
            error_log('Rakubun AI: Scheduled registration retry succeeded');
        } else {
            // Registration failed, will try again manually through admin
            update_option('rakubun_ai_registration_status', 'failed');
            error_log('Rakubun AI: Scheduled registration retry failed');
        }
    }
    
    /**
     * Migrate existing USD prices to JPY
     */
    private static function migrate_currency_to_jpy() {
        // Check if migration was already done
        if (get_option('rakubun_ai_currency_migrated_to_jpy', false)) {
            return;
        }
        
        // Update article price from $5.00 to ¥750
        $current_article_price = get_option('rakubun_ai_article_price', 750);
        if ($current_article_price <= 10) { // If it's still in USD range
            update_option('rakubun_ai_article_price', 750);
        }
        
        // Update image price from $2.00 to ¥300  
        $current_image_price = get_option('rakubun_ai_image_price', 300);
        if ($current_image_price <= 10) { // If it's still in USD range
            update_option('rakubun_ai_image_price', 300);
        }
        
        // Mark migration as completed
        update_option('rakubun_ai_currency_migrated_to_jpy', true);
    }
}
