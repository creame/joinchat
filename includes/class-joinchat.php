<?php
/**
 * The core plugin class.
 *
 * @package    Joinchat
 */

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
	 * @var      Joinchat_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

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
	 * - Joinchat_Loader. Orchestrates the hooks of the plugin.
	 * - Joinchat_i18n. Defines internationalization functionality.
	 * - Joinchat_Integrations. Defines thrid party integrations.
	 * - Joinchat_Util. Defines common utilities.
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
		require_once JOINCHAT_DIR . 'includes/class-joinchat-util-deprecated.php'; // TODO: deprecation to be deleted.

		$this->loader = new Joinchat_Loader();
		jc_common(); // Instance Joinchat_Common.

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

		$plugin_i18n = new Joinchat_I18n( $this->loader );

	}

	/**
	 * Load third party plugins integrations
	 *
	 * @since    3.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_integrations() {

		$plugin_integrations = new Joinchat_Integrations();

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

		if ( ! Joinchat_Util::can_gutenberg() ) {
			return;
		}

		require_once JOINCHAT_DIR . 'gutenberg/class-joinchat-gutenberg.php';

		$plugin_gutenberg = new Joinchat_Gutenberg();

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
		require_once JOINCHAT_DIR . 'admin/class-joinchat-admin-onboard.php';

		$this->loader->add_filter( 'option_page_capability_joinchat', 'Joinchat_Util', 'capability' );

		$plugin_admin = new Joinchat_Admin();

		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_setting' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_scripts' );
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'notices' );
		$this->loader->add_action( 'wp_ajax_joinchat_notice_dismiss', $plugin_admin, 'ajax_notice_dismiss' );
		// Post meta.
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_meta', 10, 2 );
		// Term meta.
		$this->loader->add_action( 'load-term.php', $plugin_admin, 'add_term_meta_boxes' );
		$this->loader->add_action( 'load-edit-tags.php', $plugin_admin, 'add_term_save_meta' );
		$this->loader->add_action( 'update_option_joinchat', $plugin_admin, 'clear_cache', 100 );
		// Plugins page.
		$this->loader->add_filter( 'plugin_action_links_' . JOINCHAT_BASENAME, $plugin_admin, 'settings_link' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_links', 10, 2 );

		$plugin_page = new Joinchat_Admin_Page();

		$this->loader->add_action( 'admin_menu', $plugin_page, 'add_menu' );
		$this->loader->add_action( 'admin_init', $plugin_page, 'setting_fields' );
		$this->loader->add_action( 'load-settings_page_joinchat', $plugin_page, 'page_hooks' ); // Settings submenu.
		$this->loader->add_action( 'load-toplevel_page_joinchat', $plugin_page, 'page_hooks' ); // Joinchat menu.

		$plugin_onboard = new Joinchat_Admin_Onboard();

		$this->loader->add_action( 'admin_menu', $plugin_onboard, 'add_menu' );
		$this->loader->add_action( 'admin_head', $plugin_onboard, 'remove_menu' );
		$this->loader->add_action( 'load-settings_page_joinchat-onboard', $plugin_onboard, 'page_hooks' ); // Settings submenu.
		$this->loader->add_action( 'load-joinchat_page_joinchat-onboard', $plugin_onboard, 'page_hooks' ); // Joinchat submenu.
		$this->loader->add_action( 'wp_ajax_joinchat_onboard', $plugin_onboard, 'save' );

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

		$plugin_public = new Joinchat_Public();

		$this->loader->add_filter( 'joinchat_settings', $plugin_public, 'get_settings' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'enqueue_qr_script', 5 );

		// Actions (only) for preview.
		$this->loader->add_action( 'joinchat_preview_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'joinchat_preview_footer', $plugin_public, 'enqueue_qr_script', 5 );

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

		if ( ! isset( $_GET['joinchat-preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( Joinchat_Util::capability() ) ) {
			return;
		}

		require_once JOINCHAT_DIR . 'public/class-joinchat-preview.php';

		$plugin_preview = new Joinchat_Preview();

		$this->loader->add_filter( 'template_include', $plugin_preview, 'blank_template', PHP_INT_MAX );
		$this->loader->add_filter( 'get_post_metadata', $plugin_preview, 'disable_postmeta', 1000, 3 );
		$this->loader->add_filter( 'show_admin_bar', $plugin_preview, 'hide_admin_bar', 1000 );
		$this->loader->add_filter( 'joinchat_show', $plugin_preview, 'always_show', 1000 );
		$this->loader->add_filter( 'joinchat_classes', $plugin_preview, 'preview_classes', 10, 2 );
		$this->loader->add_filter( 'joinchat_content', $plugin_preview, 'preview_content' );
		$this->loader->add_filter( 'joinchat_template', $plugin_preview, 'preview_template' );
		$this->loader->add_filter( 'joinchat_inline_style', $plugin_preview, 'inline_style' );

		$this->loader->add_action( 'wp_print_scripts', $plugin_preview, 'remove_all_scripts', 1000 );
		$this->loader->add_action( 'wp_print_styles', $plugin_preview, 'remove_all_styles', 1000 );
		$this->loader->add_action( 'joinchat_preview_header', $plugin_preview, 'preview_header' );

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

		$plugin_premium = new Joinchat_Premium();

		$this->loader->add_filter( 'plugin_action_links_' . JOINCHAT_BASENAME, $plugin_premium, 'action_link' );
		$this->loader->add_filter( 'joinchat_admin_tabs', $plugin_premium, 'admin_tab', 1000 );
		$this->loader->add_filter( 'joinchat_tab_premium_sections', $plugin_premium, 'tab_sections' );
		$this->loader->add_filter( 'joinchat_section_output', $plugin_premium, 'section_ouput', 10, 2 );

		$this->loader->add_action( 'joinchat_admin_header', $plugin_premium, 'header_coupon' );

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
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Joinchat_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
