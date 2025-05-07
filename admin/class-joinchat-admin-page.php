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

			$page_hook = add_options_page( $title, $title . $icon, Joinchat_Util::capability(), JOINCHAT_SLUG, array( $this, 'options_page' ) );
		} else {
			$icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxNyAxNiI+PHBhdGggZmls' .
				'bD0iIzlDQTJBNyIgZD0iTTE0LjQgMTIuOGE4IDggMCAxIDAtMS42IDEuNkMxNC40IDE1LjUgMTcgMTYgMTcgMTZzLTEuNS0xLjYtMi42LTMuMiIvPjwvc3ZnPg==';

			$page_hook = add_menu_page( $title, $title, Joinchat_Util::capability(), JOINCHAT_SLUG, array( $this, 'options_page' ), $icon, 81 );
		}

		add_action("load-{$page_hook}", function() { do_action( 'load_joinchat_settings_page' ); } ); // phpcs:ignore

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

		if ( isset( $_GET['onboard'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$is_onboard = true === filter_var( wp_unslash( $_GET['onboard'] ), FILTER_VALIDATE_BOOLEAN ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
					'button' => array(
						'telephone'    => '<label for="joinchat_phone">' . esc_html__( 'Telephone', 'creame-whatsapp-me' ) . '</label>',
						'message_send' => '<label for="joinchat_message_send">' . esc_html__( 'Message', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_send' ),
						'button_ico'   => esc_html__( 'Icon', 'creame-whatsapp-me' ),
						'button_image' => esc_html__( 'Image', 'creame-whatsapp-me' ),
						'button_tip'   => '<label for="joinchat_button_tip">' . esc_html__( 'Tooltip', 'creame-whatsapp-me' ) . '</label>',
						'position'     => esc_html__( 'Position on Screen', 'creame-whatsapp-me' ),
						'button_delay' => '<label for="joinchat_button_delay">' . esc_html__( 'Button Delay', 'creame-whatsapp-me' ) . '</label>',
						'mobile_only'  => esc_html__( 'Mobile Only', 'creame-whatsapp-me' ),
						'desktop'      => array(
							'label'    => esc_html__( 'On Desktop', 'creame-whatsapp-me' ),
							'callback' => array( $this, 'field_desktop' ),
						),
					),
					'chat'   => array(
						'message_text'  => '<label for="joinchat_message_text">' . esc_html__( 'Call to Action', 'creame-whatsapp-me' ) . '</label>' . self::vars_help( 'message_text' ) . self::rich_chat_help(),
						'message_start' => '<label for="joinchat_message_start">' . esc_html__( 'Button Text', 'creame-whatsapp-me' ) . '</label>',
						'color'         => esc_html__( 'Theme Color', 'creame-whatsapp-me' ),
						'dark_mode'     => esc_html__( 'Theme Style', 'creame-whatsapp-me' ),
						'header'        => esc_html__( 'Header', 'creame-whatsapp-me' ),
						'optin'         => array(
							'label'    => esc_html__( 'Opt-in', 'creame-whatsapp-me' ),
							'callback' => array( $this, 'field_optin' ),
						),
						'auto_open'     => array(
							'label'    => esc_html__( 'Auto Open', 'creame-whatsapp-me' ),
							'callback' => array( $this, 'field_auto_open' ),
						),
					),
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

		// phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ), true ) ? $_GET['tab'] : 'general';
		$tab_id     = str_replace( array( 'joinchat_tab_', '_open' ), '', $args['id'] );

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
				$output = '<h2 class="title">' . esc_html__( 'Floating Button', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'Set the contact number and the appearance of the floating contact button.', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_general__chat':
				$output = '<hr><h2 class="title">' . esc_html__( 'Chat Window', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						esc_html__( 'If you define a "Call to Action" a pop-up simulating a chat will be displayed before initiating contact.', 'creame-whatsapp-me' ) . ' ' .
						esc_html__( 'You can introduce yourself, offer help or even make promotions to your users.', 'creame-whatsapp-me' ) .
					'</p>';
				break;

			case 'joinchat_tab_visibility__global':
				$output = '<div class="joinchat__visibility__wrapper">' .
					'<h2 class="title">' . esc_html__( 'Visibility Settings', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'From here you can configure on which pages the Floating Button will be visible.', 'creame-whatsapp-me' ) .
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
						'<p class="description">' . wp_kses( __( "WhatsApp contact number <strong>(the button will not be shown if it's empty)</strong>", 'creame-whatsapp-me' ), array( 'strong' => array() ) ) . '</p>';
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

				case 'button_ico':
					$output = '<fieldset class="joinchat__button_ico">' .
					'<label><input type="radio" name="joinchat[button_ico]" value="app"' . checked( 'app', $value, false ) . '>' .
					'<span></span></label>';

					$icons = jc_common()->get_icons();
					foreach ( $icons as $key => $icon ) {
						$output .= '<label><input type="radio" name="joinchat[button_ico]" value="' . $key . '"' . checked( $key, $value, false ) . '><span>' . $icon . '</span></label>';
					}

					$output .= '</fieldset>' .
						'<p class="description">' . esc_html__( 'Choose the main icon or a contact icon with the theme colors', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'button_image':
					$fixed = (int) $value < 0 ? 'yes' : 'no';
					$value = absint( $value );

					if ( Joinchat_Util::is_video( $value ) ) {
						$image = '<video autoplay loop muted playsinline src="' . esc_url( wp_get_attachment_url( $value ) ) . '"></video>';
					} else {
						$thumb = Joinchat_Util::thumb( $value, 116, 116 );
						$image = is_array( $thumb ) ? '<img src="' . esc_url( $thumb['url'] ) . '" width="' . (int) $thumb['width'] . '" height="' . (int) $thumb['height'] . '" alt="">' : '';
					}

					$output = '<div id="joinchat_button_image_wrapper" class="' . ( $image ? '' : 'no-image' ) . '">' .
						'<div id="joinchat_button_image_holder">' . $image . '</div>' .
						'<input id="joinchat_button_image" name="joinchat[button_image]" type="hidden" value="' . (int) $value . '">' .
						'<input id="joinchat_button_image_add" type="button" value="' . esc_attr__( 'Select an image', 'creame-whatsapp-me' ) . '" class="button-primary" ' .
						'data-title="' . esc_attr__( 'Select button image', 'creame-whatsapp-me' ) . '" data-button="' . esc_attr__( 'Use image', 'creame-whatsapp-me' ) . '"> ' .
						'<input id="joinchat_button_image_remove" type="button" value="' . esc_attr__( 'Remove', 'creame-whatsapp-me' ) . '" class="button-secondary">' .
						'<p><label><input name="joinchat[button_image_fixed]" value="no" type="radio"' . checked( 'no', $fixed, false ) . '> ' . esc_html__( 'Toggle with icon', 'creame-whatsapp-me' ) . '</label> ' .
						'<label><input name="joinchat[button_image_fixed]" value="yes" type="radio"' . checked( 'yes', $fixed, false ) . '> ' . esc_html__( 'Fixed image', 'creame-whatsapp-me' ) . '</label>' .
						'</p></div>';
					break;

				case 'button_tip':
					$output = '<input id="joinchat_button_tip" name="joinchat[button_tip]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( '💬 Need help?', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . esc_html__( 'Short text shown next to button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'button_delay':
					$output = '<input id="joinchat_button_delay" name="joinchat[button_delay]" value="' . (int) $value . '" type="number" min="-1" max="120" class="tiny-text"> ' .
						esc_html__( 'seconds', 'creame-whatsapp-me' ) . ' (' . esc_html__( '-1 to display directly without animation', 'creame-whatsapp-me' ) . ')' .
						'<p class="description">' . esc_html__( 'Time since the page is opened until the button is displayed', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_text':
					$output = '<textarea id="joinchat_message_text" name="joinchat[message_text]" rows="4" class="large-text autofill" placeholder="' . esc_attr__( "{RAND Hi||Hello} 👋, welcome to *{SITE}*\n===\nCan we help you?", 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . esc_html__( 'Define an attractive text that encourages users to contact you if they are interested or need help', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_send':
					$output = '<textarea id="joinchat_message_send" name="joinchat[message_send]" rows="3" class="regular-text autofill" placeholder="' . esc_attr__( 'Hi *{SITE}*! I need more info about {TITLE} {URL}', 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
						'<p class="description">' . esc_html__( 'Pre-filled text in the first message the user will send you', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'message_start':
					$output = '<input id="joinchat_message_start" name="joinchat[message_start]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( 'Open Chat', 'creame-whatsapp-me' ) . '"> ' .
						'<p class="description">' . esc_html__( 'Text to open chat on Chat Window button', 'creame-whatsapp-me' ) . '</p>';
					break;

				case 'color':
					list($color, $text) = explode( '/', $value . '/1' );

					$output = '<input id="joinchat_color" name="joinchat[color][bg]" value="' . esc_attr( $color ) . '" type="text" data-default-color="#25d366"> ' .
						'<div class="button-group joinchat_color_text">' .
						'<label class="button white" title="' . esc_attr__( 'White Text', 'creame-whatsapp-me' ) . '"><input class="ui-helper-hidden-accessible" name="joinchat[color][text]" type="radio" value="1"' . checked( '1', $text, false ) . '><span class="screen-reader-text">' . esc_html__( 'White Text', 'creame-whatsapp-me' ) . '</span></label>' .
						'<label class="button black" title="' . esc_attr__( 'Black Text', 'creame-whatsapp-me' ) . '"><input class="ui-helper-hidden-accessible" name="joinchat[color][text]" type="radio" value="0"' . checked( '0', $text, false ) . '><span class="screen-reader-text">' . esc_html__( 'Black Text', 'creame-whatsapp-me' ) . '</span></label>' .
						'</div>';
					break;

				case 'dark_mode':
					$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Dark Mode', 'creame-whatsapp-me' ) . '</span></legend>' .
						'<label><input name="joinchat[dark_mode]" value="no" type="radio"' . checked( 'no', $value, false ) . '> ' .
						esc_html__( 'Light', 'creame-whatsapp-me' ) . '</label><br>' .
						'<label><input name="joinchat[dark_mode]" value="yes" type="radio"' . checked( 'yes', $value, false ) . '> ' .
						esc_html__( 'Dark', 'creame-whatsapp-me' ) . '</label><br>' .
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
						'<textarea id="joinchat_custom_css" name="joinchat[custom_css]" rows="3" class="large-text autofill" placeholder="' . esc_attr__( 'Your custom CSS...', 'creame-whatsapp-me' ) . '">' . esc_textarea( $value ) . '</textarea>' .
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
	 * Field 'desktop' output
	 *
	 * @since    5.1.0
	 * @return void
	 */
	public function field_desktop() {

		$qr = jc_common()->settings['qr'];
		$ww = jc_common()->settings['whatsapp_web'];

		$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'On Desktop', 'creame-whatsapp-me' ) . '</span></legend>' .
			'<label><input id="joinchat_qr" name="joinchat[qr]" value="yes" type="checkbox"' . checked( 'yes', $qr, false ) . '> ' .
			esc_html__( 'Display QR code to scan with phone', 'creame-whatsapp-me' ) . '</label><br>' .
			'<label><input id="joinchat_whatsapp_web" name="joinchat[whatsapp_web]" value="yes" type="checkbox"' . checked( 'yes', $ww, false ) . '> ' .
			wp_kses( __( 'Open directly <em>WhatsApp Web</em>', 'creame-whatsapp-me' ), array( 'em' => array() ) ) . '</label></fieldset>';

		echo apply_filters( 'joinchat_field_output', $output, 'desktop', jc_common()->settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Field 'optin' output
	 *
	 * @since    5.1.0
	 * @return void
	 */
	public function field_optin() {

		$text  = jc_common()->settings['optin_text'];
		$check = jc_common()->settings['optin_check'];

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
		wp_editor( $text, 'joinchat_optin_text', $editor_settings );
		$editor_output = ob_get_clean();

		$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Opt-in Text', 'creame-whatsapp-me' ) . '</span></legend>' .
			'<p><label for="joinchat_optin_text">' . esc_html__( 'Opt-in is a users’ consent to receive messages from a business.', 'creame-whatsapp-me' ) . ' ' .
			esc_html__( "Here you can include legal text about how you will use the user's contact and the conditions they accept, or other important information.", 'creame-whatsapp-me' ) . '</label></p>' .
			$editor_output .
			'<label><input id="joinchat_optin_check" name="joinchat[optin_check]" value="yes" type="checkbox"' . checked( 'yes', $check, false ) . '> ' .
			esc_html__( 'User approval is required to enable the contact button', 'creame-whatsapp-me' ) . '</label>' .
			'</fieldset>';

		echo apply_filters( 'joinchat_field_output', $output, 'optin', jc_common()->settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Field 'auto_open' output
	 *
	 * @since    5.1.0
	 * @since    5.2.0 renamed from 'field_show_auto' to 'field_auto_open'
	 *
	 * @return void
	 */
	public function field_auto_open() {

		$delay = jc_common()->settings['message_delay'];
		$pages = jc_common()->settings['message_views'];
		$badge = jc_common()->settings['message_badge'];

		$output = '<fieldset><legend class="screen-reader-text"><span>' . esc_html__( 'Auto Open', 'creame-whatsapp-me' ) . '</span></legend>' .
			'<label><input id="joinchat_message_delay_on" name="joinchat[message_delay_on]" value="yes" type="checkbox"' . checked( 'yes', $delay > 0 ? 'yes' : '', false ) . '> ' . esc_html__( 'Automatically show Chat Window', 'creame-whatsapp-me' ) . '</label> ' .
			/* translators: %s: input for seconds delay */
			'<label>' . sprintf( esc_html__( 'after %s seconds', 'creame-whatsapp-me' ), '<input id="joinchat_message_delay" name="joinchat[message_delay]" value="' . absint( $delay ) . '" type="number" min="1" max="120" class="tiny-text">' ) . '</label> ' .
			/* translators: %s: input for number of pages */
			'<label>' . sprintf( esc_html__( 'and user has visited at least %s pages', 'creame-whatsapp-me' ), '<input id="joinchat_message_views" name="joinchat[message_views]" value="' . (int) $pages . '" type="number" min="1" max="120" class="tiny-text">' ) . '</label><br>' .
			'<label><input id="joinchat_message_badge" name="joinchat[message_badge]" value="yes" type="checkbox"' . checked( 'yes', $badge, false ) . '> ' .
			esc_html__( 'Display a notification bubble instead of opening the Chat Window for a "less intrusive" mode', 'creame-whatsapp-me' ) . '</label></fieldset>' .
			'<p class="description">' . esc_html__( 'Only if "Call to Action" is defined.', 'creame-whatsapp-me' ) . ' ' .
			esc_html__( 'You can also use other triggers to show Chat Window', 'creame-whatsapp-me' ) . ' ' .
			' <a class="joinchat-show-help" href="#tab-link-triggers" title="' . esc_html__( 'Show Help', 'creame-whatsapp-me' ) . '">?</a>' .
			'<br><small class="joinchat-cookies-notice">' . esc_html__( 'This feature requires the use of cookies', 'creame-whatsapp-me' ) . ' ' .
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url_raw( admin_url( 'options-privacy.php?tab=policyguide' ) ), esc_html__( 'Privacy Policy Guide' ) ) . '</small></p>'; // phpcs:ignore WordPress.WP.I18n.MissingArgDomain

		echo apply_filters( 'joinchat_field_output', $output, 'auto_open', jc_common()->settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
				'id'      => 'dynamic-vars',
				'title'   => esc_html__( 'Dynamic Variables', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . esc_html__( 'You can use dynamic variables that will be replaced by the values of the page the user visits:', 'creame-whatsapp-me' ) .
					'<p style="line-height:1.8em;">' .
						'<span><code>{SITE}</code> ➜ ' . esc_html( get_bloginfo( 'name', 'display' ) ) . '</span><br> ' .
						'<span><code>{TITLE}</code> ➜ ' . esc_html__( 'Page Title', 'creame-whatsapp-me' ) . '</span><br>' .
						'<span><code>{HOME}</code> ➜ ' . esc_url( home_url() ) . '</span><br> ' .
						'<span><code>{URL}</code> ➜ ' . esc_url( user_trailingslashit( home_url( 'awesome' ) ) ) . '</span><br> ' .
						'<span><code>{HREF}</code> ➜ ' . esc_url( user_trailingslashit( home_url( 'awesome' ) ) ) . '?utm_source=twitter&utm_medium=social&utm_campaign=XXX</span>' .
					'</p>',
			),
			array(
				'id'      => 'rich-chat',
				'title'   => esc_html__( 'Rich Chat', 'creame-whatsapp-me' ),
				'content' =>
				'<p>' . esc_html__( 'Enhance your Calls to Action with a rich chat.', 'creame-whatsapp-me' ) . ' ' . esc_html__( 'Add multiple chat bubbles, links, images, and more to improve engagement.', 'creame-whatsapp-me' ) . '</p>' .
				'<p><strong>📌 ' . esc_html__( 'Text Formatting', 'creame-whatsapp-me' ) . '</strong></p>' .
				'<p style="line-height:1.8em;">' .
					'<span><em>' . esc_html__( 'Italic', 'creame-whatsapp-me' ) . '</em> ➜ <code>_text_</code></span><br>' .
					'<span><strong>' . esc_html__( 'Bold', 'creame-whatsapp-me' ) . '</strong> ➜ <code>*text*</code> ' .
						esc_html__( 'or', 'creame-whatsapp-me' ) . ' <code>**text**</code> ' .
						esc_html__( 'or', 'creame-whatsapp-me' ) . ' <code>__text__</code></span><br>' .
					'<span><del>' . esc_html__( 'Strikethrough', 'creame-whatsapp-me' ) . '</del> ➜ <code>~text~</code></span><br>' .
					'<span><code>' . esc_html__( 'Monospaced', 'creame-whatsapp-me' ) . '</code> ➜ <code>`text`</code></span>' .
				'</p>' .
				'<p><strong>💬 ' . esc_html__( 'Message Structure', 'creame-whatsapp-me' ) . '</strong></p>' .
				'<p style="line-height:1.8em;">' .
					/* translators: %s: the code. */
					'<span>' . sprintf( esc_html__( 'Split text into bubbles using %s', 'creame-whatsapp-me' ), '<code>===</code>' ) . '</span><br>' .
					/* translators: %s: the code. */
					'<span>' . sprintf( esc_html__( 'Add notes outside the chat with %s', 'creame-whatsapp-me' ), '<code>&gt;&gt;&gt;</code>' ) . '</span>' .
				'</p>' .
				'<p><strong>🔗 ' . esc_html__( 'Links, Images and More', 'creame-whatsapp-me' ) . '</strong> (' . esc_html__( 'Markdown supported', 'creame-whatsapp-me' ) . ')</p>' .
				'<p style="line-height:1.8em;">' .
					'<span><strong>' . esc_html__( 'Link', 'creame-whatsapp-me' ) . '</strong> ➜ <code>[title](https://www.example.com)</code> ' .
						esc_html__( 'or', 'creame-whatsapp-me' ) . ' <code>{LINK https://www.example.com title}</code></span><br>' .
					'<span><strong>' . esc_html__( 'Button', 'creame-whatsapp-me' ) . '</strong> ➜ <code>{BTN https://www.example.com title}</code></span><br>' .
					'<span><strong>' . esc_html__( 'Image', 'creame-whatsapp-me' ) . '</strong> ➜ <code>![alt_text](image.jpg width)</code> ' .
						esc_html__( 'or', 'creame-whatsapp-me' ) . ' <code>{IMG image.jpg width alt_text}</code></span><br>' .
					'<span><strong>' . esc_html__( 'Horizontal Rule', 'creame-whatsapp-me' ) . '</strong> ➜ <code>---</code></span><br>' .
					'<span><strong>' . esc_html__( 'Random Text', 'creame-whatsapp-me' ) . '</strong> ➜ <code>{RAND text_1||text_2||...||text_n}</code> (' . esc_html__( 'show a random option', 'creame-whatsapp-me' ) . ')</span>' .
				'</p>' .
				'<p>' . esc_html__( 'Elements can be combined. For example, for an image with link:', 'creame-whatsapp-me' ) . ' <code>{LINK https://www.example.com {IMG image.jpg}}</code></p>' .
				'<p>' .
					esc_html__( 'On images you can use the image url or the image ID of your Media Library.', 'creame-whatsapp-me' ) . ' ' .
					wp_kses( __( '<strong>We recommend</strong> to use the ID that resizes the image to the exact size of the chatbox.', 'creame-whatsapp-me' ), array( 'strong' => array() ) ) .
				'</p>' .
				'<p>' .
					/* translators: 1: FEATURED, 2: THUMB. */
					sprintf( __( 'Can use %1$s or %2$s as image url to use current post Featured Image.', 'creame-whatsapp-me' ), '<strong>FEATURED</strong>', '<strong>THUMB</strong>' ) . ' ' .
					esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>{IMG THUMB}</code> ' . esc_html__( 'or', 'creame-whatsapp-me' ) . ' <code>![{PRODUCT}](FEATURED)</code>' .
				'</p>',
			),
			array(
				'id'      => 'triggers',
				'title'   => esc_html__( 'Triggers', 'creame-whatsapp-me' ),
				'content' =>
					'<p>' . esc_html__( 'Any element in your pages can be a chat trigger:', 'creame-whatsapp-me' ) . '</p>' .
					'<p>' . sprintf( '<strong>%s</strong>, %s:', esc_html__( 'On page load, by url', 'creame-whatsapp-me' ), esc_html__( 'show Chat Window', 'creame-whatsapp-me' ) ) . '</p>' .
					'<ul>' .
						'<li>url query param <code>joinchat</code> ' . esc_html__( 'Can set delay in seconds, default is 0.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>example.com/page/?joinchat=5</code></em></li>' .
						'<li>url query hash <code>#joinchat</code> ' .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>example.com/page/#joinchat</code></em></li>' .
					'</ul>' .
					'<p>' . sprintf( '<strong>%s</strong>, %s:', esc_html__( 'On click', 'creame-whatsapp-me' ), esc_html__( 'open WhatsApp', 'creame-whatsapp-me' ) ) . '</p>' .
					'<p>' . sprintf(
						/* translators: 1: attribute phone, 2: attribute message. */
						wp_kses( __( 'You can set <strong>custom phone and initial message</strong> with attributes %1$s and %2$s.', 'creame-whatsapp-me' ), array( 'strong' => array() ) ),
						'<code>data-phone</code>',
						'<code>data-message</code>'
					) . '</p>' .
					'<ul>' .
						'<li>href <code>#whatsapp</code> ' .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;a href="#whatsapp" data-phone="99999999"&gt;' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '&lt;/a&gt;</code></em></li>' .
						'<li>class <code>joinchat_app</code> ' .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;img src="contact.jpg" class="joinchat_app" alt="' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '"&gt;</code></em></li>' .
					'</ul>' .
					'<p>' . sprintf( '<strong>%s</strong>, %s:', esc_html__( 'On click', 'creame-whatsapp-me' ), esc_html__( 'show Chat Window', 'creame-whatsapp-me' ) ) . '</p>' .
					'<p>' . esc_html__( 'If there is no chat window (no CTA and no Opt-in required) WhatsApp will open directly.', 'creame-whatsapp-me' ) . '</p>' .
					'<ul>' .
						'<li>href <code>#joinchat</code> ' .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;a href="#joinchat"&gt;' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '&lt;/a&gt;</code></em></li>' .
						'<li>class <code>joinchat_open</code> ' .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;img src="contact.jpg" class="joinchat_open" alt="' . esc_html__( 'Contact us', 'creame-whatsapp-me' ) . '"&gt;</code></em></li>' .
					'</ul>' .
					'<p>' . sprintf( '<strong>%s</strong>, %s:', esc_html__( 'On scroll (when element appears on screen)', 'creame-whatsapp-me' ), esc_html__( 'show Chat Window', 'creame-whatsapp-me' ) ) . '</p>' .
					'<ul>' .
						'<li>class <code>joinchat_show</code> ' . esc_html__( 'only show if it\'s an not seen CTA.', 'creame-whatsapp-me' ) . '</li>' .
						'<li>class <code>joinchat_force_show</code> ' . esc_html__( 'to show always.', 'creame-whatsapp-me' ) .
						' <em>' . esc_html__( 'e.g.', 'creame-whatsapp-me' ) . ' <code>&lt;footer class="joinchat_force_show"&gt;…</code></em></li>' .
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
						/* translators: %s: Joinchat X (twitter) link. */
						esc_html__( 'Follow %s on 𝕏.', 'creame-whatsapp-me' ),
						'<a href="https://x.com/joinchatnow" target="_blank">@joinchatnow</a>'
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

		// phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		$active_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], array_keys( $this->tabs ), true ) ? $_GET['tab'] : 'general';
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
					<input type="submit" class="hidden" name="submit" value="Save">
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

		$vars = apply_filters( 'joinchat_vars_help', array( 'SITE', 'TITLE', 'HOME', 'URL', 'HREF' ), $field );

		return count( $vars ) ? '<div class="joinchat_vars_help">' . esc_html__( 'You can use vars', 'creame-whatsapp-me' ) . ' ' .
			'<a class="joinchat-show-help" href="#" title="' . esc_attr__( 'Show Help', 'creame-whatsapp-me' ) . '">?</a><br> ' .
			'<code>{' . join( '}</code> <code>{', $vars ) . '}</code></div>' : '';

	}

	/**
	 * Return html for rich chat help next to field label
	 *
	 * @since 6.0.0
	 * @access public
	 * @return string
	 */
	public static function rich_chat_help() {

		return '<div class="joinchat_vars_help">' . esc_html__( 'Rich Chat', 'creame-whatsapp-me' ) .
		' <a class="joinchat-show-help" href="#tab-link-rich-chat" title="' . esc_attr__( 'Show Help', 'creame-whatsapp-me' ) . '">?</a></div>';

	}

	/**
	 * Enqueue the scripts and stylesheets for the admin page.
	 *
	 * @since    5.0.0
	 * @param    string $hook       The id of the page.
	 * @return   void
	 */
	public function enqueue_assets( $hook ) {

		$handle   = JOINCHAT_SLUG;
		$min      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$js_deps  = array( 'jquery', 'wp-color-picker' );
		$css_deps = array( 'wp-color-picker' );

		// Enqueue WordPress media scripts.
		wp_enqueue_media();

		// Enqueue IntlTelInput assets.
		if ( jc_common()->get_intltel() ) {
			$js_deps[]  = 'intl-tel-input';
			$css_deps[] = 'intl-tel-input';
		}

		// Enqueue styles.
		list($h, $s, $l, $text) = jc_common()->get_color_values();

		wp_deregister_style( $handle );
		wp_enqueue_style( $handle, plugins_url( "css/joinchat{$min}.css", __FILE__ ), $css_deps, JOINCHAT_VERSION, 'all' );
		wp_add_inline_style( $handle, "#joinchat_form { --ch:$h; --cs:$s%; --cl:$l%; --bw:$text }" );

		$example_css = '/* .joinchat some default styles
z-index: 9000;   put above or below other objects
--s: 60px;       button size
--sep: 20px;     right/left separation (mobile 6px)
--bottom: 20px;  bottom separation (same as --sep)
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
}';

		// Enqueue scripts.
		$config = array(
			'home'        => home_url(),
			'example'     => esc_html__( 'is an example, double click to use it', 'creame-whatsapp-me' ),
			'example_css' => $example_css,
		);

		wp_deregister_script( $handle );
		wp_enqueue_script( $handle, plugins_url( "js/joinchat-page{$min}.js", __FILE__ ), $js_deps, JOINCHAT_VERSION, true );
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
