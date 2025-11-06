<?php
/**
 * Fired during plugin deactivation
 */
class Rakubun_AI_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook('rakubun_ai_cleanup_old_content');
    }
}
