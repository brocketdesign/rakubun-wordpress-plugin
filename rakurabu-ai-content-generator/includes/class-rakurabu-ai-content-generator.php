<?php
/**
 * The core plugin class
 */
class Rakurabu_AI_Content_Generator {

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
        $this->version = RAKURABU_AI_VERSION;
        $this->plugin_name = 'rakurabu-ai-content-generator';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies
     */
    private function load_dependencies() {
        require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-loader.php';
        require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-credits-manager.php';
        require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-openai.php';
        require_once RAKURABU_AI_PLUGIN_DIR . 'includes/class-rakurabu-ai-stripe.php';
        require_once RAKURABU_AI_PLUGIN_DIR . 'admin/class-rakurabu-ai-admin.php';

        $this->loader = new Rakurabu_AI_Loader();
    }

    /**
     * Register all hooks related to admin area
     */
    private function define_admin_hooks() {
        $plugin_admin = new Rakurabu_AI_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('wp_ajax_rakurabu_generate_article', $plugin_admin, 'ajax_generate_article');
        $this->loader->add_action('wp_ajax_rakurabu_generate_image', $plugin_admin, 'ajax_generate_image');
        $this->loader->add_action('wp_ajax_rakurabu_process_payment', $plugin_admin, 'ajax_process_payment');
        $this->loader->add_action('wp_ajax_rakurabu_get_credits', $plugin_admin, 'ajax_get_credits');
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
}
