<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Joinchat
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @since      3.0.0      Added $show property and more hooks
 * @package    Joinchat
 * @subpackage Joinchat/public
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Public {

	/**
	 * Show WhatsApp button in front.
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      bool     $show    Show button on front.
	 */
	private $show = false;

	/**
	 * Chatbox content.
	 *
	 * @since    6.0.0
	 * @access   private
	 * @var      string     $chatbox_content    Chatbox content (messages & opt-in).
	 */
	private $chatbox_content = '';

	/**
	 * Get global settings and current post settings and prepare
	 *
	 * @since    1.0.0
	 * @since    2.0.0   Check visibility
	 * @since    2.2.0   Post settings can also change "telephone". Added 'whastapp_web' setting
	 * @since    2.3.0   Fix global $post incorrect post id on loops. WPML integration.
	 * @since    3.0.0   New filters.
	 * @since    5.0.0   Work as a filter for Joinchat_Common->load_settings()
	 * @param    array $settings    Raw settings.
	 * @return   array   Front prepared settings for current page
	 */
	public function get_settings( $settings ) {

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
		$settings['telephone']     = Joinchat_Util::clean_whatsapp( $settings['telephone'] );
		$settings['mobile_only']   = 'yes' === $settings['mobile_only'];
		$settings['whatsapp_web']  = 'yes' === $settings['whatsapp_web'];
		$settings['qr']            = 'yes' === $settings['qr'];
		$settings['message_badge'] = 'yes' === $settings['message_badge'] && '' !== $settings['message_text'];
		$settings['optin_check']   = 'yes' === $settings['optin_check'];

		if ( empty( $settings['gads'] ) ) {
			unset( $settings['gads'] );
		}

		// Apply filters to final settings after site and post settings.
		$settings = apply_filters( 'joinchat_get_settings', $settings, $obj );

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

		// Apply filters to alter 'show' value.
		$this->show = apply_filters( 'joinchat_show', $show, $settings, $obj );

		// Set a simple CTA hash, empty '' if no CTA (for javascript store viewed CTAs).
		$settings['message_hash'] = ltrim( hash( 'crc32', $settings['message_text'] ), '0' );

		// Need render QR codes.
		if ( ! $settings['mobile_only'] && $settings['qr'] ) {
			jc_common()->qr = true;
		}

		return $settings;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * Can defer styles if button delay > 0:
	 *  - move stylesheet to footer
	 *  - add 'media="print"' view(https://www.filamentgroup.com/lab/load-css-simpler/)
	 *
	 * @since    6.0.0
	 * @return   void
	 */
	public function register_styles() {

		if ( ! $this->show ) {
			return;
		}

		$file = JOINCHAT_SLUG;
		$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Defer styles by default.
		$defer = apply_filters( 'joinchat_defer_styles', true );

		// If not chatbox use lighter only button styles.
		if ( empty( $this->chatbox_content ) ) {
			$file .= '-btn';
		}

		wp_register_style( JOINCHAT_SLUG, plugins_url( "public/css/{$file}{$min}.css", JOINCHAT_FILE ), array(), JOINCHAT_VERSION, $defer ? 'print' : 'all' );

		if ( ! $defer || jc_common()->preview ) {
			$this->enqueue_styles();
		}
	}

	/**
	 * Inline header styles.
	 *
	 * If button appears directly (delay < 0) inline on <head> min required styles.
	 *
	 * @since    6.0.0
	 * @return   void
	 */
	public function header_styles() {

		if ( ! $this->show || jc_common()->settings['button_delay'] >= 0 || did_filter( 'joinchat_inline_style' ) ) {
			return;
		}

		$handle = JOINCHAT_SLUG . '-head';
		$css    = $this->get_inline_styles();
		$css   .= file_get_contents( JOINCHAT_DIR . 'public/css/joinchat-head.css' );

		wp_register_style( $handle, false ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_style( $handle );
		wp_add_inline_style( $handle, Joinchat_Util::min_css( $css ) );

	}

	/**
	 * Enqueue front stylesheets and adds inline CSS.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @since    4.4.2     use "only button stylesheet" if no chatbox
	 * @since    6.0.0     Only enqueue, register is on register_styles()
	 * @return   void
	 */
	public function enqueue_styles() {

		if ( ! $this->show || wp_style_is( JOINCHAT_SLUG, 'done' ) ) {
			return;
		}

		wp_enqueue_style( JOINCHAT_SLUG );
		wp_add_inline_style( JOINCHAT_SLUG, Joinchat_Util::min_css( $this->get_inline_styles() ) );

	}

	/**
	 * Get inline styles
	 *
	 * @since 6.0.0
	 * @return string
	 */
	private function get_inline_styles() {

		$inline_css = '';
		$settings   = jc_common()->settings;

		if ( jc_common()->defaults( 'color' ) !== $settings['color'] ) {
			list($h, $s, $l, $text) = jc_common()->get_color_values();

			$inline_css .= ".joinchat{ --ch:$h; --cs:$s%; --cl:$l%; --bw:$text }";
		}

		if ( ! empty( $settings['custom_css'] ) ) {
			// Note that esc_html() cannot be used because `div &gt; span`.
			$inline_css .= wp_strip_all_tags( $settings['custom_css'] );
		}

		return apply_filters( 'joinchat_inline_style', $inline_css, $settings );

	}

	/**
	 * Defer styles if needed
	 *
	 * If handle starts with 'joinchat' and media is 'print' add onload.
	 *
	 * @since 6.0.0
	 * @param string $tag    The link tag.
	 * @param string $handle The handle of the enqueued style.
	 * @param string $href   The source URL of the enqueued style.
	 * @param string $media  The media for which the stylesheet has been defined.
	 * @return string
	 */
	public function defer_styles( $tag, $handle, $href, $media ) {
		if ( 'print' !== $media || strpos( $handle, JOINCHAT_SLUG ) !== 0 ) {
			return $tag;
		}

		$tag = str_replace( "'", '"', $tag );
		$tag = str_replace( 'media="print"', 'media="print" onload="this.media=\'all\'"', $tag );

		return $tag;
	}

	/**
	 * Enqueue the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @since    4.4.0     added kjua script
	 * @since    4.5.0     added joinchat-lite script
	 * @since    4.5.20    abstract QR script
	 * @since    6.0.0     remove jQuery dependency & defer strategy
	 * @return   void
	 */
	public function enqueue_scripts() {

		$min  = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$args = is_wp_version_compatible( '6.3' ) ? array(
			'strategy'  => 'defer',
			'in_footer' => true,
		) : true;

		// Register QR script.
		wp_register_script( 'joinchat-qr', plugins_url( 'js/qr-creator.min.js', __FILE__ ), array(), '1.0.0', $args );

		// If QR script is missing it fails silently and don't shows QR Code :).
		$deps = $this->need_enqueue_qr_script() ? array( 'joinchat-qr' ) : array();

		if ( $this->show ) {

			// Enqueue default full script.
			wp_enqueue_script( JOINCHAT_SLUG, plugins_url( "js/joinchat{$min}.js", __FILE__ ), $deps, JOINCHAT_VERSION, $args );
			// Do action.
			do_action( 'joinchat_enqueue_script' );

		} elseif ( apply_filters( 'joinchat_script_lite', ! empty( jc_common()->settings['telephone'] ) ) ) {

			$fields = array(
				'telephone',    // Joinchat settings.
				'whatsapp_web',
				'message_send',
				'gads',
				'ga_tracker',   // Event customize.
				'ga_event',
				'data_layer',
			);

			$data = array_intersect_key( jc_common()->settings, array_flip( apply_filters( 'joinchat_script_lite_fields', $fields ) ) );

			$data['message_send'] = Joinchat_Util::replace_variables( $data['message_send'] );

			// Enqueue lite script.
			wp_enqueue_script( 'joinchat-lite', plugins_url( "js/joinchat-lite{$min}.js", __FILE__ ), $deps, JOINCHAT_VERSION, $args );
			wp_add_inline_script( 'joinchat-lite', 'var joinchat_obj = ' . wp_json_encode( array( 'settings' => $data ) ) . ';', 'before' );
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

		if ( wp_script_is( 'joinchat-qr' ) || ! $this->need_enqueue_qr_script() ) {
			return;
		}

		$script = false;
		if ( wp_script_is( JOINCHAT_SLUG ) ) {
			$script = wp_scripts()->query( JOINCHAT_SLUG, 'registered' );
		} elseif ( wp_script_is( 'joinchat-lite', 'enqueued' ) ) {
			$script = wp_scripts()->query( 'joinchat-lite', 'registered' );
		}

		// Add dependency.
		if ( $script ) {
			$script->deps[] = 'joinchat-qr';
		}
	}

	/**
	 * Set chatbox content (messages & opt-in)
	 *
	 * @since    6.0.0
	 * @return void
	 */
	public function set_chatbox_content() {

		$settings   = jc_common()->settings;
		$is_preview = jc_common()->preview;
		$content    = '';

		if ( $settings['message_text'] ) {
			$content = '<div class="joinchat__chat">' . Joinchat_Util::formatted_message( $settings['message_text'] ) . '</div>';
		} elseif ( $is_preview ) {
			$content = '<div class="joinchat__chat"><div class="joinchat__bubble"></div></div>';
		}

		if ( $settings['optin_text'] ) {
			$optin = nl2br( $settings['optin_text'] );
			$optin = str_replace( '<a ', '<a target="_blank" rel="nofollow noopener" ', $optin );

			if ( $settings['optin_check'] ) {
				$optin = '<input type="checkbox" id="joinchat_optin"><label for="joinchat_optin">' . $optin . '</label>';
			}

			$content .= '<div class="joinchat__optin">' . $optin . '</div>';
		} elseif ( $is_preview ) {
			$content .= '<div class="joinchat__optin"></div>';
		}

		$this->chatbox_content = apply_filters( 'joinchat_content', $content, $settings );

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

		$settings   = jc_common()->settings;
		$is_preview = jc_common()->preview;

		// Clean unnecessary settings on front.
		$excluded_fields = apply_filters(
			'joinchat_excluded_fields',
			array(
				'visibility',
				'position',
				'button_ico',
				'button_tip',
				'button_image',
				'message_start',
				'message_text',
				'color',
				'dark_mode',
				'header',
				'optin_text',
				'optin_check',
				'qr_text',
				'custom_css',
				'clear',
			)
		);

		$data = array_diff_key( $settings, array_flip( $excluded_fields ) );

		$data['message_send'] = Joinchat_Util::replace_variables( $data['message_send'] );

		if ( '__jc__' === $settings['header'] || $is_preview ) {
			$powered_args = array(
				'site' => rawurlencode( get_bloginfo( 'name' ) ),
				'url'  => rawurlencode( home_url( $wp->request ) ),
			);
			$powered_lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';
			$powered_link = add_query_arg( $powered_args, "https://join.chat/$powered_lang/powered/" );
		}

		$ico = 'app' !== $settings['button_ico'] ? jc_common()->get_icons( $settings['button_ico'] ) : '';

		// Set custom img tag and bypass default image logic.
		$image = apply_filters( 'joinchat_image', null );

		if ( is_null( $image ) && $settings['button_image'] ) {
			$img_id = absint( $settings['button_image'] );

			if ( Joinchat_Util::is_video( $img_id ) ) {
				$image = '<video autoplay loop muted playsinline src="' . esc_url( wp_get_attachment_url( $img_id ) ) . '"></video>';
			} elseif ( apply_filters( 'joinchat_image_original', Joinchat_Util::is_animated_gif( $img_id ), $img_id, 'button' ) ) {
				$image = '<img src="' . esc_url( wp_get_attachment_url( $img_id ) ) . '" alt="" loading="lazy">';
			} elseif ( is_array( Joinchat_Util::thumb( $img_id, 58, 58 ) ) ) {
				$thumb  = Joinchat_Util::thumb( $img_id, 58, 58 );
				$thumb2 = Joinchat_Util::thumb( $img_id, 116, 116 );
				$thumb3 = Joinchat_Util::thumb( $img_id, 174, 174 );
				$image  = '<img src="' . esc_url( $thumb['url'] ) . '" srcset="' . esc_url( $thumb2['url'] ) . ' 2x, ' . esc_url( $thumb3['url'] ) . ' 3x" alt="" loading="lazy">';
			}
		}

		$joinchat_classes = array();
		$box_content      = $this->chatbox_content;

		// class position.
		$joinchat_classes[] = 'joinchat--' . $settings['position'];

		// class dark mode.
		if ( 'no' !== $settings['dark_mode'] ) {
			$joinchat_classes[] = 'auto' === $settings['dark_mode'] ? 'joinchat--dark-auto' : 'joinchat--dark';
		}

		// class for button fixed image.
		if ( (int) $settings['button_image'] < 0 ) {
			$joinchat_classes[] = 'joinchat--img';
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

		// class for required opt-in (initially opt-out).
		if ( ! empty( $settings['optin_text'] ) && $settings['optin_check'] ) {
			$joinchat_classes[] = 'joinchat--optout';
		}

		// class only button.
		if ( empty( $box_content ) ) {
			$joinchat_classes[] = 'joinchat--btn';
		}

		$joinchat_classes  = apply_filters( 'joinchat_classes', $joinchat_classes, $settings );
		$joinchat_template = apply_filters( 'joinchat_template', __DIR__ . '/partials/html.php' );

		ob_start();
		include $joinchat_template;
		$html_output = ob_get_clean();

		echo apply_filters( 'joinchat_html_output', $html_output, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		do_action( 'joinchat_after_html' );

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

	/**
	 * Need enqueue QR script
	 *
	 * Note: caution with cache plugins and wp_is_mobile()
	 *
	 * @return bool
	 */
	private function need_enqueue_qr_script() {
		return apply_filters( 'joinchat_enqueue_qr', jc_common()->qr && ! wp_is_mobile() );
	}
}
