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
	 * Common class for admin and front methods.
	 *
	 * @since    4.2.0
	 * @since    4.5.0  Store $settings globally
	 * @access   private
	 * @var      JoinChatCommon    $common    instance.
	 */
	private $common;

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
		$this->common      = JoinChatCommon::instance();

		$this->show = false;

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

		// Load settings.
		$settings = $this->common->load_settings();

		// If use "global $post;" take first post in loop on archive pages.
		$obj = get_queried_object();

		// Filter for site settings (can be overriden by post/term settings).
		$settings = apply_filters( 'joinchat_get_settings_site', $settings, $obj );

		// Post/term custom settings override site settings.
		$obj_settings = '';
		if ( $obj instanceof WP_Post ) {
			$obj_settings = get_post_meta( $obj->ID, '_joinchat', true );
		} elseif ( $obj instanceof WP_Term ) {
			$obj_settings = get_term_meta( $obj->term_id, '_joinchat', true );
		}

		if ( is_array( $obj_settings ) ) {
			$settings = array_merge( $settings, $obj_settings );
		}

		// Replace "{}" with empty string.
		$settings['message_text'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_text'] );
		$settings['message_send'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_send'] );

		// Prepare settings ('message_send' delay replace variables until they are used).
		$settings['telephone']     = JoinChatUtil::clean_whatsapp( $settings['telephone'] );
		$settings['mobile_only']   = 'yes' === $settings['mobile_only'];
		$settings['whatsapp_web']  = 'yes' === $settings['whatsapp_web'];
		$settings['qr']            = 'yes' === $settings['qr'];
		$settings['message_badge'] = 'yes' === $settings['message_badge'] && '' !== $settings['message_text'];
		$settings['optin_check']   = 'yes' === $settings['optin_check'];

		if ( empty( $settings['gads'] ) ) {
			unset( $settings['gads'] );
		}

		// Only show if there is a phone number.
		if ( empty( $settings['telephone'] ) ) {
			$show = false;
		} elseif ( isset( $settings['view'] ) ) {
			$show = 'yes' === $settings['view'];
		} else {
			$show = $this->check_visibility( $settings['visibility'] );
		}
		// Unset post 'view' setting.
		unset( $settings['view'] );

		// Apply filters to final settings after site and post settings.
		$settings = apply_filters( 'joinchat_get_settings', $settings, $obj );
		// Apply filters to alter 'show' value.
		$this->show = apply_filters( 'joinchat_show', $show, $settings, $obj );

		// Set a simple CTA hash, empty '' if no CTA (for javascript store viewed CTAs).
		$settings['message_hash'] = ltrim( hash( 'crc32', $settings['message_text'] ), '0' );

		// Need render QR codes.
		if ( ! $settings['mobile_only'] && $settings['qr'] ) {
			$this->common->qr = true;
		}

		$this->common->settings = $settings;

	}

	/**
	 * Enqueue the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @since    4.4.2     use "only button stylesheet" if no chatbox
	 * @return   void
	 */
	public function enqueue_styles() {

		if ( $this->show ) {
			$settings = $this->common->settings;
			$file     = $this->plugin_name;
			$min      = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// If not chatbox use lighter only button styles.
			if ( empty( $settings['message_text'] ) && empty( $settings['optin_text'] ) && ! has_filter( 'joinchat_content' ) ) {
				$file .= '-btn';
			}

			wp_enqueue_style( $this->plugin_name, plugins_url( "css/{$file}{$min}.css", __FILE__ ), array(), $this->version, 'all' );

			if ( $file === $this->plugin_name ) {
				list($r, $g, $b) = sscanf( $settings['color'], '#%02x%02x%02x' );

				wp_add_inline_style( $this->plugin_name, apply_filters( 'joinchat_inline_style', ".joinchat{ --red:$r; --green:$g; --blue:$b; }", $settings ) );
			}
		}
	}

	/**
	 * Enqueue the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @since    4.4.0     added kjua script
	 * @since    4.5.0     added joinchat-lite script
	 * @since    4.5.20    abstract QR script
	 * @return   void
	 */
	public function enqueue_scripts() {

		$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$deps = array( 'jquery' );

		// Register QR script.
		wp_register_script( 'joinchat-qr', plugins_url( 'js/kjua.min.js', __FILE__ ), array(), '0.9.0', true );

		// Note: caution with cache plugins and wp_is_mobile()
		// If QR script is missing it fails silently and don't shows QR Code :).
		if ( $this->common->qr && ! wp_is_mobile() ) {
			$deps[] = 'joinchat-qr';
		}

		if ( $this->show ) {

			// Enqueue default full script.
			wp_enqueue_script( 'joinchat', plugins_url( "js/joinchat{$min}.js", __FILE__ ), $deps, $this->version, true );

		} elseif ( apply_filters( 'joinchat_script_lite', ! empty( $this->common->settings['telephone'] ) ) ) {

			$fields = array(
				'telephone',    // Joinchat settings.
				'whatsapp_web',
				'message_send',
				'gads',
				'ga_tracker',   // Event customize.
				'ga_event',
				'data_layer',
			);

			$data = array_intersect_key( $this->common->settings, array_flip( apply_filters( 'joinchat_script_lite_fields', $fields ) ) );

			$data['message_send'] = JoinChatUtil::replace_variables( $data['message_send'] );

			// Enqueue lite script.
			wp_enqueue_script( 'joinchat-lite', plugins_url( "js/joinchat-lite{$min}.js", __FILE__ ), $deps, $this->version, true );
			wp_localize_script( 'joinchat-lite', 'joinchat_obj', array( 'settings' => $data ) );
		}
	}

	/**
	 * Ensure QR script dependency
	 *
	 * Based on post content, QR script could be required after main script is enqueued.
	 * This ensures adding the QR script as a dependency if needed.
	 *
	 * @since    4.5.0
	 * @return void
	 */
	public function enqueue_qr_script() {

		if ( ! $this->common->qr || wp_script_is( 'joinchat-qr', 'enqueued' ) || wp_is_mobile() ) {
			return;
		}

		if ( wp_script_is( 'joinchat', 'enqueued' ) ) {
			$script = wp_scripts()->query( 'joinchat', 'registered' );
		} elseif ( wp_script_is( 'joinchat-lite', 'enqueued' ) ) {
			$script = wp_scripts()->query( 'joinchat-lite', 'registered' );
		}

		// Add dependency.
		if ( $script ) {
			$script->deps[] = 'joinchat-qr';
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

		if ( ! $this->show ) {
			return;
		}

		global $wp;

		$settings = $this->common->settings;

		// Clean unnecessary settings on front.
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
				'optin_text',
				'optin_check',
			)
		);

		$data = array_diff_key( $settings, array_flip( $excluded_fields ) );

		$data['message_send'] = JoinChatUtil::replace_variables( $data['message_send'] );

		if ( '__jc__' === $settings['header'] ) {
			$powered_args = array(
				'site' => rawurlencode( get_bloginfo( 'name' ) ),
				'url'  => rawurlencode( home_url( $wp->request ) ),
			);
			$powered_lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';
			$powered_link = add_query_arg( $powered_args, "https://join.chat/$powered_lang/powered/" );
		}

		// Set custom img tag and bypass default image logic.
		$image = apply_filters( 'joinchat_image', null );

		if ( is_null( $image ) && $settings['button_image'] ) {
			$img_id = $settings['button_image'];

			if ( apply_filters( 'joinchat_image_original', JoinChatUtil::is_animated_gif( $img_id ), $img_id, 'button' ) ) {
				$image = '<img src="' . wp_get_attachment_url( $img_id ) . '" alt="" loading="lazy">';
			} elseif ( is_array( JoinChatUtil::thumb( $img_id, 58, 58 ) ) ) {
				$thumb  = JoinChatUtil::thumb( $img_id, 58, 58 );
				$thumb2 = JoinChatUtil::thumb( $img_id, 116, 116 );
				$thumb3 = JoinChatUtil::thumb( $img_id, 174, 174 );
				$image  = "<img src=\"{$thumb['url']}\" srcset=\"{$thumb2['url']} 2x, {$thumb3['url']} 3x\" alt=\"\" loading=\"lazy\">";
			}
		}

		$joinchat_classes = array();
		$box_content      = '';

		// class position.
		$joinchat_classes[] = 'joinchat--' . $settings['position'];

		// class dark mode.
		if ( 'no' !== $settings['dark_mode'] ) {
			$joinchat_classes[] = 'auto' === $settings['dark_mode'] ? 'joinchat--dark-auto' : 'joinchat--dark';
		}

		// class direct display (w/o animation).
		if ( $settings['button_delay'] < 0 ) {
			$data['button_delay'] = 0;
			$joinchat_classes[]   = 'joinchat--show';
			$joinchat_classes[]   = 'joinchat--noanim';

			if ( $settings['mobile_only'] ) {
				$joinchat_classes[] = 'joinchat--mobile';
			}
		}

		if ( $settings['message_text'] ) {
			$box_content = '<div class="joinchat__message">' . JoinChatUtil::formated_message( $settings['message_text'] ) . '</div>';
		}

		if ( $settings['optin_text'] ) {
			$optin = nl2br( $settings['optin_text'] );
			$optin = str_replace( '<a ', '<a target="_blank" rel="nofollow noopener" ', $optin );

			if ( $settings['optin_check'] ) {
				$optin              = '<input type="checkbox" id="joinchat_optin"><label for="joinchat_optin">' . $optin . '</label>';
				$joinchat_classes[] = 'joinchat--optout';
			}

			$box_content .= '<div class="joinchat__optin">' . $optin . '</div>';
		}

		$box_content = apply_filters( 'joinchat_content', $box_content, $settings );

		// class only button.
		if ( empty( $box_content ) ) {
			$joinchat_classes[] = 'joinchat--btn';
		}

		$joinchat_classes = apply_filters( 'joinchat_classes', $joinchat_classes, $settings );

		ob_start();
		include __DIR__ . '/partials/html.php';
		$html_output = ob_get_clean();

		echo apply_filters( 'joinchat_html_output', $html_output, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	}

	/**
	 * Check visibility on current page
	 *
	 * @since    2.0.0
	 * @since    3.0.0       Added filter to 'joinchat_visibility'
	 * @param    array $options    array of visibility settings.
	 * @return   boolean     is visible or not on current page
	 */
	public function check_visibility( $options ) {

		// Custom visibility, bypass all checks if not null.
		$visibility = apply_filters( 'joinchat_visibility', null, $options );
		if ( ! is_null( $visibility ) ) {
			return $visibility;
		}

		$global = isset( $options['all'] ) ? 'yes' === $options['all'] : true;

		// Check front page.
		if ( is_front_page() ) {
			return isset( $options['front_page'] ) ? 'yes' === $options['front_page'] : $global;
		}

		// Check blog page.
		if ( is_home() ) {
			return isset( $options['blog_page'] ) ? 'yes' === $options['blog_page'] : $global;
		}

		// Check 404 page.
		if ( is_404() ) {
			return isset( $options['404_page'] ) ? 'yes' === $options['404_page'] : $global;
		}

		// Check Custom Post Types.
		if ( is_array( $options ) ) {
			foreach ( $options as $cpt => $view ) {
				if ( substr( $cpt, 0, 4 ) === 'cpt_' ) {
					$cpt = substr( $cpt, 4 );
					if ( is_singular( $cpt ) || is_post_type_archive( $cpt ) ) {
						return 'yes' === $view;
					}
				}
			}
		}

		// Search results.
		if ( is_search() ) {
			return isset( $options['search'] ) ? 'yes' === $options['search'] : $global;
		}

		// Check archives.
		if ( is_archive() ) {

			// Date archive.
			if ( isset( $options['date'] ) && is_date() ) {
				return 'yes' === $options['date'];
			}

			// Author archive.
			if ( isset( $options['author'] ) && is_author() ) {
				return 'yes' === $options['author'];
			}

			return isset( $options['archive'] ) ? 'yes' === $options['archive'] : $global;
		}

		// Check singular.
		if ( is_singular() ) {

			// Page.
			if ( isset( $options['page'] ) && is_page() ) {
				return 'yes' === $options['page'];
			}

			// Post (or other custom posts).
			if ( isset( $options['post'] ) && is_single() ) {
				return 'yes' === $options['post'];
			}

			return isset( $options['singular'] ) ? 'yes' === $options['singular'] : $global;
		}

		return $global;
	}

}
