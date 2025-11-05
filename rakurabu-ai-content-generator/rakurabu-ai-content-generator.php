<?php
/**
 * Plugin Name: Rakurabu AI Content Generator
 * Plugin URI: https://github.com/brocketdesign/rakurabu-wordpress-plugin
 * Description: Generate AI-powered articles and images using OpenAI GPT-4 and DALL-E. Includes Stripe payment integration for purchasing credits.
 * Version: 1.0.0
 * Author: Brocket Design
 * Author URI: https://github.com/brocketdesign
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: rakurabu-ai
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Plugin version
define('RAKURABU_AI_VERSION', '1.0.0');

// Plugin directory path
define('RAKURABU_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Plugin directory URL
define('RAKURABU_AI_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_rakurabu_ai_content_generator() {
    require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-activator.php';
    Rakurabu_AI_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_rakurabu_ai_content_generator() {
    require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-deactivator.php';
    Rakurabu_AI_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_rakurabu_ai_content_generator');
register_deactivation_hook(__FILE__, 'deactivate_rakurabu_ai_content_generator');

/**
 * The core plugin class
 */
require RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-content-generator.php';

/**
 * Begins execution of the plugin.
 */
function run_rakurabu_ai_content_generator() {
    $plugin = new Rakurabu_AI_Content_Generator();
    $plugin->run();
}

run_rakurabu_ai_content_generator();
