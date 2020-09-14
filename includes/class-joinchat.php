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
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChat {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      JoinChatLoader    $loader    Maintains and registers all hooks for the plugin.
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
		$this->version     = defined( 'JOINCHAT_VERSION' ) ? JOINCHAT_VERSION : '1.0.0';
		$this->plugin_name = 'joinchat';

		$this->load_dependencies();
		$this->set_locale();
		$this->load_integrations();
		$this->update_wame();
		is_admin() ? $this->define_admin_hooks() : $this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - JoinChatLoader. Orchestrates the hooks of the plugin.
	 * - JoinChat_i18n. Defines internationalization functionality.
	 * - JoinChatAdmin. Defines all hooks for the admin area.
	 * - JoinChatPublic. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_dependencies() {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-joinchat-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-joinchat-i18n.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-joinchat-integrations.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-joinchat-util.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-joinchat-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-joinchat-public.php';

		$this->loader = new JoinChatLoader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the JoinChat_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @return   void
	 */
	private function set_locale() {

		$plugin_i18n = new JoinChat_i18n();

		// No delegate to $this->loader, use WordPress add_action
		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Load third party plugins integrations
	 *
	 * @since    3.0.0
	 * @access   private
	 * @return   void
	 */
	private function load_integrations() {

		$plugin_integrations = new JoinChatIntegrations();

		// No delegate to $this->loader, use WordPress add_action.
		// At 'plugins_loaded' hook can determine if other plugins are present.
		add_action( 'plugins_loaded', array( $plugin_integrations, 'load_integrations' ) );

	}

	/**
	 * Run checks.
	 *
	 * Check if exists 'whatsappme' settings of previous versions (<4.0)
	 *
	 * @since    4.0.0
	 * @access   private
	 * @return   boolean    true if pass checks, false otherwise
	 */
	private function checks() {

		$whatsappme = false !== get_option( 'whatsappme' );

		if ( $whatsappme ) {
			add_action( 'admin_notices', array( $this, 'need_reactivate_notice' ) );
		}

		return ! $whatsappme;

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

		$plugin_admin = new JoinChatAdmin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_init', $plugin_admin, 'get_settings', 5 );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'settings_init' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'register_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_menu' );
		$this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_meta_boxes' );
		$this->loader->add_action( 'save_post', $plugin_admin, 'save_post' );
		$this->loader->add_action( 'load-settings_page_joinchat', $plugin_admin, 'help_tab' );
		$this->loader->add_action( 'update_option_joinchat', $plugin_admin, 'clear_cache', 100 );

		$this->loader->add_filter( "plugin_action_links_creame-whatsapp-me/{$this->plugin_name}.php", $plugin_admin, 'settings_link' );
		$this->loader->add_filter( 'plugin_row_meta', $plugin_admin, 'plugin_links', 10, 2 );

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

		$plugin_public = new JoinChatPublic( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp', $plugin_public, 'get_settings' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'wp_footer', $plugin_public, 'footer_html' );
		$this->loader->add_action( 'elementor/preview/init', $plugin_public, 'elementor_preview_disable' );

	}

	/**
	 * Migrate 'whatsappme' settings on versions < 4.0 to new 'joinchat'
	 *
	 * @since    4.0.0
	 * @access   private
	 * @return   void
	 */
	public function update_wame() {
		global $wpdb;

		$general_option = get_option( 'whatsappme' );

		if ( false !== $general_option ) {
			// General option
			update_option( 'joinchat', $general_option );
			delete_option( 'whatsappme' );

			// Post metas
			$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_joinchat' ), array( 'meta_key' => '_whatsappme' ) );

			// WPML strings
			$wpml_strings_table = $wpdb->prefix . 'icl_strings';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpml_strings_table'" ) === $wpml_strings_table ) {
				$wpdb->update( $wpml_strings_table, array( 'context' => 'Join.chat' ), array( 'context' => 'WhatsApp me' ) );
			}

			// Polylang strings
			$polylang_strings = get_option( 'polylang_wpml_strings' );
			if ( false !== $polylang_strings ) {
				foreach ( $polylang_strings as $key => $data ) {
					if ( 'WhatsApp me' == $data['context'] ) {
						$polylang_strings[ $key ]['context'] = 'Join.chat';
					}
				}
				update_option( 'polylang_wpml_strings', $polylang_strings );
			}
		}

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
	 * @return    JoinChatLoader    Orchestrates the hooks of the plugin.
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
