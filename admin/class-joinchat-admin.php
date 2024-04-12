<?php
/**
 * The admin common functionality of the plugin.
 *
 * @package    Joinchat
 */

/**
 * The admin common functionality of the plugin.
 *
 * @since      1.0.0
 * @since      2.0.0      Added visibility settings
 * @since      3.0.0      More extendable admin via hooks
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Admin {

	const KSES_LINK = array(
		'a' => array(
			'href'   => true,
			'target' => array( 'values' => array( '_blank' ) ),
		),
	);

	/**
	 * Initialize the settings for WordPress admin
	 *
	 * @since    5.0.0 (before on JoinchatAdminPage->settings_init())
	 * @access   public
	 * @return   void
	 */
	public function register_setting() {

		// Register WordPress 'joinchat' setting.
		$args = array(
			'default'           => jc_common()->defaults(),
			'sanitize_callback' => array( $this, 'setting_validate' ),
		);
		register_setting( JOINCHAT_SLUG, JOINCHAT_SLUG, $args );

	}


	/**
	 * Validate settings, clean and set defaults before save
	 *
	 * @since    5.0.0 (before on JoinchatAdminPage->settings_validate())
	 * @param    array $value  contain keys 'id', 'title' and 'callback'.
	 * @return   array
	 */
	public function setting_validate( $value ) {

		// Prevent bad behavior when validate twice on first save
		// bug (view https://core.trac.wordpress.org/ticket/21989).
		if ( count( get_settings_errors( JOINCHAT_SLUG ) ) ) {
			return $value;
		}

		$util = new Joinchat_Util(); // Shortcut.

		$util::maybe_encode_emoji();

		if ( is_array( $value['color'] ) ) {
			$bg   = preg_match( '/^#[a-f0-9]{6}$/i', $value['color']['bg'] ) ? $value['color']['bg'] : '#25d366';
			$text = '0' === $value['color']['text'] ? '0' : '100'; // '0' => black, '100' => white.
		} elseif ( preg_match( '/^(?<bg>#[a-f0-9]{6})(?:\/(?<text>0|100))?$/i', $value['color'], $color ) ) {
			$bg   = $color['bg'];
			$text = isset( $color['text'] ) ? $color['text'] : '100';
		} else {
			$bg   = '#25d366';
			$text = '100';
		}

		$optin_tags = array(
			'em'     => array(),
			'strong' => array(),
			'a'      => array( 'href' => true ),
		);

		$value['telephone']     = $util::clean_input( $value['telephone'] );
		$value['mobile_only']   = $util::yes_no( $value, 'mobile_only' );
		$value['button_image']  = intval( $value['button_image'] );
		$value['button_tip']    = $util::substr( $util::clean_input( $value['button_tip'] ), 0, 40 );
		$value['button_delay']  = intval( $value['button_delay'] );
		$value['whatsapp_web']  = $util::yes_no( $value, 'whatsapp_web' );
		$value['qr']            = $util::yes_no( $value, 'qr' );
		$value['message_text']  = $util::clean_input( $value['message_text'] );
		$value['message_badge'] = $util::yes_no( $value, 'message_badge' );
		$value['message_send']  = $util::clean_input( $value['message_send'] );
		$value['message_start'] = $util::substr( $util::clean_input( $value['message_start'] ), 0, 40 );
		$value['message_delay'] = intval( $value['message_delay'] ) * ( $util::yes_no( $value, 'message_delay_on' ) === 'yes' ? 1 : -1 );
		$value['message_views'] = intval( $value['message_views'] ) ? intval( $value['message_views'] ) : 1;
		$value['position']      = 'left' !== $value['position'] ? 'right' : 'left';
		$value['color']         = "$bg/$text";
		$value['dark_mode']     = in_array( $value['dark_mode'], array( 'no', 'yes', 'auto' ), true ) ? $value['dark_mode'] : 'no';
		$value['header']        = in_array( $value['header'], array( '__jc__', '__wa__' ), true ) ? $value['header'] : $util::substr( $util::clean_input( $value['header_custom'] ), 0, 40 );
		$value['optin_check']   = $util::yes_no( $value, 'optin_check' );
		$value['optin_text']    = wp_kses( $value['optin_text'], $optin_tags );
		$value['gads']          = is_array( $value['gads'] ) ? sprintf( 'AW-%s/%s', $util::substr( $util::clean_input( $value['gads'][0] ), 0, 11 ), $util::substr( $util::clean_input( $value['gads'][1] ), 0, 20 ) ) : '';
		$value['gads']          = 'AW-/' !== $value['gads'] ? $value['gads'] : '';
		$value['custom_css']    = trim( $util::clean_nl( $value['custom_css'] ) );
		$value['clear']         = $util::yes_no( $value, 'clear' );

		if ( isset( $value['view'] ) ) {
			$value['visibility'] = array_filter(
				$value['view'],
				function( $v ) {
					return 'yes' === $v || 'no' === $v;
				}
			);
		}

		// Clean input items that are not in settings.
		$value = array_intersect_key( $value, jc_common()->settings );

		// Filter for other validations or extra settings.
		$value = apply_filters( 'joinchat_settings_validate', $value, jc_common()->settings );

		add_settings_error( JOINCHAT_SLUG, 'settings_updated', esc_html__( 'Settings saved', 'creame-whatsapp-me' ), 'updated' );

		// Delete notice option.
		if ( $value['telephone'] ) {
			delete_option( 'joinchat_notice_dismiss' );
		}

		// Extra actions on save.
		do_action( 'joinchat_settings_validation', $value, jc_common()->settings );

		return $value;

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

		wp_register_style( 'joinchat-admin', plugins_url( "css/joinchat{$min}.css", __FILE__ ), array(), JOINCHAT_VERSION, 'all' );

		$intltel = jc_common()->get_intltel();
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
		$deps = array( 'jquery' );

		$intltel = jc_common()->get_intltel();
		if ( $intltel ) {
			$deps[] = 'intl-tel-input';
			$config = array(
				'placeholder' => esc_attr__( 'e.g.', 'creame-whatsapp-me' ),
				'version'     => $intltel,
				'utils_js'    => plugins_url( 'js/utils.js', __FILE__ ),
			);

			wp_register_script( 'intl-tel-input', plugins_url( "js/intlTelInput{$min}.js", __FILE__ ), array(), $intltel, true );
			wp_add_inline_script( 'intl-tel-input', 'var intlTelConf = ' . wp_json_encode( $config ) . ';', 'before' );
		}

		wp_register_script( 'joinchat-admin', plugins_url( "js/joinchat{$min}.js", __FILE__ ), $deps, JOINCHAT_VERSION, true );

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

		// If no phone number defined.
		if ( empty( jc_common()->settings['telephone'] )
			&& current_user_can( Joinchat_Util::capability() )
			&& ! Joinchat_Util::is_admin_screen()
			&& time() >= (int) get_option( 'joinchat_notice_dismiss' )
		) {

			printf(
				'<div class="notice notice-info is-dismissible" id="joinchat-empty-phone"><p><strong>Joinchat</strong>&nbsp;&nbsp;%s %s</p></div>',
				esc_html__( 'You only need to add your WhatsApp number to contact with your users.', 'creame-whatsapp-me' ),
				sprintf( '<a href="%s"><strong>%s</strong></a>', esc_url( Joinchat_Util::admin_url() ), esc_html__( 'Go to settings', 'creame-whatsapp-me' ) )
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

		$settings_link = sprintf( '<a href="%s">%s</a>', Joinchat_Util::admin_url(), esc_html__( 'Settings', 'creame-whatsapp-me' ) );

		array_unshift( $links, $settings_link );

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

		if ( JOINCHAT_BASENAME === $plugin_file ) {
			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( Joinchat_Util::link( 'docs', 'plugins' ) ), esc_html__( 'Documentation', 'creame-whatsapp-me' ) );
			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( Joinchat_Util::link( 'support', 'plugins' ) ), esc_html__( 'Support', 'creame-whatsapp-me' ) );
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

		$post_types  = jc_common()->get_public_post_types();
		$back_compat = apply_filters( 'joinchat_gutenberg_sidebar', Joinchat_Util::can_gutenberg() );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				JOINCHAT_SLUG,
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

		if ( jc_common()->get_intltel() ) {
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

		$placeholders = jc_common()->get_obj_placeholders( $post );
		$metabox_vars = jc_common()->get_obj_vars( $post );

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

		Joinchat_Util::maybe_encode_emoji();

		// Clean and delete empty/false fields.
		$metadata = array_filter(
			Joinchat_Util::clean_input(
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

		$taxonomies = jc_common()->get_taxonomies_meta_box();

		foreach ( $taxonomies as $taxonomy ) {
			add_action( "{$taxonomy}_edit_form_fields", array( $this, 'term_meta_box' ), 10, 2 );
		}
	}

	/**
	 * Add term save meta fields
	 *
	 * @since    5.0.9
	 * @access   public
	 * @return void
	 */
	public function add_term_save_meta() {

		$taxonomies = jc_common()->get_taxonomies_meta_box();

		foreach ( $taxonomies as $taxonomy ) {
			add_action( "edited_{$taxonomy}", array( $this, 'save_meta' ), 10, 2 );
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

		if ( jc_common()->get_intltel() ) {
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

		$placeholders = jc_common()->get_obj_placeholders( $term );
		$metabox_vars = jc_common()->get_obj_vars( $term );

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

	/**
	 * Adds the privacy message.
	 *
	 * @since    5.1.0
	 * @return void
	 */
	public function add_privacy_message() {

		if ( jc_common()->settings['message_delay'] < 0 ) {

			$message = '<p class="privacy-policy-tutorial">' . esc_html__( "With the current Joinchat's settings, no user data is collected and no cookies are used.", 'creame-whatsapp-me' ) . '</p>';

		} else {
			$message = '' .
				'<h2>' . esc_html__( 'Cookies' ) . '</h2>' .
				'<p class="privacy-policy-tutorial">' . esc_html__( 'Joinchat uses cookies to control when the chat window should be automatically displayed.', 'creame-whatsapp-me' ) . '</p>' .
				'<p><strong class="privacy-policy-tutorial">' . esc_html__( 'Suggested text:' ) . '</strong> ' .
					esc_html__( 'Cookies can be used to control when the WhatsApp floating button chat window should be automatically displayed.', 'creame-whatsapp-me' ) . ' ' .
					/* translators: %s: cookies names. */
					sprintf( esc_html__( 'These cookies (%s) do not contain personal data, are of type HTML LocalStorage and do not expire.', 'creame-whatsapp-me' ), '"joinchat_views", "joinchat_hashes"' ) .
				'</p>';
		}

		wp_add_privacy_policy_content( 'Joinchat', apply_filters( 'joinchat_privacy_message', $message ) );

	}
}
