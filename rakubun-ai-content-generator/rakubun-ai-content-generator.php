<?php
/**
 * Plugin Name: Rakubun AI Content Generator
 * Plugin URI: https://github.com/brocketdesign/rakubun-wordpress-plugin
 * Description: Generate AI-powered articles and images using OpenAI GPT-4 and DALL-E. Includes Stripe payment integration for purchasing credits.
 * Version: 2.1.0
 * Author: Brocket Design
 * Author URI: https://github.com/brocketdesign
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rakubun-ai
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('RAKUBUN_AI_VERSION', '2.1.0');

// Plugin directory path
define('RAKUBUN_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Plugin directory URL
define('RAKUBUN_AI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_rakubun_ai_content_generator() {
    require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-activator.php';
    Rakubun_AI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_rakubun_ai_content_generator() {
    require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-deactivator.php';
    Rakubun_AI_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_rakubun_ai_content_generator');
register_deactivation_hook(__FILE__, 'deactivate_rakubun_ai_content_generator');

/**
 * Handle scheduled registration attempt
 */
add_action('rakubun_ai_attempt_registration', array('Rakubun_AI_Activator', 'attempt_registration'));

/**
 * Handle scheduled analytics sync
 */
add_action('rakubun_ai_sync_analytics', function() {
    require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
    $external_api = new Rakubun_AI_External_API();
    $external_api->send_analytics();
});

/**
 * Load webhook handler for dashboard integration
 */
require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-webhook-handler.php';

/**
 * The core plugin class
 */
require RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-content-generator.php';

/**
 * Begins execution of the plugin.
 */
function run_rakubun_ai_content_generator() {
    $plugin = new Rakubun_AI_Content_Generator();
    $plugin->run();
}

run_rakubun_ai_content_generator();
