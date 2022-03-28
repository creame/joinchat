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
	 * The setings of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    The current settings of this plugin.
	 */
	private $settings;

	/**
	 * Use International Telephone Input library (https://intl-tel-input.com/)
	 *
	 * @since    1.2.0
	 * @access   private
	 * @var      boolean    $enhanced_phone    Use enhanced phone input.
	 */
	private $enhanced_phone;

	/**
	 * Admin page tabs
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array    $tabs    Admin page tabs.
	 */
	private $tabs;

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
		$this->common      = new JoinChatCommon();

		// Updated in get_settings() at 'admin_init' hook
		$this->enhanced_phone = '17.0.15'; // intl-tel-input version
		$this->tabs           = array();
		$this->settings       = array();

	}

	/**
	 * Get all settings or set defaults
	 *
	 * @since    1.0.0
	 * @since    2.0.0     Added visibility setting
	 * @since    2.1.0     Added message_badge
	 * @since    2.3.0     Added button_delay and whatsapp_web settings, message_delay in seconds
	 * @since    3.0.0     Is public and added plugin enhanced_phone and tabs
	 * @since    3.1.0     Added tooltip and image
	 * @since    4.0.0     Added message_views and color
	 * @since    4.1.0     Added header
	 */
	public function get_settings() {

		// Use International Telephone Input library version or false to disable
		$this->enhanced_phone = apply_filters( 'joinchat_enhanced_phone', $this->enhanced_phone );

		// Admin tabs
		$this->tabs = apply_filters(
			'joinchat_admin_tabs',
			array(
				'general'    => __( 'General', 'creame-whatsapp-me' ),
				'visibility' => __( 'Visibility', 'creame-whatsapp-me' ),
				'advanced'   => __( 'Advanced', 'creame-whatsapp-me' ),
			)
		);

		// Load settings
		$this->settings = $this->common->load_settings();

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

		if ( $this->enhanced_phone ) {
			wp_register_style( 'intl-tel-input', plugins_url( "css/intlTelInput{$min}.css", __FILE__ ), array(), $this->enhanced_phone, 'all' );
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

		if ( $this->enhanced_phone ) {
			$deps[]   = 'intl-tel-input';
			$localize = array(
				'placeholder' => __( 'e.g.', 'creame-whatsapp-me' ),
				'version'     => $this->enhanced_phone,
				'utils_js'    => plugins_url( 'js/utils.js', __FILE__ ),
			);

			wp_register_script( 'intl-tel-input', plugins_url( "js/intlTelInput{$min}.js", __FILE__ ), array(), $this->enhanced_phone, true );
			wp_localize_script( 'intl-tel-input', 'intlTelConf', $localize );
		}

		wp_register_script( 'joinchat-admin', plugins_url( "js/joinchat{$min}.js", __FILE__ ), $deps, $this->version, true );
		wp_localize_script( 'joinchat-admin', 'joinchat_admin', array( 'example' => __( 'is an example, double click to use it', 'creame-whatsapp-me' ) ) );

	}

	/**
	 * Initialize the settings for WordPress admin
	 * From v1.2.0 also set filter to disable enhanced phone input
	 *
	 * @since    1.0.0
	 * @since    2.0.0     Added tabs for general and Visibility settings
	 * @since    2.3.0     Split general settings in Button and Window Chat
	 * @since    3.0.0     Admin organized by tabs and sections
	 * @access   public
	 * @return   void
	 */
	public function settings_init() {

		// Register WordPress 'joinchat' settings
		register_setting( $this->plugin_name, $this->plugin_name, array( $this, 'settings_validate' ) );

		foreach ( $this->tabs as $tab => $tab_name ) {

			add_settings_section( "joinchat_tab_{$tab}_open", null, array( $this, 'settings_tab_open' ), $this->plugin_name );

			$sections = $this->get_tab_sections( $tab );

			foreach ( $sections as $section => $fields ) {
				$section_id = "joinchat_tab_{$tab}__{$section}";

				add_settings_section( $section_id, null, array( $this, 'section_output' ), $this->plugin_name );

				foreach ( $fields as $field => $field_args ) {
					if ( is_array( $field_args ) ) {
						$field_name     = $field_args['label'];
						$field_callback = $field_args['callback'];
					} else {
						$field_name     = $field_args;
						$field_callback = array( $this, 'field_output' );
					}

					add_settings_field( "joinchat_$field", $field_name, $field_callback, $this->plugin_name, $section_id, $field );
				}
			}

			add_settings_section( "joinchat_tab_{$tab}_close", null, array( $this, 'settings_tab_close' ), $this->plugin_name );
		}

	}

	/**
	 * Return an array of sections and fields for the admin tab
	 *
	 * @since    3.0.0
	 * @since    3.1.0     Added tooltip and image
	 * @param    string $tab       The id of the admin tab.
	 * @return   array
	 */
	private function get_tab_sections( $tab ) {

		if ( 'general' == $tab ) {

			$sections = array(
				'button'    => array(
					'telephone'    => '<label for="joinchat_phone">' . __( 'Telephone', 'creame-whatsapp-me' ) . '</label>',
					'message_send' => '<label for="joinchat_message_send">' . __( 'Message', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_send' ),
					'button_image' => __( 'Image', 'creame-whatsapp-me' ),
					'button_tip'   => '<label for="joinchat_button_tip">' . __( 'Tooltip', 'creame-whatsapp-me' ) . '</label>',
					'position'     => __( 'Position on Screen', 'creame-whatsapp-me' ),
					'button_delay' => '<label for="joinchat_button_delay">' . __( 'Button Delay', 'creame-whatsapp-me' ) . '</label>',
					'mobile_only'  => __( 'Mobile Only', 'creame-whatsapp-me' ),
					'whatsapp_web' => __( 'WhatsApp Web', 'creame-whatsapp-me' ),
					'qr'           => __( 'QR Code', 'creame-whatsapp-me' ),
				),
				'chat'      => array(
					'message_text'  => '<label for="joinchat_message_text">' . __( 'Call to Action', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_text' ),
					'message_start' => '<label for="joinchat_message_start">' . __( 'Button Text', 'creame-whatsapp-me' ) . '</label>',
					'color'         => __( 'Theme Color', 'creame-whatsapp-me' ),
					'dark_mode'     => __( 'Dark Mode', 'creame-whatsapp-me' ),
					'header'        => __( 'Header', 'creame-whatsapp-me' ),
				),
				'chat_open' => array(
					'message_delay' => '<label for="joinchat_message_delay">' . __( 'Chat Delay', 'creame-whatsapp-me' ) . '</label>',
					'message_views' => '<label for="joinchat_message_views">' . __( 'Page Views', 'creame-whatsapp-me' ) . '</label>',
					'message_badge' => __( 'Notification Balloon', 'creame-whatsapp-me' ),
				),
			);

		} elseif ( 'visibility' == $tab ) {

			$sections = array(
				'global' => array(
					'view__all' => array(
						'label'    => __( 'Global', 'creame-whatsapp-me' ),
						'callback' => array( $this, 'field_view_all' ),
					),
				),
				'wp'     => array(
					'view__front_page' => __( 'Front Page', 'creame-whatsapp-me' ),
					'view__blog_page'  => __( 'Blog Page', 'creame-whatsapp-me' ),
					'view__404_page'   => __( '404 Page', 'creame-whatsapp-me' ),
					'view__search'     => __( 'Search Results', 'creame-whatsapp-me' ),
					'view__archive'    => __( 'Archives', 'creame-whatsapp-me' ),
					'view__date'       => '— ' . __( 'Date Archives', 'creame-whatsapp-me' ),
					'view__author'     => '— ' . __( 'Author Archives', 'creame-whatsapp-me' ),
					'view__singular'   => __( 'Singular', 'creame-whatsapp-me' ),
					'view__page'       => '— ' . __( 'Page', 'creame-whatsapp-me' ),
					'view__post'       => '— ' . __( 'Post', 'creame-whatsapp-me' ),
				),
			);

			// If isn't set Blog Page or is the same than Front Page unset blog_page option
			if ( get_option( 'show_on_front' ) == 'posts' || get_option( 'page_for_posts' ) == 0 ) {
				unset( $sections['wp']['view__blog_page'] );
			}

			// Custom Post Types
			$custom_post_types = apply_filters(
				'joinchat_custom_post_types',
				array_keys( get_post_types( array( 'has_archive' => true ), 'names' ) )
			);

			if ( count( $custom_post_types ) ) {
				$sections['cpt'] = array();

				foreach ( $custom_post_types as $custom_post_type ) {
					$post_type      = get_post_type_object( $custom_post_type );
					$post_type_name = function_exists( 'mb_convert_case' ) ?
						mb_convert_case( $post_type->labels->name, MB_CASE_TITLE ) :
						strtolower( $post_type->labels->name );

					$sections['cpt'][ "view__cpt_$custom_post_type" ] = $post_type_name;
				}
			}
		} elseif ( 'advanced' == $tab ) {

			$sections = array(
				'optin'      => array(
					'optin_text'  => __( 'Opt-in Text', 'creame-whatsapp-me' ),
					'optin_check' => __( 'Opt-in Required', 'creame-whatsapp-me' ),
				),
				'conversion' => array(
					'gads' => '<label for="joinchat_gads">' . __( 'Google Ads Conversion', 'creame-whatsapp-me' ) . '</label>',
				),
			);

		} else {

			$sections = array();

		}

		// Filter tab sections to add, remove or edit sections or fields
		return apply_filters( "joinchat_tab_{$tab}_sections", $sections );

	}

	/**
	 * Validate settings, clean and set defaults before save
	 *
	 * @since    1.0.0
	 * @since    2.0.0    Added visibility setting
	 * @since    2.1.0    Added message_badge
	 * @since    2.3.0    Added button_delay and whatsapp_web settings, WPML integration
	 * @since    3.0.0    Added filter for extra settings and action for extra tasks
	 * @since    3.1.0    Added tooltip and image
	 * @param    array $input       contain keys 'id', 'title' and 'callback'.
	 * @return   array
	 */
	public function settings_validate( $input ) {

		// Prevent bad behavior when validate twice on first save
		// bug https://core.trac.wordpress.org/ticket/21989
		if ( count( get_settings_errors( $this->plugin_name ) ) ) {
			return $input;
		}

		$util = new JoinChatUtil(); // Shortcut

		$util::maybe_encode_emoji();

		$input['telephone']     = $util::clean_input( $input['telephone'] );
		$input['mobile_only']   = isset( $input['mobile_only'] ) ? 'yes' : 'no';
		$input['button_image']  = intval( $input['button_image'] );
		$input['button_tip']    = $util::substr( $util::clean_input( $input['button_tip'] ), 0, 40 );
		$input['button_delay']  = intval( $input['button_delay'] );
		$input['whatsapp_web']  = isset( $input['whatsapp_web'] ) ? 'yes' : 'no';
		$input['qr']            = isset( $input['qr'] ) ? 'yes' : 'no';
		$input['message_text']  = $util::clean_input( $input['message_text'] );
		$input['message_badge'] = isset( $input['message_badge'] ) ? 'yes' : 'no';
		$input['message_send']  = $util::clean_input( $input['message_send'] );
		$input['message_start'] = $util::substr( $util::clean_input( $input['message_start'] ), 0, 20 );
		$input['message_delay'] = intval( $input['message_delay'] );
		$input['message_views'] = intval( $input['message_views'] ) ?: 1;
		$input['position']      = $input['position'] != 'left' ? 'right' : 'left';
		$input['color']         = preg_match( '/^#[a-f0-9]{6}$/i', $input['color'] ) ? $input['color'] : '#25d366';
		$input['dark_mode']     = in_array( $input['dark_mode'], array( 'no', 'yes', 'auto' ) ) ? $input['dark_mode'] : 'no';
		$input['header']        = in_array( $input['header'], array( '__jc__', '__wa__' ) ) ? $input['header'] : $util::substr( $util::clean_input( $input['header_custom'] ), 0, 40 );
		$input['optin_check']   = isset( $input['optin_check'] ) ? 'yes' : 'no';
		$input['optin_text']    = wp_kses(
			$input['optin_text'],
			array(
				'em'     => true,
				'strong' => true,
				'a'      => array( 'href' => true ),
			)
		);
		$input['gads']          = $util::substr( $util::clean_input( $input['gads'] ), 0, 40 );

		if ( isset( $input['view'] ) ) {
			$input['visibility'] = array_filter(
				$input['view'],
				function( $v ) {
					return 'yes' == $v || 'no' == $v;
				}
			);
		}

		// Clean input items that are not in settings
		$input = array_intersect_key( $input, $this->settings );

		// Filter for other validations or extra settings
		$input = apply_filters( 'joinchat_settings_validate', $input, $this->settings );

		add_settings_error( $this->plugin_name, 'settings_updated', __( 'Settings saved', 'creame-whatsapp-me' ), 'updated' );

		// Delete notice option
		if ( $input['telephone'] ) {
			delete_option( 'joinchat_notice_dismiss' );
		}

		// Extra actions on save
		do_action( 'joinchat_settings_validation', $input, $this->settings );

		return $input;
	}

	/**
	 * Tab open HTML output
	 *
	 * @since    3.0.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function settings_tab_open( $args ) {

		$tab_id     = str_replace( array( 'joinchat_tab_', '_open' ), '', $args['id'] );
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ) ) ? $_GET['tab'] : 'general';
		$active     = $active_tab == $tab_id ? 'joinchat-tab-active' : '';

		echo "<div id=\"joinchat_tab_$tab_id\" class=\"joinchat-tab $active\" role=\"tabpanel\" aria-labelledby=\"navtab_$tab_id\" >";

	}

	/**
	 * Tab close HTML output
	 *
	 * @since    3.0.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function settings_tab_close( $args ) {

		echo '</div>';

	}

	/**
	 * Section HTML output
	 *
	 * @since    3.0.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function section_output( $args ) {
		$section_id = $args['id'];

		switch ( $section_id ) {
			case 'joinchat_tab_general__button':
				$output = '<h2 class="title">' . __( 'Button', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'Set the contact number and where you want the WhatsApp button to be displayed.', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_general__chat':
				$output = '<hr><h2 class="title">' . __( 'Chat Window', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						__( 'If you define a "Call to Action" a window will be displayed simulating a chat before launching WhatsApp.', 'creame-whatsapp-me' ) . ' ' .
						__( 'You can introduce yourself, offer help or even make promotions to your users.', 'creame-whatsapp-me' ) .
					'</p>';
				break;

			case 'joinchat_tab_general__chat_open':
				$output = '<p>' .
						__( 'If it\'s defined a "Call to Action", the Chat Window can be displayed automatically if conditions are met.', 'creame-whatsapp-me' ) .
						' <a class="joinchat-show-help" href="#tab-link-triggers" title="' . __( 'Show Help', 'creame-whatsapp-me' ) . '">?</a>' .
					'</p>';
				break;

			case 'joinchat_tab_visibility__global':
				$output = '<h2 class="title">' . __( 'Visibility Settings', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'From here you can configure on which pages the WhatsApp button will be visible.', 'creame-whatsapp-me' ) .
					' <a href="#" class="joinchat_view_reset">' . __( 'Restore default visibility', 'creame-whatsapp-me' ) . '</a></p>';
				break;

			case 'joinchat_tab_visibility__wp':
				$output = '<hr>';
				break;

			case 'joinchat_tab_visibility__cpt':
				$output = '<h2 class="title">' . __( 'Custom Post Types', 'creame-whatsapp-me' ) . '</h2>';
				break;

			case 'joinchat_tab_advanced__optin':
				$output = '<h2 class="title">' . __( 'Opt-in', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'Opt-in is a users’ consent to receive messages from a business.', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_advanced__conversion':
				$output = '<hr><h2 class="title">' . __( 'Conversions', 'creame-whatsapp-me' ) . '</h2>';
				break;

			default:
				$output = '';
				break;
		}

		// Filter section opening ouput
		echo apply_filters( 'joinchat_section_output', $output, $section_id );
	}

	/**
	 * Field HTML output
	 *
	 * @since    3.0.0
	 * @since    3.1.0     Added tooltip and image
	 * @return   void
	 */
	public function field_output( $field_id ) {

		if ( strpos( $field_id, 'view__' ) === 0 ) {
			$field = substr( $field_id, 6 );
			$value = isset( $this->settings['visibility'][ $field ] ) ? $this->settings['visibility'][ $field ] : '';

			$output = '<label><input type="radio" name="joinchat[view][' . $field . ']" value="yes"' . checked( 'yes', $value, false ) . '> ' .
				'<span class="dashicons dashicons-visibility" title="' . __( 'Show', 'creame-whatsapp-me' ) . '"></span></label>' .
				'<label><input type="radio" name="joinchat[view][' . $field . ']" value="no"' . checked( 'no', $value, false ) . '> ' .
				'<span class="dashicons dashicons-hidden" title="' . __( 'Hide', 'creame-whatsapp-me' ) . '"></span></label>' .
				'<label><input type="radio" name="joinchat[view][' . $field . ']" value=""' . checked( '', $value, false ) . '> ' .
				__( 'Inherit', 'creame-whatsapp-me' ) . ' <span class="dashicons dashicons-visibility view_inheritance_' . $field . '"></span></label>';

		} else {

			$value = isset( $this->settings[ $field_id ] ) ? $this->settings[ $field_id ] : '';
			$utm   = '?utm_source=settings&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
			$lang  = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

			switch ( $field_id ) {
				case 'telephone':
					$output = '<input id="joinchat_phone" ' . ( $this->enhanced_phone ? 'data-' : '' ) . 'name="joinchat[telephone]" value="' . esc_attr( $value ) . '" type="text" style="width:15em">' .
						'<p class="description">' . __( "Contact phone number <strong>(the button will not be shown if it's empty)</strong>", 'creame-whatsapp-me' ) . '</p>' .
						'<p class="joinchat-addon">' . sprintf(
							__( 'Add unlimited numbers with %1$s or multiple contacts with %2$s', 'creame-whatsapp-me' ),
							'<a href="https://join.chat/' . $lang . '/addons/random-phone/' . $utm . '" target="_blank">\'Random Phone\'</a>',
							'<a href="https://join.chat/' . $lang . '/addons/support-agents/' . $utm . '" target="_blank">\'Support Agents\'</a>'
						) . '</p>';
					break;

				case 'mobile_only':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Mobile Only', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_mobile_only" name="joinchat[mobile_only]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Only display the button on mobile devices', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'position':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Position on Screen', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[position]" value="left" type="radio"' . checked( 'left', $value, false ) . '> ' .
						__( 'Left', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[position]" value="right" type="radio"' . checked( 'right', $value, false ) . '> ' .
						__( 'Right', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'button_image':
					$thumb = JoinChatUtil::thumb( $value, 116, 116 );
					$image = is_array( $thumb ) ? $thumb['url'] : false;

					$output = '<div id="joinchat_button_image_wrapper">' .
						'<div id="joinchat_button_image_holder" ' . ( $image ? "style=\"background-size:cover; background-image:url('$image');\"" : '' ) . '></div>' .
						'<input id="joinchat_button_image" name="joinchat[button_image]" type="hidden" value="' . intval( $value ) . '">' .
						'<input id="joinchat_button_image_add" type="button" value="' . esc_attr__( 'Select an image', 'creame-whatsapp-me' ) . '" class="button-primary" ' .
						'data-title="' . esc_attr__( 'Select button image', 'creame-whatsapp-me' ) . '" data-button="' . esc_attr__( 'Use image', 'creame-whatsapp-me' ) . '"> ' .
						'<input id="joinchat_button_image_remove" type="button" value="' . esc_attr__( 'Remove', 'creame-whatsapp-me' ) . '" class="button-secondary' . ( $image ? '' : ' joinchat-hidden' ) . '">' .
						'<p class="description">' . __( 'The image will alternate with button icon', 'creame-whatsapp-me' ) . '</p></div>' .
						'<p class="joinchat-addon">' . sprintf(
							__( 'Other icons and more channels (Telegram, Messenger…) with %s', 'creame-whatsapp-me' ),
							'<a href="https://join.chat/' . $lang . '/addons/omnichannel/' . $utm . '" target="_blank">\'Omnichannel\'</a>'
						) . '</p>';
					break;

				case 'button_tip':
					$output = '<input id="joinchat_button_tip" name="joinchat[button_tip]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( '💬 Need help?', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . __( 'Short text shown next to button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'button_delay':
					$output = '<input id="joinchat_button_delay" name="joinchat[button_delay]" value="' . intval( $value ) . '" type="number" min="-1" max="120" style="width:5em"> ' .
						__( 'seconds', 'creame-whatsapp-me' ) . ' (' . __( '-1 to display directly without animation', 'creame-whatsapp-me' ) . ')' .
						'<p class="description">' . __( 'Time since the page is opened until the button is displayed', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'whatsapp_web':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'WhatsApp Web', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_whatsapp_web" name="joinchat[whatsapp_web]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Open <em>WhatsApp Web</em> directly on desktop', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'qr':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'QR Code', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_qr" name="joinchat[qr]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Display QR code on desktop to scan with phone', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'message_text':
					$output = '<textarea id="joinchat_message_text" name="joinchat[message_text]" rows="4" class="regular-text autofill" placeholder="' . esc_attr__( "Hello 👋\nCan we help you?", 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . __( 'Define a text to encourage users to contact by WhatsApp', 'creame-whatsapp-me' ) . '</p>' .
						'<p class="joinchat-addon">' . sprintf(
							__( 'Add links, images, videos and more with %s', 'creame-whatsapp-me' ),
							'<a href="https://join.chat/' . $lang . '/addons/cta-extras/' . $utm . '" target="_blank">\'CTA Extras\'</a>'
						) . '</p>';
					break;

				case 'message_send':
					$output = '<textarea id="joinchat_message_send" name="joinchat[message_send]" rows="3" class="regular-text autofill" placeholder="' . esc_attr__( 'Hi *{SITE}*! I need more info about {TITLE} {URL}', 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . __( 'Predefined text for the first message the user will send you', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_start':
					$output = '<input id="joinchat_message_start" name="joinchat[message_start]" value="' . esc_attr( $value ) . '" type="text" maxlength="20" class="regular-text autofill" placeholder="' . esc_attr__( 'Open chat', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . __( 'Text to open chat on Chat Window button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_delay':
					$output = '<input id="joinchat_message_delay" name="joinchat[message_delay]" value="' . intval( $value ) . '" type="number" min="0" max="120" style="width:5em"> ' .
					__( 'seconds', 'creame-whatsapp-me' ) . ' (' . __( '0 to disable', 'creame-whatsapp-me' ) . ')' .
					'<p class="description">' . __( 'Chat Window auto displays after delay', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_views':
					$output = '<input id="joinchat_message_views" name="joinchat[message_views]" value="' . intval( $value ) . '" type="number" min="1" max="120" style="width:5em"> ' .
						'<p class="description">' . __( 'Chat Window auto displays from this number of page views', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_badge':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Notification Balloon', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_message_badge" name="joinchat[message_badge]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Display a notification balloon instead of opening the Chat Window for a "less intrusive" mode', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'color':
					$output = '<input id="joinchat_color" name="joinchat[color]" value="' . esc_attr( $value ) . '" type="text" data-default-color="#25d366"> ';
					break;

				case 'dark_mode':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Dark Mode', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[dark_mode]" value="no" type="radio"' . checked( 'no', $value, false ) . '> ' .
						__( 'No', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[dark_mode]" value="yes" type="radio"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Yes', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[dark_mode]" value="auto" type="radio"' . checked( 'auto', $value, false ) . '> ' .
						__( 'Auto (detects device dark mode)', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'header':
					$check = in_array( $value, array( '__jc__', '__wa__' ) ) ? $value : '__custom__';
					$value = '__custom__' == $check ? $value : '';

					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Header', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[header]" value="__jc__" type="radio"' . checked( '__jc__', $check, false ) . '> ' .
						__( 'Powered by Join.chat', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[header]" value="__wa__" type="radio"' . checked( '__wa__', $check, false ) . '> ' .
						__( 'WhatsApp Logo', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[header]" value="__custom__" type="radio"' . checked( '__custom__', $check, false ) . '> ' .
						__( 'Custom:', 'creame-whatsapp-me' ) . '</label> ' .
						'<input id="joinchat_header_custom" name="joinchat[header_custom]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text">' .
						'</fieldset>';
					break;

				case 'optin_text':
					$editor_settings = array(
						'textarea_name' => 'joinchat[optin_text]',
						'textarea_rows' => 4,
						'teeny'         => true,
						'media_buttons' => false,
						'tinymce'       => array( 'statusbar' => false ),
						'quicktags'     => false,
					);

					// phpcs:disable
					add_filter( 'teeny_mce_plugins', function( $filters, $editor_id ) {
						return 'joinchat_optin_text' === $editor_id ? array( 'wordpress', 'wplink' ) : $filters;
					}, 10, 2 );

					add_filter( 'teeny_mce_buttons', function( $mce_buttons, $editor_id ) {
						return 'joinchat_optin_text' === $editor_id ? array( 'bold', 'italic', 'link' ) : $mce_buttons;
					}, 10, 2 );
					// phpcs:enable

					$output = wp_editor( $value, 'joinchat_optin_text', $editor_settings ) .
						'<p class="description">' . __( "Explain how you will use the user's contact and the conditions they accept.", 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'optin_check':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Opt-in Required', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_optin_check" name="joinchat[optin_check]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'User approval is required to enable the contact button', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'gads':
					$output = '<input id="joinchat_gads" name="joinchat[gads]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text" ' .
						'placeholder="' . esc_attr__( 'AW-CONVERSION_ID/CONVERSION_LABEL', 'creame-whatsapp-me' ) . '" title="' . esc_attr__( 'AW-CONVERSION_ID/CONVERSION_LABEL', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . __( 'Send the conversion automatically at the chat start', 'creame-whatsapp-me' ) . '</p>';
					break;

				default:
					$output = '';
					break;
			}
		}

		// Filter field ouput
		echo apply_filters( 'joinchat_field_output', $output, $field_id, $this->settings );
	}

	/**
	 * Field 'field_view_all' output
	 *
	 * @since    2.0.0
	 * @since    3.0.0     Added $inheritance data
	 * @return   void
	 */
	public function field_view_all() {
		$value = ( isset( $this->settings['visibility']['all'] ) && 'no' == $this->settings['visibility']['all'] ) ? 'no' : 'yes';

		$inheritance = apply_filters(
			'joinchat_visibility_inheritance',
			array(
				'all'      => array( 'front_page', 'blog_page', '404_page', 'search', 'archive', 'singular', 'cpts' ),
				'archive'  => array( 'date', 'author' ),
				'singular' => array( 'page', 'post' ),
			)
		);

		echo '<div class="joinchat_view_all" data-inheritance="' . esc_attr( json_encode( $inheritance ) ) . '">' .
			'<label><input type="radio" name="joinchat[view][all]" value="yes"' . checked( 'yes', $value, false ) . '> ' .
			'<span class="dashicons dashicons-visibility" title="' . __( 'Show', 'creame-whatsapp-me' ) . '"></span></label>' .
			'<label><input type="radio" name="joinchat[view][all]" value="no"' . checked( 'no', $value, false ) . '> ' .
			'<span class="dashicons dashicons-hidden" title="' . __( 'Hide', 'creame-whatsapp-me' ) . '"></span></label></div>';
	}

	/**
	 * Add menu to the options page in the WordPress admin
	 *
	 * @since    1.0.0
	 * @since    4.2.0 allowed direct menu page
	 * @access   public
	 * @return   void
	 */
	public function add_menu() {

		$title = 'Join.chat';

		if ( JoinChatUtil::options_submenu() ) {
			$icon = '<span class="dashicons dashicons-whatsapp" aria-hidden="true" style="height:18px;font-size:18px;margin:0 8px;"></span>';

			add_options_page( $title, $title . $icon, JoinChatUtil::capability(), $this->plugin_name, array( $this, 'options_page' ) );
		} else {
			add_menu_page( $title, $title, JoinChatUtil::capability(), $this->plugin_name, array( $this, 'options_page' ), 'dashicons-whatsapp', 81 );
		}

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

		// If no phone number defined
		if ( empty( $this->settings['telephone'] )
			&& current_user_can( JoinChatUtil::capability() )
			&& ( $current_screen && false === strpos( $current_screen->id, '_joinchat' ) )
			&& time() >= (int) get_option( 'joinchat_notice_dismiss' )
		) {

			printf(
				'<div class="notice notice-info is-dismissible" id="joinchat-empty-phone"><p><strong>Join.chat</strong>&nbsp;&nbsp;%s %s</p></div>',
				__( 'You only need to add your WhatsApp number to contact with your users.', 'creame-whatsapp-me' ),
				sprintf( '<a href="%s"><strong>%s</strong></a>', JoinChatUtil::admin_url(), __( 'Go to settings', 'creame-whatsapp-me' ) )
			);

			printf(
				'<script>jQuery("#joinchat-empty-phone").on("click", ".notice-dismiss", function () {' .
				'jQuery.post(ajaxurl, { action: "joinchat_notice_dismiss", nonce: "%s"}, null, "json");' .
				'});</script>',
				wp_create_nonce( 'joinchat_nonce' )
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
	 * Add a help tab to the options page in the WordPress admin
	 *
	 * @since    3.0.0
	 * @access   public
	 * @return   void
	 */
	function help_tab() {
		$screen = get_current_screen();
		$utm    = '?utm_source=helptab&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
		$lang   = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

		$help_tabs = array(
			array(
				'id'      => 'styles-and-vars',
				'title'   => __( 'Styles and Variables', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . __( 'You can use formatting styles like in WhatsApp: _<em>italic</em>_ *<strong>bold</strong>* ~<del>strikethrough</del>~.', 'creame-whatsapp-me' ) . '</p>' .
					'<p>' . __( 'You can use dynamic variables that will be replaced by the values of the page the user visits:', 'creame-whatsapp-me' ) .
					'<p>' .
					'<span><code>{SITE}</code> ➜ ' . get_bloginfo( 'name', 'display' ) . '</span><br> ' .
					'<span><code>{TITLE}</code> ➜ ' . __( 'Page Title', 'creame-whatsapp-me' ) . '</span><br>' .
					'<span><code>{URL}</code> ➜ ' . home_url( 'awesome/' ) . '</span><br> ' .
					'<span><code>{HREF}</code> ➜ ' . home_url( 'awesome/' ) . '?utm_source=twitter&utm_medium=social&utm_campaign=XXX</span> ' .
					'</p>',
			),
			array(
				'id'      => 'triggers',
				'title'   => __( 'Triggers', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . __( 'You can interact on your page with Join.chat in two ways:', 'creame-whatsapp-me' ) . '</p>' .
					'<p>' . __( 'With anchor links:', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
						'<li><code>#joinchat</code> ' . __( 'to show Chat Window (or open WhatsApp if there is no CTA) on click.', 'creame-whatsapp-me' ) . '</li>' .
						'<li><code>#whatsapp</code> ' . __( 'to open WhatsApp directly on click.', 'creame-whatsapp-me' ) . '</li>' .
					'</ul>' .
					'<p>' . __( 'Example:', 'creame-whatsapp-me' ) . '<code>&lt;a href="#whatsapp"&gt;' . __( 'Contact us', 'creame-whatsapp-me' ) . '&lt;/a&gt;</code></p>' .
					'<p>' . __( 'Adding some CSS classes in your HTML:', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
						'<li><code>joinchat_open</code> ' . __( 'to show Chat Window (or open WhatsApp if there is no CTA) on click.', 'creame-whatsapp-me' ) . '</li>' .
						'<li><code>joinchat_close</code> ' . __( 'to hide Chat Window on click.', 'creame-whatsapp-me' ) . '</li>' .
						'<li><code>joinchat_app</code> ' . __( 'to open WhatsApp directly on click.', 'creame-whatsapp-me' ) . '</li>' .
						'<li>' . __( 'To show Chat Window when an HTML element appears on screen when user scrolls:', 'creame-whatsapp-me' ) .
						'<ul>' .
							'<li><code>joinchat_show</code> ' . __( 'only show if it\'s an not seen CTA.', 'creame-whatsapp-me' ) . '</li>' .
							'<li><code>joinchat_force_show</code> ' . __( 'to show always.', 'creame-whatsapp-me' ) . '</li>' .
						'</ul></li>' .
					'</ul>' .
					'<p>' . __( 'Example:', 'creame-whatsapp-me' ) . '<code>&lt;img src="contact.jpg" class="joinchat_open" alt="' . __( 'Contact us', 'creame-whatsapp-me' ) . '"&gt;</code></p>',
			),
			array(
				'id'      => 'support',
				'title'   => __( 'Support', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . sprintf(
						__( 'If you need help, first review our <a href="%1$s" target="_blank">documentation</a> and if you don\'t find a solution check the <a href="%2$s" target="_blank">free plugin support forum</a> or buy our <a href="%3$s" target="_blank">premium support</a>.', 'creame-whatsapp-me' ),
						esc_url( "https://join.chat/$lang/docs/$utm" ),
						esc_url( 'https://wordpress.org/support/plugin/creame-whatsapp-me/' ),
						esc_url( "https://my.join.chat/$utm" )
					) . '</p>' .
					'<p>' . __( 'If you like Join.chat 😍', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
					'<li>' . sprintf(
						__( "Please leave us a %s rating. We'll thank you.", 'creame-whatsapp-me' ),
						'<a href="https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post" target="_blank">★★★★★</a>'
					) . '</li>' .
					'<li>' . sprintf(
						__( 'Subscribe to our newsletter and visit our blog at %s.', 'creame-whatsapp-me' ),
						'<a href="https://join.chat/' . $utm . '" target="_blank">join.chat</a>'
					) . '</li>' .
					'<li>' . sprintf(
						__( 'Follow %s on twitter.', 'creame-whatsapp-me' ),
						'<a href="https://twitter.com/joinchatnow" target="_blank">@joinchatnow</a>'
					) . '</li>' .
					'</ul>',
			),
		);

		foreach ( $help_tabs as $tab_data ) {
			$screen->add_help_tab( apply_filters( 'joinchat_help_tab_' . str_replace( '-', '_', $tab_data['id'] ), $tab_data ) );
		}

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

		if ( 'creame-whatsapp-me/joinchat.php' == $plugin_file ) {
			$utm  = '?utm_source=plugins&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
			$lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( "https://join.chat/$lang/docs/$utm" ), __( 'Documentation', 'creame-whatsapp-me' ) );
			$plugin_meta[] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( "https://join.chat/$lang/support/$utm" ), __( 'Support', 'creame-whatsapp-me' ) );
		}

		return $plugin_meta;

	}

	/**
	 * Generate the options page in the WordPress admin
	 *
	 * @since    1.0.0
	 * @since    2.2.0     Enqueue scripts/styles
	 * @access   public
	 * @return   void
	 */
	function options_page() {

		// Enqueue WordPress media scripts
		wp_enqueue_media();
		// Enqueue assets
		wp_enqueue_script( 'joinchat-admin' );
		wp_enqueue_style( 'joinchat-admin' );

		if ( $this->enhanced_phone ) {
			wp_enqueue_style( 'intl-tel-input' );
		}

		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ) ) ? $_GET['tab'] : 'general';
		?>
			<div class="wrap">
				<h1><?php _e( 'Join.chat Settings', 'creame-whatsapp-me' ); ?></h1>

				<?php
				if ( ! JoinChatUtil::options_submenu() ) {
					settings_errors();
				}
				?>

				<form method="post" id="joinchat_form" action="options.php" autocomplete="off">
					<?php settings_fields( $this->plugin_name ); ?>
					<h2 class="nav-tab-wrapper wp-clearfix" role="tablist">
						<?php foreach ( $this->tabs as $tab => $name ) : ?>
							<?php if ( $active_tab === $tab ) : ?>
								<a id="navtab_<?php echo $tab; ?>" href="#joinchat_tab_<?php echo $tab; ?>" class="nav-tab nav-tab-active" role="tab" aria-controls="joinchat_tab_<?php echo $tab; ?>" aria-selected="true"><?php echo $name; ?></a>
							<?php else : ?>
								<a id="navtab_<?php echo $tab; ?>" href="#joinchat_tab_<?php echo $tab; ?>" class="nav-tab" role="tab" aria-controls="joinchat_tab_<?php echo $tab; ?>" aria-selected="false"><?php echo $name; ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</h2>
					<div class="joinchat-tabs">
						<?php do_settings_sections( $this->plugin_name ); ?>
					</div><!-- end tabs -->
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Add Meta Box for all the public post types
	 *
	 * @since    1.1.0
	 * @access   public
	 * @return   void
	 */
	public function add_meta_boxes() {
		// Default post types
		$builtin_post_types = array( 'post', 'page' );
		// Custom post types with public url
		$custom_post_types = array_keys( get_post_types( array( 'has_archive' => true ), 'names' ) );

		// Add/remove posts types for "Join.chat" meta box
		$post_types = apply_filters( 'joinchat_post_types_meta_box', array_merge( $builtin_post_types, $custom_post_types ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				$this->plugin_name,
				__( 'Join.chat', 'creame-whatsapp-me' ),
				array( $this, 'meta_box' ),
				$post_type,
				'side',
				'default'
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
	 * @param  WP_Post $post Current post object
	 * @return void
	 */
	public function meta_box( $post ) {

		// Enqueue assets
		wp_enqueue_script( 'joinchat-admin' );
		wp_enqueue_style( 'joinchat-admin' );

		if ( $this->enhanced_phone ) {
			wp_enqueue_style( 'intl-tel-input' );
		}

		$metadata = get_post_meta( $post->ID, '_joinchat', true ) ?: array();
		$metadata = array_merge(
			array(
				'telephone'    => '',
				'message_text' => '',
				'message_send' => '',
				'view'         => '',
			),
			$metadata
		);

		$placeholders = apply_filters(
			'joinchat_metabox_placeholders',
			array(
				'telephone'    => $this->settings['telephone'],
				'message_text' => $this->settings['message_text'],
				'message_send' => $this->settings['message_send'],
			),
			$post,
			$this->settings
		);

		$metabox_vars = apply_filters( 'joinchat_metabox_vars', array( 'SITE', 'TITLE', 'URL', 'HREF' ), $post );

		ob_start();
		include __DIR__ . '/partials/post_meta_box.php';
		$metabox_output = ob_get_clean();

		echo apply_filters( 'joinchat_metabox_output', $metabox_output, $post, $metadata );
	}

	/**
	 * Save meta data from "Join.chat"
	 *
	 * @since    4.3.0
	 * @access   public
	 * @param  int         $id post|term ID
	 * @param  WP_Post|int $arg current post or term taxonomi id
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

		// Clean and delete empty/false fields
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
	 * @param  WP_Term $term Current taxonomy term object
	 * @param  string  $taxonomy Current taxonomy slug
	 * @return void
	 */
	public function term_meta_box( $term, $taxonomy ) {

		// Enqueue assets
		wp_enqueue_script( 'joinchat-admin' );
		wp_enqueue_style( 'joinchat-admin' );

		if ( $this->enhanced_phone ) {
			wp_enqueue_style( 'intl-tel-input' );
		}

		$metadata = get_term_meta( $term->term_id, '_joinchat', true ) ?: array();
		$metadata = array_merge(
			array(
				'telephone'    => '',
				'message_text' => '',
				'message_send' => '',
				'view'         => '',
			),
			$metadata
		);

		$placeholders = apply_filters(
			'joinchat_metabox_placeholders',
			array(
				'telephone'    => $this->settings['telephone'],
				'message_text' => $this->settings['message_text'],
				'message_send' => $this->settings['message_send'],
			),
			$term,
			$this->settings
		);

		$metabox_vars = apply_filters( 'joinchat_metabox_vars', array( 'SITE', 'TITLE', 'URL', 'HREF' ), $term );

		ob_start();
		include __DIR__ . '/partials/term_meta_box.php';
		$metabox_output = ob_get_clean();

		echo apply_filters( 'joinchat_term_metabox_output', $metabox_output, $term, $metadata, $taxonomy );
	}

	/**
	 * Return html for dynamic variables help next to field label
	 *
	 * @since    3.1.2
	 * @access   public
	 * @param    string $field       field name.
	 * @return   string
	 */
	public static function vars_help( $field ) {

		$vars = apply_filters( 'joinchat_vars_help', array( 'SITE', 'TITLE', 'URL', 'HREF' ), $field );

		return count( $vars ) ? '<div class="joinchat_vars_help">' . __( 'You can use vars', 'creame-whatsapp-me' ) . ' ' .
			'<a class="joinchat-show-help" href="#" title="' . __( 'Show Help', 'creame-whatsapp-me' ) . '">?</a><br> ' .
			'<code>{' . join( '}</code> <code>{', $vars ) . '}</code></div>' : '';

	}

	/**
	 * Clear third party cache plugins if joinchat option changed
	 *
	 * @since    4.0.5
	 * @access   public
	 * @param    mixed $old_value  joinchat previous settings.
	 * @param    mixed $value      joinchat new settings.
	 * @return   void
	 */
	public static function clear_cache() {

		// TODO: Prevent Autoptimize clear many times

		/**
		 * List of callable functions or actions by third party plugins.
		 * format: string callable or array( string callable or hook, [, mixed $parameter [, mixed $... ]] )
		 */
		$cache_plugins = apply_filters(
			'joinchat_cache_plugins',
			array(
				'autoptimizeCache::clearall_actionless', // Autoptimize https://wordpress.org/plugins/autoptimize/
				'ce_clear_cache',                        // Cache Enabler https://wordpress.org/plugins/cache-enabler/
				'cachify_flush_cache',                   // Cachify https://wordpress.org/plugins/cachify/
				'LiteSpeed_Cache_API::purge_all',        // LiteSpeed Cache https://wordpress.org/plugins/litespeed-cache/
				'sg_cachepress_purge_cache',             // SG Optimizer https://es.wordpress.org/plugins/sg-cachepress/
				array( 'wpfc_clear_all_cache', true ),   // WP Fastest Cache https://es.wordpress.org/plugins/wp-fastest-cache/
				'rocket_clean_minify',                   // WP Rocket https://wp-rocket.me
				'rocket_clean_domain',
				'wp_cache_clear_cache',                  // WP Super Cache https://wordpress.org/plugins/wp-super-cache/
				'w3tc_flush_all',                        // W3 Total Cache https://wordpress.org/plugins/w3-total-cache/
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
	 * Modifies the "Thank you" text displayed in the admin footer.
	 *
	 * @since 4.0.10
	 * @access public
	 * @param string $footer_text The content that will be printed.
	 * @return string The content that will be printed.
	 */
	public function admin_footer_text( $footer_text ) {
		$current_screen = get_current_screen();

		if ( $current_screen && false !== strpos( $current_screen->id, '_joinchat' ) ) {
			$footer_text = sprintf(
				__( 'Do you like %1$s? Please help us with a %2$s rating.', 'creame-whatsapp-me' ),
				'<strong>Join.chat</strong>',
				'<a href="https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post" target="_blank">★★★★★</a>'
			);
		}

		return $footer_text;
	}
}
