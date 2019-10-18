<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/includes
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WhatsAppMe_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->version     = defined( 'WHATSAPPME_VERSION' ) ? WHATSAPPME_VERSION : '1.0.0';
		$this->plugin_name = 'whatsappme';

		$this->load_dependencies();
		$this->set_locale();
		$this->load_integrations();
		is_admin() ? $this->define_admin_hooks() : $this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WhatsAppMe_Loader. Orchestrates the hooks of the plugin.
	 * - WhatsAppMe_i18n. Defines internationalization functionality.
	 * - WhatsAppMe_Admin. Defines all hooks for the admin area.
	 * - WhatsAppMe_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-whatsappme-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-whatsappme-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-whatsappme-integrations.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-whatsappme-util.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-whatsappme-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-whatsappme-public.php';

		$this->loader = new WhatsAppMe_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WhatsAppMe_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WhatsAppMe_i18n();

		// No delegate to $this->loader, use WordPress add_action
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Load third party plugins integrations
	 *
	 * @since    3.0.0
	 * @access   private
	 */
	private function load_integrations() {

		$plugin_integrations = new WhatsAppMe_Integrations();

		// No delegate to $this->loader, use WordPress add_action.
		// At 'plugins_loaded' hook can determine if other plugins are present.
		add_action( 'plugins_loaded', array( $plugin_integrations, 'load_integrations' ) );

	}


	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WhatsAppMe_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'get_settings', 5 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_init' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_post' );
		$this->loader->add_action( 'load-settings_page_whatsappme', $plugin_admin, 'help_tab' );

		$this->loader->add_filter( "plugin_action_links_creame-whatsapp-me/{$this->plugin_name}.php", $plugin_admin, 'settings_link' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WhatsAppMe_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp', $plugin_public, 'get_settings' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'elementor/preview/init', $plugin_public, 'elementor_preview_disable' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @since    3.0.0     Added actions
	 */
	public function run() {

		do_action( 'whatsappme_run_pre', $this );

		$this->loader->run();

		do_action( 'whatsappme_run_pos', $this );

	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WhatsAppMe_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
