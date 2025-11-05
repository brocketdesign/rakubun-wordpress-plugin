<?php
/**
 * The core plugin class
 */
class Rakubun_AI_Content_Generator {

    /**
     * The loader that's responsible for maintaining and registering all hooks
     */
    protected $loader;

    /**
     * The unique identifier of this plugin
     */
    protected $plugin_name;

    /**
     * The current version of the plugin
     */
    protected $version;

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->version = RAKUBUN_AI_VERSION;
        $this->plugin_name = 'rakubun-ai-content-generator';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies
     */
    private function load_dependencies() {
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-loader.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-external-api.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-credits-manager.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-openai.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-auto-rewriter.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'includes/class-rakubun-ai-stripe.php';
        require_once RAKUBUN_AI_PLUGIN_DIR . 'admin/class-rakubun-ai-admin.php';

        $this->loader = new Rakubun_AI_Loader();
    }

    /**
     * Register all hooks related to admin area
     */
    private function define_admin_hooks() {
        $plugin_admin = new Rakubun_AI_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('wp_ajax_rakubun_generate_article', $plugin_admin, 'ajax_generate_article');
        $this->loader->add_action('wp_ajax_rakubun_generate_image', $plugin_admin, 'ajax_generate_image');
        $this->loader->add_action('wp_ajax_rakubun_create_payment_intent', $plugin_admin, 'ajax_create_payment_intent');
        $this->loader->add_action('wp_ajax_rakubun_process_payment', $plugin_admin, 'ajax_process_payment');
        $this->loader->add_action('wp_ajax_rakubun_get_credits', $plugin_admin, 'ajax_get_credits');
        $this->loader->add_action('wp_ajax_rakubun_regenerate_image', $plugin_admin, 'ajax_regenerate_image');
        $this->loader->add_action('wp_ajax_rakubun_get_analytics', $plugin_admin, 'ajax_get_analytics');
        
        // Add custom cron schedules
        $this->loader->add_filter('cron_schedules', $this, 'add_custom_cron_schedules');
    }

    /**
     * Register all hooks related to public-facing functionality
     */
    private function define_public_hooks() {
        // Public hooks can be added here if needed
    }

    /**
     * Run the loader to execute all hooks
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Add custom cron schedules for auto-rewriting
     */
    public function add_custom_cron_schedules($schedules) {
        $schedules['weekly'] = array(
            'interval' => 604800, // 1 week
            'display' => __('Weekly')
        );
        $schedules['monthly'] = array(
            'interval' => 2635200, // 1 month (approximately)
            'display' => __('Monthly')
        );
        return $schedules;
    }
}
