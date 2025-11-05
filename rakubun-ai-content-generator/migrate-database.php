<?php
/**
 * Database Migration Script for Rakubun AI Content Generator
 * Run this once to add the attachment_id column to existing installations
 */

// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Only run if admin user
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

global $wpdb;

// Add attachment_id column if it doesn't exist
$content_table = $wpdb->prefix . 'rakubun_generated_content';
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM $content_table LIKE 'attachment_id'");

if (empty($column_exists)) {
    $result = $wpdb->query("ALTER TABLE $content_table ADD COLUMN attachment_id bigint(20) DEFAULT 0 AFTER post_id");
    
    if ($result !== false) {
        echo '<div class="notice notice-success"><p>✅ Database migration completed successfully! The attachment_id column has been added.</p></div>';
        
        // Update db version
        update_option('rakubun_ai_db_version', '1.1');
    } else {
        echo '<div class="notice notice-error"><p>❌ Database migration failed. Error: ' . $wpdb->last_error . '</p></div>';
    }
} else {
    echo '<div class="notice notice-info"><p>ℹ️ Database is already up to date. No migration needed.</p></div>';
}

echo '<p><a href="' . admin_url('admin.php?page=rakubun-ai-content') . '" class="button button-primary">← Back to Dashboard</a></p>';
?>