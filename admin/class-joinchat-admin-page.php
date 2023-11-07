<?php
/**
 * The admin settings page of the plugin.
 *
 * @package    Joinchat
 */

/**
 * The admin settings page of the plugin.
 *
 * @since      4.5.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Admin_Page {

	/**
	 * Admin page tabs
	 *
	 * @since    4.5.0
	 * @access   private
	 * @var      array    $tabs    Admin page tabs.
	 */
	private $tabs = array();

	/**
	 * Add menu to the options page in the WordPress admin
	 *
	 * @since    4.5.0
	 * @access   public
	 * @return   void
	 */
	public function add_menu() {

		$title = 'Joinchat';

		if ( Joinchat_Util::options_submenu() ) {
			$icon = '<span class="dashicons dashicons-whatsapp" aria-hidden="true" style="height:18px;font-size:18px;margin:0 8px;"></span>';

			add_options_page( $title, $title . $icon, Joinchat_Util::capability(), JOINCHAT_SLUG, array( $this, 'options_page' ) );
		} else {
			add_menu_page( $title, $title, Joinchat_Util::capability(), JOINCHAT_SLUG, array( $this, 'options_page' ), 'dashicons-whatsapp', 81 );
		}

	}

	/**
	 * Initialize the settings for WordPress admin
	 *
	 * @since    5.0.0 before named settings_init()
	 * @access   public
	 * @return   void
	 */
	public function setting_fields() {

		// Admin tabs.
		$this->tabs = apply_filters(
			'joinchat_admin_tabs',
			array(
				'general'    => __( 'General', 'creame-whatsapp-me' ),
				'visibility' => __( 'Visibility', 'creame-whatsapp-me' ),
				'advanced'   => __( 'Advanced', 'creame-whatsapp-me' ),
			)
		);

		foreach ( $this->tabs as $tab => $tab_name ) {

			add_settings_section( "joinchat_tab_{$tab}_open", '', array( $this, 'settings_tab_open' ), JOINCHAT_SLUG );

			$sections = $this->get_tab_sections( $tab );

			foreach ( $sections as $section => $fields ) {
				$section_id = "joinchat_tab_{$tab}__{$section}";

				add_settings_section( $section_id, '', array( $this, 'section_output' ), JOINCHAT_SLUG );

				foreach ( $fields as $field => $field_args ) {
					if ( is_array( $field_args ) ) {
						$field_name     = $field_args['label'];
						$field_callback = $field_args['callback'];
					} else {
						$field_name     = $field_args;
						$field_callback = array( $this, 'field_output' );
					}

					add_settings_field( "joinchat_$field", $field_name, $field_callback, JOINCHAT_SLUG, $section_id, $field );
				}
			}

			add_settings_section( "joinchat_tab_{$tab}_close", '', array( $this, 'settings_tab_close' ), JOINCHAT_SLUG );
		}

	}

	/**
	 * Add settings page hooks
	 *
	 * @since    5.0.0
	 * @return void
	 */
	public function page_hooks() {

		if ( isset( $_GET['onboard'] ) ) {
			$is_onboard = true === filter_var( $_GET['onboard'], FILTER_VALIDATE_BOOLEAN );
		} else {
			$is_onboard = jc_common()->settings === jc_common()->defaults();
		}

		// Redirect to onboard page.
		if ( apply_filters( 'joinchat_onboard', $is_onboard ) ) {
			wp_safe_redirect( add_query_arg( 'page', 'joinchat-onboard', admin_url( 'admin.php' ) ) );
			return;
		}

		$this->help_tab();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'in_admin_header', array( $this, 'admin_header' ) );

		add_filter( 'admin_title', array( $this, 'admin_title' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), PHP_INT_MAX );

	}

	/**
	 * Return an array of sections and fields for the admin tab
	 *
	 * @since    4.5.0
	 * @param    string $tab       The id of the admin tab.
	 * @return   array
	 */
	private function get_tab_sections( $tab ) {

		switch ( $tab ) {

			case 'general':
				$sections = array(
					'button'        => array(
						'telephone'    => '<label for="joinchat_phone">' . esc_html__( 'Telephone', 'creame-whatsapp-me' ) . '</label>',
						'message_send' => '<label for="joinchat_message_send">' . esc_html__( 'Message', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_send' ),
						'button_image' => esc_html__( 'Image', 'creame-whatsapp-me' ),
						'button_tip'   => '<label for="joinchat_button_tip">' . esc_html__( 'Tooltip', 'creame-whatsapp-me' ) . '</label>',
						'position'     => esc_html__( 'Position on Screen', 'creame-whatsapp-me' ),
						'button_delay' => '<label for="joinchat_button_delay">' . esc_html__( 'Button Delay', 'creame-whatsapp-me' ) . '</label>',
						'mobile_only'  => esc_html__( 'Mobile Only', 'creame-whatsapp-me' ),
						'whatsapp_web' => esc_html__( 'WhatsApp Web', 'creame-whatsapp-me' ),
						'qr'           => esc_html__( 'QR Code', 'creame-whatsapp-me' ),
					),
					'chat'          => array(
						'message_text'  => '<label for="joinchat_message_text">' . esc_html__( 'Call to Action', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_text' ),
						'message_start' => '<label for="joinchat_message_start">' . esc_html__( 'Button Text', 'creame-whatsapp-me' ) . '</label>',
						'color'         => esc_html__( 'Theme Color', 'creame-whatsapp-me' ),
						'dark_mode'     => esc_html__( 'Dark Mode', 'creame-whatsapp-me' ),
						'header'        => esc_html__( 'Header', 'creame-whatsapp-me' ),
					),
					'optin'         => array(
						'optin_text'  => esc_html__( 'Opt-in Text', 'creame-whatsapp-me' ),
						'optin_check' => esc_html__( 'Opt-in Required', 'creame-whatsapp-me' ),
					),
					'chat_open'     => array(
						'message_delay' => '<label for="joinchat_message_delay">' . esc_html__( 'Chat Delay', 'creame-whatsapp-me' ) . '</label>',
						'message_views' => '<label for="joinchat_message_views">' . esc_html__( 'Page Views', 'creame-whatsapp-me' ) . '</label>',
						'message_badge' => esc_html__( 'Notification Balloon', 'creame-whatsapp-me' ),
					),
					'chat_open_end' => array(), // Close wrapper for chat_open settings.
				);
				break;

			case 'visibility':
				$sections = array(
					'global' => array(
						'view__all' => array(
							'label'    => esc_html__( 'Global', 'creame-whatsapp-me' ),
							'callback' => array( $this, 'field_view_all' ),
						),
					),
					'wp'     => array(
						'view__front_page' => esc_html__( 'Front Page', 'creame-whatsapp-me' ),
						'view__blog_page'  => esc_html__( 'Blog Page', 'creame-whatsapp-me' ),
						'view__404_page'   => esc_html__( '404 Page', 'creame-whatsapp-me' ),
						'view__search'     => esc_html__( 'Search Results', 'creame-whatsapp-me' ),
						'view__archive'    => esc_html__( 'Archives', 'creame-whatsapp-me' ),
						'view__date'       => '— ' . esc_html__( 'Date Archives', 'creame-whatsapp-me' ),
						'view__author'     => '— ' . esc_html__( 'Author Archives', 'creame-whatsapp-me' ),
						'view__singular'   => esc_html__( 'Singular', 'creame-whatsapp-me' ),
						'view__page'       => '— ' . esc_html__( 'Page', 'creame-whatsapp-me' ),
						'view__post'       => '— ' . esc_html__( 'Post', 'creame-whatsapp-me' ),
					),
				);

				// If isn't set Blog Page or is the same than Front Page unset blog_page option.
				if ( get_option( 'show_on_front' ) === 'posts' || get_option( 'page_for_posts' ) === 0 ) {
					unset( $sections['wp']['view__blog_page'] );
				}

				// Custom Post Types.
				$custom_post_types = jc_common()->get_custom_post_types();

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

				$sections['global_end'] = array(); // Close wrapper for visibility.
				break;

			case 'advanced':
				$sections = array(
					'global' => array(
						'gads'       => '<label for="joinchat_gads">' . esc_html__( 'Google Ads Conversion', 'creame-whatsapp-me' ) . '</label>',
						'custom_css' => esc_html__( 'Custom CSS', 'creame-whatsapp-me' ),
						'clear'      => esc_html__( 'Clear on uninstall', 'creame-whatsapp-me' ),
					),
				);
				break;

			default:
				$sections = array();
		}

		// Filter tab sections to add, remove or edit sections or fields.
		return apply_filters( "joinchat_tab_{$tab}_sections", $sections );

	}

	/**
	 * Tab open HTML output
	 *
	 * @since    4.5.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function settings_tab_open( $args ) {

		$tab_id     = str_replace( array( 'joinchat_tab_', '_open' ), '', $args['id'] );
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ), true ) ? wp_unslash( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification

		printf(
			'<div id="joinchat_tab_%1$s" class="joinchat-tab %2$s" role="tabpanel" aria-labelledby="navtab_%1$s">',
			esc_attr( $tab_id ),
			esc_attr( $active_tab === $tab_id ? 'joinchat-tab-active' : '' )
		);

	}

	/**
	 * Tab close HTML output
	 *
	 * @since    4.5.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function settings_tab_close( $args ) {

		echo '</div>';

	}

	/**
	 * Section HTML output
	 *
	 * @since    4.5.0
	 * @param    array $args       Section info.
	 * @return   void
	 */
	public function section_output( $args ) {
		$section_id = $args['id'];

		switch ( $section_id ) {
			case 'joinchat_tab_general__button':
				$output = '<h2 class="title">' . esc_html__( 'Button', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'Set the contact number and the appearance of the WhatsApp button.', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_general__chat':
				$output = '<hr><h2 class="title">' . esc_html__( 'Chat Window', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						esc_html__( 'If you define a "Call to Action" a window will be displayed simulating a chat before launching WhatsApp.', 'creame-whatsapp-me' ) . ' ' .
						esc_html__( 'You can introduce yourself, offer help or even make promotions to your users.', 'creame-whatsapp-me' ) .
					'</p>';
				break;

			case 'joinchat_tab_general__chat_open':
				$output = '<div class="joinchat__chat_open__wrapper">' .
					'<h2 class="title">' . esc_html__( 'Show automatically', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						esc_html__( 'If a "Call to Action" is defined, the Chat Window can be displayed automatically to capture the user\'s attention.', 'creame-whatsapp-me' ) .
						' <a class="joinchat-show-help" href="#tab-link-triggers" title="' . esc_html__( 'Show Help', 'creame-whatsapp-me' ) . '">?</a>' .
					'</p>';
				break;

			case 'joinchat_tab_general__chat_open_end':
				$output = '</div><!-- .joinchat__chat_open__wrapper -->';
				break;

			case 'joinchat_tab_visibility__global':
				$output = '<div class="joinchat__visibility__wrapper">' .
					'<h2 class="title">' . esc_html__( 'Visibility Settings', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'From here you can configure on which pages the WhatsApp button will be visible.', 'creame-whatsapp-me' ) .
					' <a href="#" class="joinchat_view_reset">' . esc_html__( 'Restore default visibility', 'creame-whatsapp-me' ) . '</a></p>';
				break;

			case 'joinchat_tab_visibility__wp':
				$output = '<hr>';
				break;

			case 'joinchat_tab_visibility__cpt':
				$output = '<h2 class="title">' . esc_html__( 'Custom Post Types', 'creame-whatsapp-me' ) . '</h2>';
				break;

			case 'joinchat_tab_visibility__global_end':
				$output = '</div><!-- .joinchat__visibility__wrapper -->';
				break;

			case 'joinchat_tab_advanced__global':
				$output = '<h2 class="title">' . esc_html__( 'Advanced Settings', 'creame-whatsapp-me' ) . '</h2>';
				break;

			default:
				$output = '';
		}

		// Filter section opening ouput.
		echo apply_filters( 'joinchat_section_output', $output, $section_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Field HTML output
	 *
	 * @since    4.5.0
	 * @param  mixed $field_id The field string id.
	 * @return void
	 */
	public function field_output( $field_id ) {

		if ( strpos( $field_id, 'view__' ) === 0 ) {
			$field = substr( $field_id, 6 );
			$value = isset( jc_common()->settings['visibility'][ $field ] ) ? jc_common()->settings['visibility'][ $field ] : '';

			$output = '<label><input type="radio" name="joinchat[view][' . $field . ']" value="yes"' . checked( 'yes', $value, false ) . '> ' .
				'<span class="dashicons dashicons-visibility" title="' . esc_attr__( 'Show', 'creame-whatsapp-me' ) . '"></span></label>' .
				'<label><input type="radio" name="joinchat[view][' . $field . ']" value="no"' . checked( 'no', $value, false ) . '> ' .
				'<span class="dashicons dashicons-hidden" title="' . esc_attr__( 'Hide', 'creame-whatsapp-me' ) . '"></span></label>' .
				'<label><input type="radio" name="joinchat[view][' . $field . ']" value=""' . checked( '', $value, false ) . '> ' .
				esc_html__( 'Inherit', 'creame-whatsapp-me' ) . ' <span class="dashicons dashicons-visibility view_inheritance_' . $field . '"></span></label>';

		} else {

			$value = isset( jc_common()->settings[ $field_id ] ) ? jc_common()->settings[ $field_id ] : '';

			switch ( $field_id ) {
				case 'telephone':
					$output = '<input id="joinchat_phone" ' . ( jc_common()->get_intltel() ? 'data-' : '' ) . 'name="joinchat[telephone]" value="' . esc_attr( $value ) . '" type="text" style="width:15em;display:inline-block"> ' .
						'<input id="joinchat_phone_test" type="button" value="' . esc_attr__( 'Test Number', 'creame-whatsapp-me' ) . '" class="button" ' . ( empty( $value ) ? 'disabled' : '' ) . '>' .
						'<p class="description">' . esc_html__( "Contact WhatsApp number <strong>(the button will not be shown if it's empty)</strong>", 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'mobile_only':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Mobile Only', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_mobile_only" name="joinchat[mobile_only]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'Only display the button on mobile devices', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'position':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Position on Screen', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[position]" value="left" type="radio"' . checked( 'left', $value, false ) . '> ' .
						esc_html__( 'Left', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[position]" value="right" type="radio"' . checked( 'right', $value, false ) . '> ' .
						esc_html__( 'Right', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'button_image':
					$thumb = Joinchat_Util::thumb( $value, 116, 116 );
					$image = is_array( $thumb ) ? $thumb['url'] : false;

					$output = '<div id="joinchat_button_image_wrapper">' .
						'<div id="joinchat_button_image_holder" ' . ( $image ? "style=\"background-size:cover; background-image:url('$image');\"" : '' ) . '></div>' .
						'<input id="joinchat_button_image" name="joinchat[button_image]" type="hidden" value="' . intval( $value ) . '">' .
						'<input id="joinchat_button_image_add" type="button" value="' . esc_attr__( 'Select an image', 'creame-whatsapp-me' ) . '" class="button-primary" ' .
						'data-title="' . esc_attr__( 'Select button image', 'creame-whatsapp-me' ) . '" data-button="' . esc_attr__( 'Use image', 'creame-whatsapp-me' ) . '"> ' .
						'<input id="joinchat_button_image_remove" type="button" value="' . esc_attr__( 'Remove', 'creame-whatsapp-me' ) . '" class="button-secondary' . ( $image ? '' : ' joinchat-hidden' ) . '">' .
						'<p class="description">' . esc_html__( 'The image will alternate with button icon', 'creame-whatsapp-me' ) . '</p></div>';
					break;

				case 'button_tip':
					$output = '<input id="joinchat_button_tip" name="joinchat[button_tip]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( '💬 Need help?', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . esc_html__( 'Short text shown next to button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'button_delay':
					$output = '<input id="joinchat_button_delay" name="joinchat[button_delay]" value="' . intval( $value ) . '" type="number" min="-1" max="120" style="width:5em"> ' .
						esc_html__( 'seconds', 'creame-whatsapp-me' ) . ' (' . esc_html__( '-1 to display directly without animation', 'creame-whatsapp-me' ) . ')' .
						'<p class="description">' . esc_html__( 'Time since the page is opened until the button is displayed', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'whatsapp_web':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'WhatsApp Web', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_whatsapp_web" name="joinchat[whatsapp_web]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						wp_kses( __( 'Open <em>WhatsApp Web</em> directly on desktop', 'creame-whatsapp-me' ), array( 'em' => array() ) ) . '</label></fieldset>';
					break;

				case 'qr':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'QR Code', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_qr" name="joinchat[qr]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'Display QR code on desktop to scan with phone', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'message_text':
					$output = '<textarea id="joinchat_message_text" name="joinchat[message_text]" rows="4" class="regular-text autofill" placeholder="' . esc_attr__( "Hello 👋\nCan we help you?", 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . esc_html__( 'Define a text to encourage users to contact by WhatsApp', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_send':
					$output = '<textarea id="joinchat_message_send" name="joinchat[message_send]" rows="3" class="regular-text autofill" placeholder="' . esc_attr__( 'Hi *{SITE}*! I need more info about {TITLE} {URL}', 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . esc_html__( 'Predefined text for the first message the user will send you', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_start':
					$output = '<input id="joinchat_message_start" name="joinchat[message_start]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( 'Open chat', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . esc_html__( 'Text to open chat on Chat Window button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_delay':
					$output = '<input id="joinchat_message_delay" name="joinchat[message_delay]" value="' . intval( $value ) . '" type="number" min="0" max="120" style="width:5em"> ' .
					esc_html__( 'seconds', 'creame-whatsapp-me' ) . ' (' . esc_html__( '0 to disable', 'creame-whatsapp-me' ) . ')' .
					'<p class="description">' . esc_html__( 'Chat Window auto displays after delay', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_views':
					$output = '<input id="joinchat_message_views" name="joinchat[message_views]" value="' . intval( $value ) . '" type="number" min="1" max="120" style="width:5em"> ' .
						'<p class="description">' . esc_html__( 'Chat Window auto displays from this number of page views', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_badge':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Notification Balloon', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_message_badge" name="joinchat[message_badge]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'Display a notification balloon instead of opening the Chat Window for a "less intrusive" mode', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'color':
					$output = '<input id="joinchat_color" name="joinchat[color]" value="' . esc_attr( $value ) . '" type="text" data-default-color="#25d366"> ';
					break;

				case 'dark_mode':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Dark Mode', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[dark_mode]" value="no" type="radio"' . checked( 'no', $value, false ) . '> ' .
						esc_html__( 'No', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[dark_mode]" value="yes" type="radio"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'Yes', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[dark_mode]" value="auto" type="radio"' . checked( 'auto', $value, false ) . '> ' .
						esc_html__( 'Auto (detects device dark mode)', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'header':
					$check = in_array( $value, array( '__jc__', '__wa__' ), true ) ? $value : '__custom__';
					$value = '__custom__' === $check ? $value : '';

					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Header', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[header]" value="__jc__" type="radio"' . checked( '__jc__', $check, false ) . '> ' .
						esc_html__( 'Powered by Joinchat', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[header]" value="__wa__" type="radio"' . checked( '__wa__', $check, false ) . '> ' .
						esc_html__( 'WhatsApp Logo', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[header]" value="__custom__" type="radio"' . checked( '__custom__', $check, false ) . '> ' .
						esc_html__( 'Custom:', 'creame-whatsapp-me' ) . '</label> ' .
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

					ob_start();
					wp_editor( $value, 'joinchat_optin_text', $editor_settings );
					$editor_output = ob_get_clean();

					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Opt-in Text', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<p><label for="joinchat_optin_text">' . esc_html__( 'Opt-in is a users’ consent to receive messages from a business.', 'creame-whatsapp-me' ) . ' ' .
						esc_html__( "Here you can include legal text about how you will use the user's contact and the conditions they accept, or other important information.", 'creame-whatsapp-me' ) . '</label></p>' .
						$editor_output .
						'</fieldset>';
					break;

				case 'optin_check':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Opt-in Required', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_optin_check" name="joinchat[optin_check]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'User approval is required to enable the contact button', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				case 'gads':
					$parts = $value ? explode( '/', str_replace( 'AW-', '', $value ) ) : array( '', '' );

					$output = '<label class="joinchat-gads">AW-' .
						'<input id="joinchat_gads" name="joinchat[gads][]" value="' . esc_attr( $parts[0] ) . '" type="text" maxlength="11" style="width:7.5em;" placeholder="99999999999" title="' . esc_attr__( 'Conversion ID', 'creame-whatsapp-me' ) . '">/ ' .
						'<input name="joinchat[gads][]" value="' . esc_attr( $parts[1] ) . '" type="text" maxlength="20" style="width:13em;" placeholder="ABCDEFGHIJ0123456789" title="' . esc_attr__( 'Conversion label', 'creame-whatsapp-me' ) . '"> ' .
						'</label> <span style="white-space:nowrap">AW-<em>CONVERSION_ID</em>/<em>CONVERSION_LABEL</em></span>' .
						'<p class="description">' . esc_html__( 'Send the conversion automatically at the chat start', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'custom_css':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Custom CSS', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<p><label for="joinchat_custom_css">' . esc_html__( 'Add your own CSS code here to customize the appearance of Joinchat.', 'creame-whatsapp-me' ) . ' ' .
						'<a href="#" class="joinchat_custom_css_prefill">' . esc_html__( 'Fill with example code', 'creame-whatsapp-me' ) . '</a>' .
						'</label></p>' .
						'<textarea id="joinchat_custom_css" name="joinchat[custom_css]" rows="3" class="regular-text autofill" placeholder="' . esc_attr__( 'Your custom CSS...', 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' .
						sprintf( /* translators: %s: CSS tricks link. */
							wp_kses( __( 'You can find examples and more tricks <a href="%s" target="_blank">here</a>.', 'creame-whatsapp-me' ), Joinchat_Admin::KSES_LINK ),
							esc_url( Joinchat_Util::link( 'css', 'help' ) )
						) . '</p></fieldset>';
					break;

				case 'clear':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Clear on uninstall', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input id="joinchat_clear" name="joinchat[clear]" value="yes" type="checkbox"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'All Joinchat settings will be removed', 'creame-whatsapp-me' ) . '</label></fieldset>';
					break;

				default:
					$output = '';
			}
		}

		// Filter field ouput.
		echo apply_filters( 'joinchat_field_output', $output, $field_id, jc_common()->settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Field 'field_view_all' output
	 *
	 * @since    4.5.0
	 * @return   void
	 */
	public function field_view_all() {

		$value = ( isset( jc_common()->settings['visibility']['all'] ) && 'no' === jc_common()->settings['visibility']['all'] ) ? 'no' : 'yes';

		$inheritance = apply_filters(
			'joinchat_visibility_inheritance',
			array(
				'all'      => array( 'front_page', 'blog_page', '404_page', 'search', 'archive', 'singular', 'cpts' ),
				'archive'  => array( 'date', 'author' ),
				'singular' => array( 'page', 'post' ),
			)
		);

		echo '<div class="joinchat_view_all" data-inheritance="' . esc_attr( wp_json_encode( $inheritance ) ) . '">' .
			'<label><input type="radio" name="joinchat[view][all]" value="yes"' . checked( 'yes', $value, false ) . '> ' .
			'<span class="dashicons dashicons-visibility" title="' . esc_attr__( 'Show', 'creame-whatsapp-me' ) . '"></span></label>' .
			'<label><input type="radio" name="joinchat[view][all]" value="no"' . checked( 'no', $value, false ) . '> ' .
			'<span class="dashicons dashicons-hidden" title="' . esc_attr__( 'Hide', 'creame-whatsapp-me' ) . '"></span></label></div>';

	}

	/**
	 * Add a help tab to the options page in the WordPress admin
	 *
	 * @since    4.5.0
	 * @access   public
	 * @return   void
	 */
	public function help_tab() {

		$help_tabs = array(
			array(
				'id'      => 'styles-and-vars',
				'title'   => esc_html__( 'Styles and Variables', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . wp_kses(
						__( 'You can use formatting styles like in WhatsApp: _<em>italic</em>_ *<strong>bold</strong>* ~<del>strikethrough</del>~.', 'creame-whatsapp-me' ),
						array(
							'em'     => array(),
							'strong' => array(),
							'del'    => array(),
						)
					) . '</p>' .
					'<p>' . esc_html__( 'You can use dynamic variables that will be replaced by the values of the page the user visits:', 'creame-whatsapp-me' ) .
					'<p>' .
					'<span><code>{SITE}</code> ➜ ' . esc_html( get_bloginfo( 'name', 'display' ) ) . '</span><br> ' .
					'<span><code>{TITLE}</code> ➜ ' . esc_html__( 'Page Title', 'creame-whatsapp-me' ) . '</span><br>' .
					'<span><code>{URL}</code> ➜ ' . esc_url( home_url( 'awesome/' ) ) . '</span><br> ' .
					'<span><code>{HREF}</code> ➜ ' . esc_url( home_url( 'awesome/' ) ) . '?utm_source=twitter&utm_medium=social&utm_campaign=XXX</span> ' .
					'</p>',
			),
			array(
				'id'      => 'triggers',
				'title'   => esc_html__( 'Triggers', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . esc_html__( 'Any element in your pages can be a chat trigger:', 'creame-whatsapp-me' ) . '</p>' .
					'<p><strong>' . esc_html__( 'On page load, by url:', 'creame-whatsapp-me' ) . '</strong></p>' .
					'<ul>' .
						'<li>url query param <code>joinchat</code> ' . esc_html__( 'to show chat window.', 'creame-whatsapp-me' ) . ' ' . esc_html__( 'Can set delay in seconds, default is 0.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>example.com/page/?joinchat=5</code></em></li>' .
						'<li>url query hash <code>#joinchat</code> ' . esc_html__( 'to show chat window.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>example.com/page/#joinchat</code></em></li>' .
					'</ul>' .
					'<p><strong>' . esc_html__( 'On click:', 'creame-whatsapp-me' ) . '</strong></p>' .
					'<p>' . sprintf(
						/* translators: 1: attribute phone, 2: attribute message. */
						wp_kses( __( 'You can set <strong>custom phone and initial message</strong> for direct WhatsApp triggers with attributes %1$s and %2$s.', 'creame-whatsapp-me' ), array( 'strong' => array() ) ),
						'<code>data-phone</code>',
						'<code>data-message</code>'
					) . '</p>' .
					'<ul>' .
						'<li>class <code>joinchat_app</code> ' . esc_html__( 'to open WhatsApp directly.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;img src="contact.jpg" class="joinchat_app" alt="' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '"&gt;</code></em></li>' .
						'<li>class <code>joinchat_open</code> ' . esc_html__( 'to show chat window (or open WhatsApp if there is no CTA).', 'creame-whatsapp-me' ) . '</li>' .
						'<li>href <code>#whatsapp</code> ' . esc_html__( 'to open WhatsApp directly.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;a href="#whatsapp" data-phone="99999999"&gt;' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '&lt;/a&gt;</code></em></li>' .
						'<li>href <code>#joinchat</code> ' . esc_html__( 'to show chat window (or open WhatsApp if there is no CTA).', 'creame-whatsapp-me' ) . '</li>' .
					'</ul>' .
					'<p><strong>' . esc_html__( 'On scroll (when element appears on screen):', 'creame-whatsapp-me' ) . '</strong></p>' .
					'<ul>' .
						'<li>class <code>joinchat_show</code> ' . esc_html__( 'only show if it\'s an not seen CTA.', 'creame-whatsapp-me' ) . '</li>' .
						'<li>class <code>joinchat_force_show</code> ' . esc_html__( 'to show always.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;section class="joinchat_force_show"&gt;…</code></em></li>' .
					'</ul>',
			),
			array(
				'id'       => 'support',
				'title'    => esc_html__( 'Help & Support', 'creame-whatsapp-me' ),
				'priority' => 100, // At the end.
				'content'  =>
					'<p>' .
					sprintf(
						/* translators: 1: docs url, 2: wordpress.org plugin support url. */
						wp_kses( __( 'If you need help, first check the <a href="%1$s" target="_blank">documentation</a> and if you don\'t find the solution you can consult the <a href="%2$s" target="_blank">plugin\'s free support forum</a>.', 'creame-whatsapp-me' ), Joinchat_Admin::KSES_LINK ),
						esc_url( Joinchat_Util::link( 'docs', 'helptab' ) ),
						esc_url( 'https://wordpress.org/support/plugin/creame-whatsapp-me/' )
					) . ' ' .
					sprintf(
						/* translators: %s: premium url. */
						wp_kses( __( 'You can also buy <strong><a href="%s" target="_blank">Joinchat Premium</a></strong> with one year of support service included.', 'creame-whatsapp-me' ), array( 'strong' => array() ) + Joinchat_Admin::KSES_LINK ),
						esc_url( Joinchat_Util::link( 'premium', 'helptab' ) )
					) . '</p>' .
					'<p>' . esc_html__( 'If you like Joinchat 😍', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
					'<li>' . sprintf(
						/* translators: %s: Add review link. */
						esc_html__( "Please leave us a %s rating. We'll thank you.", 'creame-whatsapp-me' ),
						'<a href="https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post" target="_blank">★★★★★</a>'
					) . '</li>' .
					'<li>' . sprintf(
						/* translators: %s: Joinchat page link. */
						esc_html__( 'Subscribe to our newsletter and visit our blog at %s.', 'creame-whatsapp-me' ),
						'<a href="' . esc_url( Joinchat_Util::link( '', 'helptab' ) ) . '" target="_blank">join.chat</a>'
					) . '</li>' .
					'<li>' . sprintf(
						/* translators: %s: Joinchat twitter link. */
						esc_html__( 'Follow %s on twitter.', 'creame-whatsapp-me' ),
						'<a href="https://twitter.com/joinchatnow" target="_blank">@joinchatnow</a>'
					) . '</li>' .
					'</ul>' .
					'<p>' . esc_html__( 'If you need to access the setup wizard again, please click on the button below:', 'creame-whatsapp-me' ) . '</p>' .
					'<p><a href="' . esc_url( Joinchat_Util::admin_url( 'joinchat-onboard' ) ) . '" class="button button-primary">' . esc_html__( 'Welcome to Joinchat', 'creame-whatsapp-me' ) . '</a></p>',
			),
		);

		$screen = get_current_screen();

		foreach ( $help_tabs as $tab_data ) {
			$tab_id = str_replace( '-', '_', $tab_data['id'] );
			$screen->add_help_tab( apply_filters( "joinchat_help_tab_{$tab_id}", $tab_data ) );
		}

	}

	/**
	 * Generate the options page in the WordPress admin
	 *
	 * @since    4.5.0
	 * @access   public
	 * @return   void
	 */
	public function options_page() {

		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ), true ) ? wp_unslash( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		$prev_satus = in_array( $active_tab, array( 'general', 'advanced' ), true ) ? 'button' : 'button disabled';
		?>
			<div class="wrap">
				<div class="wp-header-end"></div>

				<?php
				if ( ! Joinchat_Util::options_submenu() ) {
					settings_errors();
				}
				?>

				<form method="post" id="joinchat_form" action="options.php" autocomplete="off">
					<?php settings_fields( JOINCHAT_SLUG ); ?>
					<h2 class="nav-tab-wrapper wp-clearfix" role="tablist">
						<?php
						foreach ( $this->tabs as $tab => $name ) {
							$link = $active_tab === $tab
								? '<a id="navtab_%1$s" href="#joinchat_tab_%1$s" class="nav-tab nav-tab-active" role="tab" aria-controls="joinchat_tab_%1$s" aria-selected="true">%2$s</a>'
								: '<a id="navtab_%1$s" href="#joinchat_tab_%1$s" class="nav-tab" role="tab" aria-controls="joinchat_tab_%1$s" aria-selected="false">%2$s</a>';

							printf( $link, esc_attr( $tab ), esc_html( $name ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</h2>
					<div class="joinchat_preview_control"><a id="joinchat_preview_show" href="#" class="<?php echo esc_attr( $prev_satus ); ?> dashicons-before"><?php esc_html_e( 'Preview', 'creame-whatsapp-me' ); ?></a></div>
					<div class="joinchat-tabs">
						<?php do_settings_sections( JOINCHAT_SLUG ); ?>
					</div><!-- end tabs -->
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Return html for dynamic variables help next to field label
	 *
	 * @since    4.5.0
	 * @access   public
	 * @param    string $field       field name.
	 * @return   string
	 */
	public static function vars_help( $field ) {

		$vars = apply_filters( 'joinchat_vars_help', array( 'SITE', 'TITLE', 'URL', 'HREF' ), $field );

		return count( $vars ) ? '<div class="joinchat_vars_help">' . esc_html__( 'You can use vars', 'creame-whatsapp-me' ) . ' ' .
			'<a class="joinchat-show-help" href="#" title="' . esc_attr__( 'Show Help', 'creame-whatsapp-me' ) . '">?</a><br> ' .
			'<code>{' . join( '}</code> <code>{', $vars ) . '}</code></div>' : '';

	}

	/**
	 * Enqueue the scripts and stylesheets for the admin page.
	 *
	 * @since    5.0.0
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function enqueue_assets( $hook ) {

		$handle = 'joinchat-admin';
		$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$deps   = array( 'jquery', 'wp-color-picker' );

		// Enqueue WordPress media scripts.
		wp_enqueue_media();

		// Enqueue styles.
		wp_deregister_style( $handle );
		wp_enqueue_style( $handle, plugins_url( "css/joinchat{$min}.css", __FILE__ ), array( 'wp-color-picker' ), JOINCHAT_VERSION, 'all' );

		// Enqueue IntlTelInput assets.
		if ( jc_common()->get_intltel() ) {
			$deps[] = 'intl-tel-input';
			wp_enqueue_style( 'intl-tel-input' );
		}

		$example_css = <<<CSS
/* .joinchat some default styles
z-index: 9000;   put above or below other objects
--s: 60px;       button size
--bottom: 20px;  bottom separation (mobile 6px)
--sep: 20px;     right/left separation (mobile 6px)
--header: 70px;  chatbox header height (mobile 55px)
*/
.joinchat {
	/* your css rules */
}

/* Joinchat mobile styles */
@media (max-width: 480px), (orientation: landscape) and (max-width: 767px) {
	.joinchat {
		/* your mobile rules */
	}
}
CSS;

		// Enqueue scripts.
		$config = array(
			'home'        => home_url(),
			'example'     => esc_html__( 'is an example, double click to use it', 'creame-whatsapp-me' ),
			'example_css' => $example_css,
		);

		wp_deregister_script( $handle );
		wp_enqueue_script( $handle, plugins_url( "js/joinchat-page{$min}.js", __FILE__ ), $deps, JOINCHAT_VERSION, true );
		wp_add_inline_script( $handle, 'var joinchat_admin = ' . wp_json_encode( $config ) . ';' );

		// Enqueue Custom CSS editor.
		$editor_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		wp_add_inline_script( 'code-editor', 'var custom_css_settings = ' . wp_json_encode( $editor_settings ) . ';' );

	}

	/**
	 * Update admin title
	 *
	 * @since 5.0.0
	 * @param  string $admin_title  current title.
	 * @return string
	 */
	public static function admin_title( $admin_title ) {

		return sprintf( '%s &lsaquo; %s', esc_html__( 'Joinchat Settings', 'creame-whatsapp-me' ), get_bloginfo( 'name' ) );

	}

	/**
	 * Custom admin header with Joinchat logo
	 *
	 * @since 5.0.0
	 * @since 5.0.12 Added action 'joinchat_admin_header'.
	 * @return void
	 */
	public function admin_header() {
		?>
		<div id="jcadminbar">
			<div class="joinchat-header">
				<h1><img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . '/admin/img/joinchat.svg' ); ?>" width="159" height="40" alt="Joinchat"></h1>
				<?php do_action( 'joinchat_admin_header' ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Modifies the "Thank you" text displayed in the admin footer.
	 *
	 * @since 4.5.0
	 * @access public
	 * @param string $footer_text The content that will be printed.
	 * @return string The content that will be printed.
	 */
	public function admin_footer_text( $footer_text ) {

		return sprintf(
			/* translators: 1: Joinchat, 2: Add review link. */
			esc_html__( 'Do you like %1$s? Please help us with a %2$s rating.', 'creame-whatsapp-me' ),
			'<strong>Joinchat</strong>',
			'<a href="https://wordpress.org/support/plugin/creame-whatsapp-me/reviews/#new-post" target="_blank">★★★★★</a>'
		);

	}
}
