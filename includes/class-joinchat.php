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
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      JoinchatLoader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Common settings along all plugin parts
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      JoinchatCommon    $common    Front and Back common class.
	 */
	protected $common;

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

		$this->version     = defined( 'JOINCHAT_VERSION' ) ? JOINCHAT_VERSION : '1.0.0';
		$this->plugin_name = 'joinchat';

		$this->load_dependencies();
		$this->set_locale();
		$this->load_integrations();

		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->define_premium_hooks();

		// WordPress 5.9 or higher.
		$this->define_gutenberg_hooks();

		add_action( 'joinchat_run_pre', array( $this, 'define_preview_hooks' ) );

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - JoinchatLoader. Orchestrates the hooks of the plugin.
	 * - Joinchat_i18n. Defines internationalization functionality.
	 * - JoinchatIntegrations. Defines thrid party integrations.
	 * - JoinchatUtil. Defines common utilities.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_dependencies() {

		require_once JOINCHAT_DIR . 'includes/class-joinchat-loader.php';
		require_once JOINCHAT_DIR . 'includes/class-joinchat-common.php';
		require_once JOINCHAT_DIR . 'includes/class-joinchat-i18n.php';
		require_once JOINCHAT_DIR . 'includes/class-joinchat-integrations.php';
		require_once JOINCHAT_DIR . 'includes/class-joinchat-util.php';

		$this->loader = new JoinchatLoader();
		$this->common = JoinchatCommon::instance();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Joinchat_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function set_locale() {

		$plugin_i18n = new Joinchat_i18n( $this->loader );

	}

	/**
	 * Load third party plugins integrations
	 *
	 * @since    3.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_integrations() {

		$plugin_integrations = new JoinchatIntegrations();

		// No delegate to $this->loader, use WordPress add_action.
		// At 'plugins_loaded' hook can determine if other plugins are present.
		add_action( 'plugins_loaded', array( $plugin_integrations, 'load_integrations' ) );

	}

	/**
	 * Register all of the hooks related to gutenberg functionality
	 * of the plugin.
	 *
	 * @since    4.5.0
	 * @access   private
	 * @return   void
	 */
	private function define_gutenberg_hooks() {

		if ( ! JoinchatUtil::can_gutenberg() ) {
			return;
		}

		require_once JOINCHAT_DIR . 'gutenberg/class-joinchat-gutenberg.php';

		$plugin_gutenberg = new JoinchatGutenberg( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init', $plugin_gutenberg, 'register_meta', 11 );
		$this->loader->add_action( 'init', $plugin_gutenberg, 'register_blocks', 11 );

		$this->loader->add_action( 'admin_init', $plugin_gutenberg, 'register_patterns' );
		$this->loader->add_action( 'enqueue_block_editor_assets', $plugin_gutenberg, 'enqueue_editor_assets' );

		$this->loader->add_action( 'wp_footer', $plugin_gutenberg, 'root_styles', 100 );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_admin_hooks() {

		if ( ! is_admin() ) {
			return;
		}

		require_once JOINCHAT_DIR . 'admin/class-joinchat-admin.php';
		require_once JOINCHAT_DIR . 'admin/class-joinchat-admin-page.php';

		$this->loader->add_filter( 'option_page_capability_joinchat', 'JoinchatUtil', 'capability' );

		$plugin_admin = new JoinchatAdmin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_scripts' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'notices' );
		$this->loader->add_action( 'wp_ajax_joinchat_notice_dismiss', $plugin_admin, 'ajax_notice_dismiss' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'add_term_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta', 10, 2 );
		$this->loader->add_action( 'update_option_joinchat', $plugin_admin, 'clear_cache', 100 );
		$this->loader->add_filter( 'plugin_action_links_' . JOINCHAT_BASENAME, $plugin_admin, 'settings_link' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_links', 10, 2 );

		$plugin_page = new JoinchatAdminPage( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_init', $plugin_page, 'settings_init' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_page, 'enqueue_assets' );
		$this->loader->add_action( 'in_admin_header', $plugin_page, 'admin_header' );
		$this->loader->add_action( 'admin_menu', $plugin_page, 'add_menu' );
		$this->loader->add_action( 'load-settings_page_joinchat', $plugin_page, 'help_tab' );
		$this->loader->add_action( 'load-toplevel_page_joinchat', $plugin_page, 'help_tab' );
		$this->loader->add_filter( 'admin_title', $plugin_page, 'admin_title' );
		$this->loader->add_filter( 'admin_footer_text', $plugin_page, 'admin_footer_text', PHP_INT_MAX );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_public_hooks() {

		global $pagenow;

		if ( is_admin() || 'wp-login.php' === $pagenow ) {
			return;
		}

		require_once JOINCHAT_DIR . 'public/class-joinchat-public.php';

		$plugin_public = new JoinchatPublic( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'joinchat_settings', $plugin_public, 'get_settings' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'enqueue_qr_script', 5 );

		// Actions (only) for preview
		$this->loader->add_action( 'preview_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'preview_footer', $plugin_public, 'enqueue_qr_script', 5 );

	}

	/**
	 * Register all of the hooks related to render Joinchat preview.
	 *
	 * Run on "init" to check user capability
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function define_preview_hooks() {

		if ( ! isset( $_GET['joinchat-preview'] ) ) {
			return;
		}

		if ( ! current_user_can( JoinchatUtil::capability() ) ) {
			return;
		}

		$this->common->set_preview( true );
		$this->common->qr = true;

		require_once JOINCHAT_DIR . 'admin/class-joinchat-preview.php';

		$plugin_preview = new JoinchatPreview( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'template_include', $plugin_preview, 'blank_template', PHP_INT_MAX );
		$this->loader->add_filter( 'show_admin_bar', $plugin_preview, 'hide_admin_bar', 1000 );
		$this->loader->add_filter( 'joinchat_show', $plugin_preview, 'always_show', 1000 );
		$this->loader->add_filter( 'joinchat_classes', $plugin_preview, 'preview_classes', 10, 2 );
		$this->loader->add_filter( 'joinchat_inline_style', $plugin_preview, 'inline_style' );

		$this->loader->add_action( 'wp_print_scripts', $plugin_preview, 'remove_all_scripts', 1000 );
		$this->loader->add_action( 'wp_print_styles', $plugin_preview, 'remove_all_styles', 1000 );

	}

	/**
	 * Register all of the hooks related to the premium info.
	 *
	 * @since    5.0.0
	 * @access   private
	 * @return   void
	 */
	private function define_premium_hooks() {

		if ( ! is_admin() ) {
			return;
		}

		require_once JOINCHAT_DIR . 'admin/class-joinchat-premium.php';

		$plugin_premium = new JoinchatPremium( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_filter( 'plugin_action_links_' . JOINCHAT_BASENAME, $plugin_premium, 'action_link' );
		$this->loader->add_filter( 'joinchat_admin_tabs', $plugin_premium, 'admin_tab', 1000 );
		$this->loader->add_filter( 'joinchat_tab_premium_sections', $plugin_premium, 'tab_sections' );
		$this->loader->add_filter( 'joinchat_section_output', $plugin_premium, 'section_ouput', 10, 2 );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 * @since    3.0.0     Added actions
	 * @return   void
	 */
	public function run() {

		do_action( 'joinchat_run_pre', $this );

		$this->loader->run();

		do_action( 'joinchat_run_pos', $this );

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
	 * @return    JoinchatLoader    Orchestrates the hooks of the plugin.
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
