<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @since      2.0.0      Added visibility settings
 * @since      3.0.0      More extendable admin via hooks
 * @package    JoinChat
 * @subpackage JoinChat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinChatAdmin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Common class for admin and front methods.
	 *
	 * @since    4.2.0
	 * @access   private
	 * @var      JoinChatCommon    $common    instance.
	 */
	private $common;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @since    3.0.0     Added $tabs initilization and removed get_settings()
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->common      = JoinChatCommon::instance();

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 * @since    4.1.15     Added color picker dependency.
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function register_styles( $hook ) {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'joinchat-admin', plugins_url( "css/joinchat{$min}.css", __FILE__ ), array( 'wp-color-picker' ), $this->version, 'all' );

		$intltel = $this->common->get_intltel();
		if ( $intltel ) {
			wp_register_style( 'intl-tel-input', plugins_url( "css/intlTelInput{$min}.css", __FILE__ ), array(), $intltel, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 * @since    4.1.4     Added intlTelInput localize.
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function register_scripts( $hook ) {

		$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$deps = array( 'jquery', 'wp-color-picker' );

		$intltel = $this->common->get_intltel();
		if ( $intltel ) {
			$deps[]   = 'intl-tel-input';
			$localize = array(
				'placeholder' => __( 'e.g.', 'creame-whatsapp-me' ),
				'version'     => $intltel,
				'utils_js'    => plugins_url( 'js/utils.js', __FILE__ ),
			);

			wp_register_script( 'intl-tel-input', plugins_url( "js/intlTelInput{$min}.js", __FILE__ ), array(), $intltel, true );
			wp_localize_script( 'intl-tel-input', 'intlTelConf', $localize );
		}

		wp_register_script( 'joinchat-admin', plugins_url( "js/joinchat{$min}.js", __FILE__ ), $deps, $this->version, true );
		wp_localize_script( 'joinchat-admin', 'joinchat_admin', array( 'example' => __( 'is an example, double click to use it', 'creame-whatsapp-me' ) ) );

	}

	/**
	 * Show admin notices
	 *
	 * @since    4.2.0
	 * @access   public
	 * @return   void
	 */
	public function notices() {

		if ( defined( 'DISABLE_NAG_NOTICES' ) && DISABLE_NAG_NOTICES ) {
			return;
		}

		$current_screen = get_current_screen();

		// If no phone number defined.
		if ( empty( $this->common->settings['telephone'] )
			&& current_user_can( JoinChatUtil::capability() )
			&& ( $current_screen && false === strpos( $current_screen->id, '_joinchat' ) )
			&& time() >= (int) get_option( 'joinchat_notice_dismiss' )
		) {

			printf(
				'<div class="notice notice-info is-dismissible" id="joinchat-empty-phone"><p><strong>Joinchat</strong>&nbsp;&nbsp;%s %s</p></div>',
				esc_html__( 'You only need to add your WhatsApp number to contact with your users.', 'creame-whatsapp-me' ),
				sprintf( '<a href="%s"><strong>%s</strong></a>', esc_url( JoinChatUtil::admin_url() ), esc_html__( 'Go to settings', 'creame-whatsapp-me' ) )
			);

			printf(
				'<script>jQuery("#joinchat-empty-phone").on("click", ".notice-dismiss", function () {' .
				'jQuery.post(ajaxurl, { action: "joinchat_notice_dismiss", nonce: "%s"}, null, "json");' .
				'});</script>',
				esc_js( wp_create_nonce( 'joinchat_nonce' ) )
			);
		}

	}

	/**
	 * Notice Dismiss
	 *
	 * @since    4.3.1
	 * @access   public
	 * @return   void
	 */
	public function ajax_notice_dismiss() {

		check_ajax_referer( 'joinchat_nonce', 'nonce', true );
		update_option( 'joinchat_notice_dismiss', time() + MONTH_IN_SECONDS, true );
		wp_send_json_success();

	}

	/**
	 * Add link to options page on plugins page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @param    array $links       current plugin links.
	 * @return   array
	 */
	public function settings_link( $links ) {

		$settings_link = sprintf( '<a href="%s">%s</a>', JoinChatUtil::admin_url(), __( 'Settings', 'creame-whatsapp-me' ) );

		array_unshift( $links, $settings_link );

		$utm  = '?utm_source=action&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
		$lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

		$links['premium'] = sprintf(
			'<a href="%1$s" target="_blank" style="font-weight:bold;color:#f9603a;">%2$s</a>',
			esc_url( "https://join.chat/$lang/premium/$utm" ),
			esc_html__( 'Premium', 'creame-whatsapp-me' )
		);

		return $links;

	}

	/**
	 * Add plugin meta links
	 *
	 * @since    4.0.0
	 * @access   public
	 * @param    array  $plugin_meta       current plugin row meta.
	 * @param    string $plugin_file       plugin file.
	 * @return   array
	 */
	public function plugin_links( $plugin_meta, $plugin_file ) {

		if ( 'creame-whatsapp-me/joinchat.php' === $plugin_file ) {
			$utm  = '?utm_source=plugins&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
			$lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( "https://join.chat/$lang/docs/$utm" ), __( 'Documentation', 'creame-whatsapp-me' ) );
			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( "https://join.chat/$lang/support/$utm" ), __( 'Support', 'creame-whatsapp-me' ) );
		}

		return $plugin_meta;

	}

	/**
	 * Add Meta Box for all the public post types
	 *
	 * @since    1.1.0
	 * @since    4.5.0   Added back_compat to disable in block editor
	 * @access   public
	 * @return   void
	 */
	public function add_meta_boxes() {

		$post_types  = $this->common->get_public_post_types();
		$back_compat = apply_filters( 'joinchat_gutenberg_sidebar', JoinChatUtil::can_gutenberg() );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				$this->plugin_name,
				__( 'Joinchat', 'creame-whatsapp-me' ),
				array( $this, 'meta_box' ),
				$post_type,
				'side',
				'default',
				array( '__back_compat_meta_box' => $back_compat && post_type_supports( $post_type, 'custom-fields' ) )
			);
		}
	}

	/**
	 * Generate Meta Box html
	 *
	 * @since    1.1.0     (previously named "add_meta_box")
	 * @since    2.0.0     Now can set as [show, hide, default]
	 * @since    2.2.0     Enqueue scripts/styles. Added "telephone"
	 * @since    3.0.3     Capture and filter output
	 * @since    3.2.0     Added filter 'joinchat_metabox_placeholders'
	 * @access   public
	 * @param  WP_Post $post Current post object.
	 * @return void
	 */
	public function meta_box( $post ) {

		// Enqueue assets.
		wp_enqueue_script( 'joinchat-admin' );
		wp_enqueue_style( 'joinchat-admin' );

		if ( $this->common->get_intltel() ) {
			wp_enqueue_style( 'intl-tel-input' );
		}

		$metadata = get_post_meta( $post->ID, '_joinchat', true ) ?: array(); //phpcs:ignore WordPress.PHP.DisallowShortTernary
		$metadata = array_merge(
			array(
				'telephone'    => '',
				'message_text' => '',
				'message_send' => '',
				'view'         => '',
			),
			$metadata
		);

		$placeholders = $this->common->get_obj_placeholders( $post );
		$metabox_vars = $this->common->get_obj_vars( $post );

		ob_start();
		include __DIR__ . '/partials/post-meta-box.php';
		$metabox_output = ob_get_clean();

		echo apply_filters( 'joinchat_metabox_output', $metabox_output, $post, $metadata ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save meta data from "Joinchat"
	 *
	 * @since    4.3.0
	 * @access   public
	 * @param  int         $id post|term ID.
	 * @param  WP_Post|int $arg current post or term taxonomi id.
	 * @return void
	 */
	public function save_meta( $id, $arg ) {

		if ( ! isset( $_POST['joinchat_nonce'] ) || ! wp_verify_nonce( $_POST['joinchat_nonce'], 'joinchat_data' ) ) {
			return;
		}

		$type = $arg instanceof WP_Post ? 'post' : 'term';

		if ( 'post' === $type && wp_is_post_autosave( $id ) ) {
			return;
		}

		JoinChatUtil::maybe_encode_emoji();

		// Clean and delete empty/false fields.
		$metadata = array_filter(
			JoinChatUtil::clean_input(
				array(
					'telephone'    => $_POST['joinchat_telephone'],
					'message_text' => $_POST['joinchat_message'],
					'message_send' => $_POST['joinchat_message_send'],
					'view'         => $_POST['joinchat_view'],
				)
			)
		);

		$metadata = apply_filters( 'joinchat_metabox_save', $metadata, $id, $type );

		if ( count( $metadata ) ) {
			update_metadata( $type, $id, '_joinchat', $metadata );
		} else {
			delete_metadata( $type, $id, '_joinchat' );
		}
	}

	/**
	 * Add term edit form meta fields
	 *
	 * @since    4.3.0
	 * @access   public
	 * @return void
	 */
	public function add_term_meta_boxes() {

		$taxonomies = apply_filters( 'joinchat_taxonomies_meta_box', array( 'category', 'post_tag' ) );

		foreach ( $taxonomies as $tax ) {
			add_action( "{$tax}_edit_form_fields", array( $this, 'term_meta_box' ), 10, 2 );
			add_action( "edited_{$tax}", array( $this, 'save_meta' ), 10, 2 );
		}

	}

	/**
	 * Generate term edit form fields html
	 *
	 * @since    4.3.0
	 * @access   public
	 * @param  WP_Term $term Current taxonomy term object.
	 * @param  string  $taxonomy Current taxonomy slug.
	 * @return void
	 */
	public function term_meta_box( $term, $taxonomy ) {

		// Enqueue assets.
		wp_enqueue_script( 'joinchat-admin' );
		wp_enqueue_style( 'joinchat-admin' );

		if ( $this->common->get_intltel() ) {
			wp_enqueue_style( 'intl-tel-input' );
		}

		$metadata = get_term_meta( $term->term_id, '_joinchat', true ) ?: array(); //phpcs:ignore WordPress.PHP.DisallowShortTernary
		$metadata = array_merge(
			array(
				'telephone'    => '',
				'message_text' => '',
				'message_send' => '',
				'view'         => '',
			),
			$metadata
		);

		$placeholders = $this->common->get_obj_placeholders( $term );
		$metabox_vars = $this->common->get_obj_vars( $term );

		ob_start();
		include __DIR__ . '/partials/term-meta-box.php';
		$metabox_output = ob_get_clean();

		echo apply_filters( 'joinchat_term_metabox_output', $metabox_output, $term, $metadata, $taxonomy ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Clear third party cache plugins if joinchat option changed
	 *
	 * @since    4.0.5
	 * @access   public
	 * @return   void
	 */
	public static function clear_cache() {

		// TODO: Prevent Autoptimize clear many times.

		/**
		 * List of callable functions or actions by third party plugins.
		 * format: string callable or array( string callable or hook, [, mixed $parameter [, mixed $... ]] )
		 */
		$cache_plugins = apply_filters(
			'joinchat_cache_plugins',
			array(
				'autoptimizeCache::clearall_actionless', // Autoptimize https://wordpress.org/plugins/autoptimize/.
				'cache_enabler_clear_complete_cache',    // Cache Enabler https://wordpress.org/plugins/cache-enabler/.
				'cachify_flush_cache',                   // Cachify https://wordpress.org/plugins/cachify/.
				'LiteSpeed_Cache_API::purge_all',        // LiteSpeed Cache https://wordpress.org/plugins/litespeed-cache/.
				'sg_cachepress_purge_cache',             // SG Optimizer https://es.wordpress.org/plugins/sg-cachepress/.
				array( 'wpfc_clear_all_cache', true ),   // WP Fastest Cache https://es.wordpress.org/plugins/wp-fastest-cache/.
				'rocket_clean_minify',                   // WP Rocket https://wp-rocket.me.
				'rocket_clean_domain',
				'wp_cache_clear_cache',                  // WP Super Cache https://wordpress.org/plugins/wp-super-cache/.
				'w3tc_flush_all',                        // W3 Total Cache https://wordpress.org/plugins/w3-total-cache/.
			)
		);

		foreach ( $cache_plugins as $callable ) {
			$callable = (array) $callable;

			if ( is_callable( $callable[0] ) ) {
				call_user_func_array( array_shift( $callable ), $callable );
			} elseif ( has_action( $callable[0] ) ) {
				call_user_func_array( 'do_action', $callable );
			}
		}

	}
}
