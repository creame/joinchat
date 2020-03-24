<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @since      2.0.0      Added advanced visibility settings
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

		// Updated in get_settings() at 'admin_init' hook
		$this->enhanced_phone = '16.0.11'; // intl-tel-input version
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
	 */
	public function get_settings() {

		// Use International Telephone Input library version or false to disable
		$this->enhanced_phone = apply_filters( 'joinchat_enhanced_phone', $this->enhanced_phone );

		// Admin tabs
		$this->tabs = apply_filters(
			'joinchat_admin_tabs',
			array(
				'general'  => __( 'General', 'creame-whatsapp-me' ),
				'advanced' => __( 'Advanced', 'creame-whatsapp-me' ),
			)
		);

		// Default settings
		$default_settings = array_merge(
			array(
				'telephone'     => '',
				'mobile_only'   => 'no',
				'button_image'  => '',
				'button_tip'    => '',
				'button_delay'  => 3,
				'whatsapp_web'  => 'no',
				'message_text'  => '',
				'message_delay' => 10,
				'message_badge' => 'no',
				'message_send'  => '',
				'message_start' => __( 'Open chat', 'creame-whatsapp-me' ),
				'position'      => 'right',
				'visibility'    => array( 'all' => 'yes' ),
				'dark_mode'     => 'no',
			),
			apply_filters( 'joinchat_extra_settings', array() )
		);

		$saved_settings = get_option( 'joinchat' );

		if ( is_array( $saved_settings ) ) {
			// clean unused saved settings
			$saved_settings = array_intersect_key( $saved_settings, $default_settings );
			// merge defaults with saved settings
			$this->settings = array_merge( $default_settings, $saved_settings );
			// miliseconds (<v2.3) to seconds
			if ( $this->settings['message_delay'] > 120 ) {
				$this->settings['message_delay'] = round( $this->settings['message_delay'] / 1000 );
			}
		} else {
			$this->settings = $default_settings;
		}

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    3.0.0
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function register_styles( $hook ) {

		$styles = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'joinchat.css' : 'joinchat.min.css';
		wp_register_style( 'joinchat-admin', plugin_dir_url( __FILE__ ) . 'css/' . $styles, array(), $this->version, 'all' );

		if ( $this->enhanced_phone ) {
			wp_register_style( 'intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/' . $this->enhanced_phone . '/css/intlTelInput.css', array(), null, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    3.0.0
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function register_scripts( $hook ) {

		$script = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'joinchat.js' : 'joinchat.min.js';

		if ( $this->enhanced_phone ) {
			wp_register_script( 'intl-tel-input', 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/' . $this->enhanced_phone . '/js/intlTelInput.min.js', array(), null, true );
			wp_register_script( 'joinchat-admin', plugin_dir_url( __FILE__ ) . 'js/' . $script, array( 'jquery', 'intl-tel-input' ), $this->version, true );
			wp_localize_script( 'intl-tel-input', 'intl_tel_input_version', $this->enhanced_phone );
		} else {
			wp_register_script( 'joinchat-admin', plugin_dir_url( __FILE__ ) . 'js/' . $script, array( 'jquery' ), $this->version, true );
		}

	}

	/**
	 * Initialize the settings for WordPress admin
	 * From v1.2.0 also set filter to disable enhanced phone input
	 *
	 * @since    1.0.0
	 * @since    2.0.0     Added tabs for general and Advanced settings
	 * @since    2.3.0     Split general settings in Button and Window Chat
	 * @since    3.0.0     Admin organized by tabs and sections
	 * @access   public
	 * @return   void
	 */
	public function settings_init() {

		// Register WordPress 'joinchat' settings
		register_setting( 'joinchat', 'joinchat', array( $this, 'settings_validate' ) );

		foreach ( $this->tabs as $tab => $tab_name ) {

			add_settings_section( "joinchat_tab_{$tab}_open", null, array( $this, 'settings_tab_open' ), 'joinchat' );

			$sections = $this->get_tab_sections( $tab );

			foreach ( $sections as $section => $fields ) {
				$section_id = "joinchat_tab_{$tab}__{$section}";

				add_settings_section( $section_id, null, array( $this, 'section_output' ), 'joinchat' );

				foreach ( $fields as $field => $field_args ) {
					if ( is_array( $field_args ) ) {
						$field_name     = $field_args['label'];
						$field_callback = $field_args['callback'];
					} else {
						$field_name     = $field_args;
						$field_callback = array( $this, 'field_output' );
					}

					add_settings_field( "joinchat_$field", $field_name, $field_callback, 'joinchat', $section_id, $field );
				}
			}

			add_settings_section( "joinchat_tab_{$tab}_close", null, array( $this, 'settings_tab_close' ), 'joinchat' );
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
				'button' => array(
					'telephone'    => '<label for="joinchat_phone">' . __( 'Telephone', 'creame-whatsapp-me' ) . '</label>',
					'message_send' => '<label for="joinchat_message_send">' . __( 'Message', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_send' ),
					'mobile_only'  => __( 'Mobile Only', 'creame-whatsapp-me' ),
					'position'     => __( 'Position on Screen', 'creame-whatsapp-me' ),
					'button_image' => __( 'Image', 'creame-whatsapp-me' ),
					'button_tip'   => __( 'Tooltip', 'creame-whatsapp-me' ),
					'button_delay' => '<label for="joinchat_button_delay">' . __( 'Button Delay', 'creame-whatsapp-me' ) . '</label>',
					'whatsapp_web' => __( 'WhatsApp Web', 'creame-whatsapp-me' ),
				),
				'chat'   => array(
					'message_text'  => '<label for="joinchat_message_text">' . __( 'Call to Action', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_text' ),
					'message_start' => '<label for="joinchat_message_start">' . __( 'Start WhatsApp Button', 'creame-whatsapp-me' ) . '</label>',
					'message_delay' => '<label for="joinchat_message_delay">' . __( 'Chat Delay', 'creame-whatsapp-me' ) . '</label>',
					'message_badge' => __( 'Notification Balloon', 'creame-whatsapp-me' ),
					'dark_mode'     => __( 'Dark Mode', 'creame-whatsapp-me' ),
				),
			);

		} elseif ( 'advanced' == $tab ) {

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
		if ( count( get_settings_errors( 'joinchat' ) ) ) {
			return $input;
		}

		$util = new JoinChatUtil(); // Shortcut

		$input['telephone']     = $util::clean_input( $input['telephone'] );
		$input['mobile_only']   = isset( $input['mobile_only'] ) ? 'yes' : 'no';
		$input['button_image']  = intval( $input['button_image'] );
		$input['button_tip']    = $util::substr( $util::clean_input( $input['button_tip'] ), 0, 40 );
		$input['button_delay']  = intval( $input['button_delay'] );
		$input['whatsapp_web']  = isset( $input['whatsapp_web'] ) ? 'yes' : 'no';
		$input['message_text']  = $util::clean_input( $input['message_text'] );
		$input['message_badge'] = isset( $input['message_badge'] ) ? 'yes' : 'no';
		$input['message_send']  = $util::clean_input( $input['message_send'] );
		$input['message_start'] = $util::substr( $util::clean_input( $input['message_start'] ), 0, 20 );
		$input['message_delay'] = intval( $input['message_delay'] );
		$input['position']      = $input['position'] != 'left' ? 'right' : 'left';
		$input['dark_mode']     = in_array( $input['dark_mode'], array( 'no', 'yes', 'auto' ) ) ? $input['dark_mode'] : 'no';
		if ( isset( $input['view'] ) ) {
			$input['visibility'] = array_filter(
				$input['view'],
				function( $v ) {
					return 'yes' == $v || 'no' == $v;
				}
			);
			unset( $input['view'] );
		}

		// Filter for other validations or extra settings
		$input = apply_filters( 'joinchat_settings_validate', $input );

		/**
		 * Register WPML/Polylang strings for translation
		 * https://wpml.org/wpml-hook/wpml_register_single_string/
		 */
		$settings_i18n = JoinChatUtil::settings_i18n();

		foreach ( $settings_i18n as $setting_key => $setting_name ) {
			do_action( 'wpml_register_single_string', 'Join.chat', $setting_name, $input[ $setting_key ] );
		}

		// Extra actions on save
		do_action( 'joinchat_settings_validate', $input );

		add_settings_error( 'joinchat', 'settings_updated', __( 'Settings saved', 'creame-whatsapp-me' ), 'updated' );

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

		$tab_id = str_replace( array( 'joinchat_tab_', '_open' ), '', $args['id'] );
		$active = 'general' == $tab_id ? 'joinchat-tab-active' : '';

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

			case 'joinchat_tab_advanced__global':
				$output = '<h2 class="title">' . __( 'Advanced Visibility Settings', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'From here you can configure on which pages the WhatsApp button will be visible.', 'creame-whatsapp-me' ) .
					' <a href="#" class="joinchat_view_reset">' . __( 'Restore default visibility', 'creame-whatsapp-me' ) . '</a></p>';
				break;

			case 'joinchat_tab_advanced__wp':
				$output = '<hr>';
				break;

			case 'joinchat_tab_advanced__cpt':
				$output = '<h2 class="title">' . __( 'Custom Post Types', 'creame-whatsapp-me' ) . '</h2>';
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
			$utm   = '?utm_source=wpadmin&utm_medium=settings&utm_campaign=v' . str_replace( '.', '_', $this->version );
			$lang  = _x( 'en', 'url lang slug (only avaible for spanish "es")', 'creame-whatsapp-me' );

			switch ( $field_id ) {
				case 'telephone':
					$output = '<input id="joinchat_phone" ' . ( $this->enhanced_phone ? 'data-' : '' ) . 'name="joinchat[telephone]" value="' . $value . '" type="text" style="width:15em">' .
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
					$thumb = intval( $value ) > 0 ? JoinChatUtil::thumb( $value, 116, 116 ) : false;
					$image = is_array( $thumb ) ? $thumb['url'] : false;

					$output = '<div id="joinchat_button_image_wrapper">' .
						'<div id="joinchat_button_image_holder" ' . ( $image ? "style=\"background-size:cover; background-image:url('$image');\"" : '' ) . '></div>' .
						'<input id="joinchat_button_image" name="joinchat[button_image]" type="hidden" value="' . $value . '">' .
						'<input id="joinchat_button_image_add" type="button" value="' . esc_attr__( 'Select an image', 'creame-whatsapp-me' ) . '" class="button-primary" ' .
						'data-title="' . esc_attr__( 'Select button image', 'creame-whatsapp-me' ) . '" data-button="' . esc_attr__( 'Use image', 'creame-whatsapp-me' ) . '"> ' .
						'<input id="joinchat_button_image_remove" type="button" value="' . esc_attr__( 'Remove', 'creame-whatsapp-me' ) . '" class="button-secondary' . ( $image ? '' : ' joinchat-hidden' ) . '">' .
						'<p class="description">' . __( 'The image will alternate with WhatsApp logo', 'creame-whatsapp-me' ) . '</p></div>';
					break;

				case 'button_tip':
					$output = '<input id="joinchat_button_tip" name="joinchat[button_tip]" value="' . $value . '" type="text" maxlength="40" class="regular-text" placeholder="' . esc_attr__( '💬 Need help?', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . __( 'Short text shown next to WhatsApp button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'button_delay':
					$output = '<input id="joinchat_button_delay" name="joinchat[button_delay]" value="' . $value . '" type="number" min="0" max="120" style="width:5em"> ' . __( 'seconds', 'creame-whatsapp-me' ) .
						'<p class="description">' . __( 'Time since the page is opened until the WhatsApp button is displayed', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'whatsapp_web':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'WhatsApp Web', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_whatsapp_web" name="joinchat[whatsapp_web]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Open <em>WhatsApp Web</em> directly on desktop', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'message_text':
					$output = '<textarea id="joinchat_message_text" name="joinchat[message_text]" rows="4" class="regular-text" placeholder="' . esc_attr__( "Hello 👋\nCan we help you?", 'creame-whatsapp-me' ) . '">' . $value . '</textarea>' .
						'<p class="description">' . __( 'Define a text to encourage users to contact by WhatsApp', 'creame-whatsapp-me' ) . '</p>' .
						'<p class="joinchat-addon">' . sprintf(
							__( 'Add links, images, videos and more with %s', 'creame-whatsapp-me' ),
							'<a href="https://join.chat/' . $lang . '/addons/cta-extras/' . $utm . '" target="_blank">\'CTA Extras\'</a>'
						) . '</p>';
					break;

				case 'message_send':
					$output = '<textarea id="joinchat_message_send" name="joinchat[message_send]" rows="3" class="regular-text" placeholder="' . esc_attr__( 'Hi *{SITE}*! I need more info about {TITLE} {URL}', 'creame-whatsapp-me' ) . '">' . $value . '</textarea>' .
						'<p class="description">' . __( 'Predefined text for the first message the user will send you', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_start':
					$output = '<input id="joinchat_message_start" name="joinchat[message_start]" value="' . $value . '" type="text" maxlength="20" class="regular-text" placeholder="' . esc_attr__( 'Open chat', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . __( 'Text of the start WhatsApp button on Chat Window', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_delay':
					$output = '<input id="joinchat_message_delay" name="joinchat[message_delay]" value="' . $value . '" type="number" min="0" max="120" style="width:5em"> ' . __( 'seconds (0 disabled)', 'creame-whatsapp-me' ) .
						'<p class="description">' . __( 'Chat Window is automatically displayed after delay', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_badge':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . __( 'Notification Balloon', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_message_badge" name="joinchat[message_badge]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						__( 'Display a notification balloon instead of opening the Chat Window for a "less intrusive" mode', 'creame-whatsapp-me' ) . '</label></fieldset>';
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
			'joinchat_advanced_inheritance',
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
	 * @access   public
	 * @return   void
	 */
	public function add_menu() {

		add_options_page( 'Join.chat', 'Join.chat', 'manage_options', 'joinchat', array( $this, 'options_page' ) );

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
		$utm    = '?utm_source=wpadmin&utm_medium=helptab&utm_campaign=v' . str_replace( '.', '_', $this->version );
		$lang   = _x( 'en', 'url lang slug (only avaible for spanish "es")', 'creame-whatsapp-me' );

		$help_tabs = array(
			array(
				'id'      => 'support',
				'title'   => __( 'Support and Help', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . sprintf(
						__(
							'If you need help, first review our <a href="%1$s" rel="external" target="_blank">documentation</a> ' .
							'and if you don\'t find a solution check the <a href="%2$s" rel="external" target="_blank">free plugin support forum</a> ' .
							'or buy our <a href="%3$s" rel="external" target="_blank">premium support</a>.',
							'creame-whatsapp-me'
						),
						esc_url( 'https://join.chat/' . $lang . '/docs/' . $utm ),
						esc_url( 'https://wordpress.org/support/plugin/creame-whatsapp-me/' ),
						esc_url( 'https://my.join.chat/' . $utm )
					) . '</p>' .
					'<p>' . __( 'If you like Join.chat 😍', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
					'<li>' . sprintf(
						__( "Please leave us a %s rating. We'll thank you.", 'creame-whatsapp-me' ),
						'<a href="https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post" rel="external" target="_blank">★★★★★</a>'
					) . '</li>' .
					'<li>' . sprintf(
						__( 'Subscribe to our newsletter and visit our blog at %s.', 'creame-whatsapp-me' ),
						'<a href="https://join.chat/' . $utm . '" rel="external" target="_blank">join.chat</a>'
					) . '</li>' .
					'<li>' . sprintf(
						__( 'Follow %s on twitter.', 'creame-whatsapp-me' ),
						'<a href="https://twitter.com/joinchatnow" rel="external" target="_blank">@joinchatnow</a>'
					) . '</li>' .
					'</ul>',
			),
			array(
				'id'      => 'styles-and-vars',
				'title'   => __( 'Styles and Variables', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . __( 'You can use formatting styles like in WhatsApp: _<em>italic</em>_ *<strong>bold</strong>* ~<del>strikethrough</del>~.', 'creame-whatsapp-me' ) . '</p>' .
					'<p>' . __( 'You can use dynamic variables that will be replaced by the values of the page the user visits:', 'creame-whatsapp-me' ) .
					'<p>' .
					'<span><code>{SITE}</code> ➜ ' . get_bloginfo( 'name', 'display' ) . '</span>, ' .
					'<span><code>{URL}</code> ➜ ' . home_url( 'example' ) . '</span>, ' .
					'<span><code>{TITLE}</code> ➜ ' . __( 'Page Title', 'creame-whatsapp-me' ) . '</span>' .
					'</p>',
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
	 * @return   void
	 */
	public function settings_link( $links ) {

		$settings_link = '<a href="options-general.php?page=' . $this->plugin_name . '">' . __( 'Settings', 'creame-whatsapp-me' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;

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

		?>
			<div class="wrap">
				<h1><?php _e( 'Join.chat Settings', 'creame-whatsapp-me' ); ?></h1>

				<form method="post" id="joinchat_form" action="options.php" autocomplete="off">
					<?php settings_fields( 'joinchat' ); ?>
					<h2 class="nav-tab-wrapper wp-clearfix" role="tablist">
						<?php foreach ( $this->tabs as $tab => $name ) : ?>
							<?php if ( 'general' === $tab ) : ?>
								<a id="navtab_<?php echo $tab; ?>" href="#joinchat_tab_<?php echo $tab; ?>" class="nav-tab nav-tab-active" role="tab" aria-controls="joinchat_tab_<?php echo $tab; ?>" aria-selected="true"><?php echo $name; ?></a>
							<?php else : ?>
								<a id="navtab_<?php echo $tab; ?>" href="#joinchat_tab_<?php echo $tab; ?>" class="nav-tab" role="tab" aria-controls="joinchat_tab_<?php echo $tab; ?>" aria-selected="false"><?php echo $name; ?></a>
							<?php endif; ?>
						<?php endforeach; ?>
					</h2>
					<div class="joinchat-tabs">
						<?php do_settings_sections( 'joinchat' ); ?>
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
				'joinchat',
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
	 * @return   void
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
				'hide'         => false,
				'view'         => '',
			),
			$metadata
		);

		// Move old 'hide' to new 'view' field
		if ( $metadata['hide'] ) {
			$metadata['view'] = 'no';
		}
		unset( $metadata['hide'] );

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

		$metabox_vars = apply_filters( 'joinchat_metabox_vars', array( 'SITE', 'URL', 'TITLE' ), $post );

		ob_start();
		?>
			<div class="joinchat-metabox">
				<?php wp_nonce_field( 'joinchat_data', 'joinchat_nonce' ); ?>
				<p>
					<label for="joinchat_phone"><?php _e( 'Telephone', 'creame-whatsapp-me' ); ?></label><br>
					<input id="joinchat_phone" <?php echo $this->enhanced_phone ? 'data-' : ''; ?>name="joinchat_telephone" value="<?php echo $metadata['telephone']; ?>" type="text" placeholder="<?php echo $placeholders['telephone']; ?>">
				</p>
				<p>
					<label for="joinchat_message"><?php _e( 'Call to Action', 'creame-whatsapp-me' ); ?></label><br>
					<textarea id="joinchat_message" name="joinchat_message" rows="2" placeholder="<?php echo $placeholders['message_text']; ?>" class="large-text"><?php echo $metadata['message_text']; ?></textarea>
				</p>
				<p>
					<label for="joinchat_message_send"><?php _e( 'Message', 'creame-whatsapp-me' ); ?></label><br>
					<textarea id="joinchat_message_send" name="joinchat_message_send" rows="2" placeholder="<?php echo $placeholders['message_send']; ?>" class="large-text"><?php echo $metadata['message_send']; ?></textarea>
					<?php if ( count( $metabox_vars ) ) : ?>
						<small><?php _e( 'Can use vars', 'creame-whatsapp-me' ); ?> <code>{<?php echo join( '}</code> <code>{', $metabox_vars ); ?>}</code></small>
					<?php endif; ?>
					<small><?php _e( 'to leave it blank use', 'creame-whatsapp-me' ); ?> <code>{}</code></small>
				</p>
				<p>
					<label><input type="radio" name="joinchat_view" value="yes" <?php checked( 'yes', $metadata['view'] ); ?>>
						<span class="dashicons dashicons-visibility" title="<?php echo __( 'Show', 'creame-whatsapp-me' ); ?>"></span></label>
					<label><input type="radio" name="joinchat_view" value="no" <?php checked( 'no', $metadata['view'] ); ?>>
						<span class="dashicons dashicons-hidden" title="<?php echo __( 'Hide', 'creame-whatsapp-me' ); ?>"></span></label>
					<label><input type="radio" name="joinchat_view" value="" <?php checked( '', $metadata['view'] ); ?>>
						<?php echo __( 'Default visibility', 'creame-whatsapp-me' ); ?></label>
				</p>
			</div>
		<?php
		$metabox_output = ob_get_clean();

		echo apply_filters( 'joinchat_metabox_output', $metabox_output, $post, $metadata );
	}

	/**
	 * Save meta data from "Join.chat" Meta Box on post save
	 *
	 * @since    1.1.0
	 * @since    2.0.0     Change 'hide' key to 'view' now values can be [yes, no]
	 * @since    2.2.0     Added "telephone"
	 * @since    3.0.3     Filter metadata before save
	 * @access   public
	 * @return   void
	 */
	public function save_post( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) ||
			 ! isset( $_POST['joinchat_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['joinchat_nonce'], 'joinchat_data' ) ) {
			return;
		}

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

		$metadata = apply_filters( 'joinchat_metabox_save', $metadata, $post_id );

		if ( count( $metadata ) ) {
			update_post_meta( $post_id, '_joinchat', $metadata );
		} else {
			delete_post_meta( $post_id, '_joinchat' );
		}
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

		$vars = apply_filters( 'joinchat_vars_help', array( 'SITE', 'URL', 'TITLE' ), $field );

		return count( $vars ) ? '<div class="joinchat_vars_help">' . __( 'You can use vars', 'creame-whatsapp-me' ) . ' ' .
			'<a class="joinchat-show-help" href="#" title="' . __( 'Show Help', 'creame-whatsapp-me' ) . '">?</a><br> ' .
			'<code>{' . join( '}</code> <code>{', $vars ) . '}</code></div>' : '';

	}

}
