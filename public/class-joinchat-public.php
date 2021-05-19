<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @since      3.0.0      Added $show property and more hooks
 * @package    JoinChat
 * @subpackage JoinChat/public
 * @author     Creame <hola@crea.me>
 */
class JoinChatPublic {

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
	 * Show WhatsApp button in front.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      bool     $show    Show button on front.
	 */
	private $show;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @since    2.0.0     Added visibility setting
	 * @since    2.1.0     Added message_badge
	 * @since    2.3.0     Added button_delay and whatsapp_web settings, message_delay in seconds
	 * @param    string $plugin_name       The name of the plugin.
	 * @param    string $version    The version of this plugin.
	 * @return   void
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Updated in get_settings() at 'wp' hook
		$this->show     = false;
		$this->settings = array();

	}

	/**
	 * Get global settings and current post settings and prepare
	 *
	 * @since    1.0.0
	 * @since    2.0.0   Check visibility
	 * @since    2.2.0   Post settings can also change "telephone". Added 'whastapp_web' setting
	 * @since    2.3.0   Fix global $post incorrect post id on loops. WPML integration.
	 * @since    3.0.0   New filters.
	 * @return   void
	 */
	public function get_settings() {

		// If use "global $post;" take first post in loop on archive pages
		$obj = get_queried_object();

		$default_settings = array_merge(
			array(
				'telephone'     => '',
				'mobile_only'   => 'no',
				'button_image'  => '',
				'button_tip'    => '',
				'button_delay'  => 3,
				'whatsapp_web'  => 'no',
				'message_text'  => '',
				'message_views' => 2,
				'message_delay' => 10,
				'message_badge' => 'no',
				'message_send'  => '',
				'message_start' => __( 'Open chat', 'creame-whatsapp-me' ),
				'position'      => 'right',
				'visibility'    => array( 'all' => 'yes' ),
				'color'         => '#25d366',
				'dark_mode'     => 'no',
				'header'        => '__jc__', // values: '__jc__', '__wa__' or other custom text
			),
			apply_filters( 'joinchat_extra_settings', array() )
		);

		// Can hook 'option_joinchat' and 'default_option_joinchat' filters
		$settings = array_merge( $default_settings, (array) get_option( 'joinchat', $default_settings ) );

		// Migrate addons 'remove_brand' setting to 'header' (v. < 4.1)
		if ( isset( $settings['remove_brand'] ) ) {
			$remove             = $settings['remove_brand'];
			$settings['header'] = 'wa' == $remove ? '__wa__' : ( 'no' == $remove ? '__jc__' : '' );
		}

		// Clean unused saved settings
		$settings = array_intersect_key( $settings, $default_settings );

		// Load WPML/Polylang translated strings
		$settings_i18n = JoinChatUtil::settings_i18n( $settings );

		foreach ( $settings_i18n as $key => $label ) {
			if ( isset( $settings[ $key ] ) ) {
				$settings[ $key ] = $settings[ $key ] ? apply_filters( 'wpml_translate_single_string', $settings[ $key ], 'Join.chat', $label ) : '';
			}
		}

		// Filter for site settings (can be overriden by post settings)
		$settings = apply_filters( 'joinchat_get_settings_site', $settings, $obj );

		// Post custom settings override site settings
		$post_settings = is_a( $obj, 'WP_Post' ) ? get_post_meta( $obj->ID, '_joinchat', true ) : '';

		if ( is_array( $post_settings ) ) {
			$settings = array_merge( $settings, $post_settings );

			// Allow override general settings with empty string with "{}"
			$settings['message_text'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_text'] );
			$settings['message_send'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_send'] );
		}

		// Prepare settings
		$settings['telephone']     = preg_replace( '/^0+|\D/', '', $settings['telephone'] );
		$settings['mobile_only']   = 'yes' == $settings['mobile_only'];
		$settings['whatsapp_web']  = 'yes' == $settings['whatsapp_web'];
		$settings['message_badge'] = 'yes' == $settings['message_badge'] && '' != $settings['message_text'];
		$settings['message_send']  = JoinChatUtil::replace_variables( $settings['message_send'] );
		// Set true to link http://web.whatsapp.com instead http://api.whatsapp.com
		$settings['whatsapp_web'] = apply_filters( 'joinchat_whatsapp_web', 'yes' == $settings['whatsapp_web'] );

		// Only show if there is a phone number
		if ( empty( $settings['telephone'] ) ) {
			$show = false;
		} elseif ( isset( $settings['view'] ) ) {
			$show = 'yes' == $settings['view'];
		} else {
			$show = $this->check_visibility( $settings['visibility'] );
		}
		// Unset post 'view' setting
		unset( $settings['view'] );

		// Apply filters to final settings after site and post settings
		$this->settings = apply_filters( 'joinchat_get_settings', $settings, $obj );
		// Apply filters to alter 'show' value
		$this->show = apply_filters( 'joinchat_show', $show, $this->settings, $obj );

		// Set a simple CTA hash, empty '' if no CTA (for javascript store viewed CTAs)
		$this->settings['message_hash'] = ltrim( hash( 'crc32', $this->settings['message_text'] ), '0' );

	}

	/**
	 * Enqueue the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @return   void
	 */
	public function enqueue_styles() {

		if ( $this->show ) {
			$min             = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$color           = $this->settings['color'];
			list($r, $g, $b) = sscanf( $color, '#%02x%02x%02x' );

			wp_enqueue_style( $this->plugin_name, plugins_url( "css/{$this->plugin_name}{$min}.css", __FILE__ ), array(), $this->version, 'all' );
			wp_add_inline_style( $this->plugin_name, apply_filters( 'joinchat_inline_style', ".joinchat{ --red:$r; --green:$g; --blue:$b; }", $this->settings ) );
		}

	}

	/**
	 * Enqueue the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @return   void
	 */
	public function enqueue_scripts() {

		if ( $this->show ) {
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_script( $this->plugin_name, plugins_url( "js/{$this->plugin_name}{$min}.js", __FILE__ ), array( 'jquery' ), $this->version, true );
		}

	}

	/**
	 * Outputs WhatsApp button html and his settings on footer
	 *
	 * @since    1.0.0
	 * @since    3.2.0  Capture and filter output
	 * @return   void
	 */
	public function footer_html() {
		global $wp;

		if ( $this->show ) {

			// Clean unnecessary settings on front
			$excluded_fields = apply_filters(
				'joinchat_excluded_fields',
				array(
					'visibility',
					'position',
					'button_tip',
					'button_image',
					'message_start',
					'message_text',
					'color',
					'dark_mode',
					'header',
				)
			);

			$data = array_diff_key( $this->settings, array_flip( $excluded_fields ) );

			if ( '__jc__' === $this->settings['header'] ) {
				$powered_url  = urlencode( home_url( $wp->request ) );
				$powered_site = urlencode( get_bloginfo( 'name' ) );
				$powered_lang = _x( 'en', 'url lang slug (only available for spanish "es")', 'creame-whatsapp-me' );
				$powered_link = "https://join.chat/{$powered_lang}/powered/?site={$powered_site}&url={$powered_url}";
			}

			// Set custom img tag and bypass default image logic
			$image = apply_filters( 'joinchat_image', null );

			if ( is_null( $image ) && $this->settings['button_image'] ) {
				$img_id = $this->settings['button_image'];

				if ( apply_filters( 'joinchat_image_original', JoinChatUtil::is_animated_gif( $img_id ), $img_id, 'button' ) ) {
					$image = '<img src="' . wp_get_attachment_url( $img_id ) . '" alt="" loading="lazy">';
				} elseif ( is_array( JoinChatUtil::thumb( $img_id, 58, 58 ) ) ) {
					$thumb  = JoinChatUtil::thumb( $img_id, 58, 58 );
					$thumb2 = JoinChatUtil::thumb( $img_id, 116, 116 );
					$thumb3 = JoinChatUtil::thumb( $img_id, 174, 174 );
					$image  = "<img src=\"{$thumb['url']}\" srcset=\"{$thumb2['url']} 2x, {$thumb3['url']} 3x\" alt=\"\" loading=\"lazy\">";
				}
			}

			$joinchat_classes = 'joinchat--' . $this->settings['position'];
			if ( 'no' !== $this->settings['dark_mode'] ) {
				$joinchat_classes .= 'auto' === $this->settings['dark_mode'] ? ' joinchat--dark-auto' : ' joinchat--dark';
			}

			$box_content = '';
			if ( $this->settings['message_text'] ) {
				$box_content = '<div class="joinchat__message">' . JoinChatUtil::formated_message( $this->settings['message_text'] ) . '</div>';
			}
			$box_content = apply_filters( 'joinchat_content', $box_content, $this->settings );

			ob_start();
			include __DIR__ . '/partials/html.php';
			$html_output = ob_get_clean();

			echo apply_filters( 'joinchat_html_output', $html_output, $this->settings );
		}
	}

	/**
	 * Prints a fallback script to open WhatsApp for html triggers when Join.chat is not showed
	 *
	 * @since    4.1.5
	 * @return   void
	 */
	public function links_script() {

		if ( ! $this->show && ! empty( $this->settings['telephone'] ) && wp_script_is( 'jquery', 'enqueued' ) ) {
			$args = array(
				'tel' => $this->settings['telephone'],
				'msg' => $this->settings['message_send'],
				'web' => $this->settings['whatsapp_web'],
			);

			include __DIR__ . '/partials/script.php';
		}

	}

	/**
	 * Check visibility on current page
	 *
	 * @since    2.0.0
	 * @since    3.0.0       Added filter to 'joinchat_visibility'
	 * @param    array $options    array of visibility settings
	 * @return   boolean     is visible or not on current page
	 */
	public function check_visibility( $options ) {

		// Custom visibility, bypass all checks if not null
		$visibility = apply_filters( 'joinchat_visibility', null, $options );
		if ( ! is_null( $visibility ) ) {
			return $visibility;
		}

		$global = isset( $options['all'] ) ? 'yes' == $options['all'] : true;

		// Check front page
		if ( is_front_page() ) {
			return isset( $options['front_page'] ) ? 'yes' == $options['front_page'] : $global;
		}

		// Check blog page
		if ( is_home() ) {
			return isset( $options['blog_page'] ) ? 'yes' == $options['blog_page'] : $global;
		}

		// Check 404 page
		if ( is_404() ) {
			return isset( $options['404_page'] ) ? 'yes' == $options['404_page'] : $global;
		}

		// Check Custom Post Types
		if ( is_array( $options ) ) {
			foreach ( $options as $cpt => $view ) {
				if ( substr( $cpt, 0, 4 ) == 'cpt_' ) {
					$cpt = substr( $cpt, 4 );
					if ( is_singular( $cpt ) || is_post_type_archive( $cpt ) ) {
						return 'yes' == $view;
					}
				}
			}
		}

		// Search results
		if ( is_search() ) {
			return isset( $options['search'] ) ? 'yes' == $options['search'] : $global;
		}

		// Check archives
		if ( is_archive() ) {

			// Date archive
			if ( isset( $options['date'] ) && is_date() ) {
				return 'yes' == $options['date'];
			}

			// Author archive
			if ( isset( $options['author'] ) && is_author() ) {
				return 'yes' == $options['author'];
			}

			return isset( $options['archive'] ) ? 'yes' == $options['archive'] : $global;
		}

		// Check singular
		if ( is_singular() ) {

			// Page
			if ( isset( $options['page'] ) && is_page() ) {
				return 'yes' == $options['page'];
			}

			// Post (or other custom posts)
			if ( isset( $options['post'] ) && is_single() ) {
				return 'yes' == $options['post'];
			}

			return isset( $options['singular'] ) ? 'yes' == $options['singular'] : $global;
		}

		return $global;
	}

}
