<?php
/**
 * Debug script to check credit system
 * Add this temporarily to debug the issue
 */

// Add this to your admin page or create a temporary debug endpoint
function rakubun_debug_credits() {
    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'rakubun_user_credits';
    
    echo "<h3>Debug Credit Information</h3>";
    echo "<p>User ID: " . $user_id . "</p>";
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    echo "<p>Table exists: " . ($table_exists ? 'Yes' : 'No') . "</p>";
    
    if ($table_exists) {
        // Show table structure
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        echo "<p>Table columns:</p><ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col->Field . " (" . $col->Type . ")</li>";
        }
        echo "</ul>";
        
        // Show user record
        $user_record = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d", 
            $user_id
        ));
        
        if ($user_record) {
            echo "<p>User record:</p><pre>";
            print_r($user_record);
            echo "</pre>";
        } else {
            echo "<p>No user record found</p>";
        }
        
        // Show all records (for debugging)
        $all_records = $wpdb->get_results("SELECT * FROM $table_name");
        echo "<p>All records:</p><pre>";
        print_r($all_records);
        echo "</pre>";
    }
    
    // Show what get_user_credits returns
    $credits = Rakubun_AI_Credits_Manager::get_user_credits($user_id);
    echo "<p>get_user_credits() returns:</p><pre>";
    print_r($credits);
    echo "</pre>";
}

// Call this function in your dashboard or add it as a menu item temporarily
// rakubun_debug_credits();
?>