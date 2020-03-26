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

		$settings = $default_settings;
		$show     = false;

		$site_settings = get_option( 'joinchat' );

		if ( is_array( $site_settings ) ) {
			// Clean unused saved settings
			$settings = array_intersect_key( $site_settings, $default_settings );
			// Merge defaults with saved settings
			$settings = array_merge( $default_settings, $settings );
			// miliseconds (<v2.3) to seconds
			if ( $settings['message_delay'] > 120 ) {
				$settings['message_delay'] = round( $settings['message_delay'] / 1000 );
			}

			// Load WPML/Polylang translated strings
			$settings_i18n = JoinChatUtil::settings_i18n();

			foreach ( $settings_i18n as $key => $label ) {
				$settings[ $key ] = $settings[ $key ] ? apply_filters( 'wpml_translate_single_string', $settings[ $key ], 'Join.chat', $label ) : '';
			}

			// Filter for site settings (can be overriden by post settings)
			$settings = apply_filters( 'joinchat_get_settings_site', $settings, $obj );

			// Post custom settings override site settings
			$post_settings = is_a( $obj, 'WP_Post' ) ? get_post_meta( $obj->ID, '_joinchat', true ) : '';

			if ( is_array( $post_settings ) ) {
				// Move old 'hide' to new 'view' field
				if ( isset( $post_settings['hide'] ) ) {
					$post_settings['view'] = 'no';
					unset( $post_settings['hide'] );
				}

				$settings = array_merge( $settings, $post_settings );

				// Allow override general settings with empty string with "{}"
				$settings['message_text'] = preg_match( '/^\{\s*\}$/', $settings['message_text'] ) ? '' : $settings['message_text'];
				$settings['message_send'] = preg_match( '/^\{\s*\}$/', $settings['message_send'] ) ? '' : $settings['message_send'];
			}

			// Prepare settings
			$settings['telephone']     = preg_replace( '/^0+|\D/', '', $settings['telephone'] );
			$settings['mobile_only']   = 'yes' == $settings['mobile_only'];
			$settings['whatsapp_web']  = 'yes' == $settings['whatsapp_web'];
			$settings['message_badge'] = 'yes' == $settings['message_badge'] && '' != $settings['message_text'];
			$settings['position']      = 'right' == $settings['position'] ? 'right' : 'left';
			$settings['dark_mode']     = in_array( $settings['dark_mode'], array( 'no', 'yes', 'auto' ) ) ? $settings['dark_mode'] : 'no';
			$settings['message_send']  = JoinChatUtil::replace_variables( $settings['message_send'] );
			// Set true to link http://web.whatsapp.com instead http://api.whatsapp.com
			$settings['whatsapp_web'] = apply_filters( 'joinchat_whatsapp_web', 'yes' == $settings['whatsapp_web'] );

			// Only show if there is a phone number
			if ( '' != $settings['telephone'] ) {
				if ( isset( $settings['view'] ) && 'yes' == $settings['view'] ) {
					$show = true;
				} elseif ( isset( $settings['view'] ) && 'no' == $settings['view'] ) {
					$show = false;
				} else {
					$show = $this->check_visibility( $settings['visibility'] );
				}
			}
			// Unset post 'view' setting
			unset( $settings['view'] );
		}

		// Apply filters to final settings after site and post settings
		$this->settings = apply_filters( 'joinchat_get_settings', $settings, $obj );
		// Apply filters to alter 'show' value
		$this->show = apply_filters( 'joinchat_show', $show, $this->settings, $obj );

		// Ensure not show if not phone
		if ( '' == $this->settings['telephone'] ) {
			$this->show = false;
		}
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
			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . "css/{$this->plugin_name}{$min}.css", array(), $this->version, 'all' );
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
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . "js/{$this->plugin_name}{$min}.js", array( 'jquery' ), $this->version, true );
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
					'dark_mode',
				)
			);

			$data = array_diff_key( $this->settings, array_flip( $excluded_fields ) );

			$copy = apply_filters( 'joinchat_copy', 'Powered by' );

			$powered_url  = urlencode( home_url( $wp->request ) );
			$powered_site = urlencode( get_bloginfo( 'name' ) );
			$powered_link = "https://join.chat/powered/?site={$powered_site}&url={$powered_url}";

			// Set custom img tag and bypass default image logic
			$image = apply_filters( 'joinchat_image', null );

			if ( is_null( $image ) && $this->settings['button_image'] ) {
				$img_path = get_attached_file( $this->settings['button_image'] );

				if ( apply_filters( 'joinchat_image_original', JoinChatUtil::is_animated_gif( $img_path ) ) ) {
					$image = '<img src="' . wp_get_attachment_url( $this->settings['button_image'] ) . '" alt="">';
				} elseif ( is_array( JoinChatUtil::thumb( $img_path, 58, 58 ) ) ) {
					$thumb  = JoinChatUtil::thumb( $img_path, 58, 58 );
					$thumb2 = JoinChatUtil::thumb( $img_path, 116, 116 );
					$thumb3 = JoinChatUtil::thumb( $img_path, 174, 174 );
					$image  = "<img src=\"{$thumb['url']}\" srcset=\"{$thumb2['url']} 2x, {$thumb3['url']} 3x\" alt=\"\">";
				}
			}

			$joinchat_classes  = 'joinchat--' . $this->settings['position'];
			$joinchat_classes .= isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false ? ' joinchat--webp' : '';
			if ( 'no' !== $this->settings['dark_mode'] ) {
				$joinchat_classes .= 'auto' === $this->settings['dark_mode'] ? ' joinchat--dark-auto' : ' joinchat--dark';
			}

			$box_content = '';
			if ( $this->settings['message_text'] ) {
				$box_content = '<div class="joinchat__message">' . JoinChatUtil::formated_message( $this->settings['message_text'] ) . '</div>';
			}
			$box_content = apply_filters( 'joinchat_content', $box_content, $this->settings );

			ob_start();

			// load SVGs
			echo file_get_contents( __DIR__ . '/images/svgs.php' );
			?>
			<div class="joinchat <?php echo apply_filters( 'joinchat_classes', $joinchat_classes ); ?>" data-settings="<?php echo esc_attr( json_encode( $data ) ); ?>">
				<div class="joinchat__button">
					<svg class="joinchat__button__open"><use href="#joinchat_svg__logo"></use></svg>
					<?php if ( $image ) : ?>
						<div class="joinchat__button__image"><?php echo $image; ?></div>
					<?php endif; ?>
					<?php if ( $this->settings['message_start'] ) : ?>
						<div class="joinchat__button__sendtext"><?php echo $this->settings['message_start']; ?></div>
					<?php endif; ?>
					<?php if ( $this->settings['message_text'] ) : ?>
						<svg class="joinchat__button__send" viewbox="0 0 400 400" stroke-linecap="round" stroke-width="33">
							<path class="joinchat_svg__plain" d="M168.83 200.504H79.218L33.04 44.284a1 1 0 0 1 1.386-1.188L365.083 199.04a1 1 0 0 1 .003 1.808L34.432 357.903a1 1 0 0 1-1.388-1.187l29.42-99.427"/>
							<path class="joinchat_svg__chat" d="M318.087 318.087c-52.982 52.982-132.708 62.922-195.725 29.82l-80.449 10.18 10.358-80.112C18.956 214.905 28.836 134.99 81.913 81.913c65.218-65.217 170.956-65.217 236.174 0 42.661 42.661 57.416 102.661 44.265 157.316"/>
						</svg>
					<?php endif; ?>
					<?php if ( $this->settings['message_badge'] ) : ?>
						<div class="joinchat__badge">1</div>
					<?php endif; ?>
					<?php if ( $this->settings['button_tip'] ) : ?>
						<div class="joinchat__tooltip"><div><?php echo $this->settings['button_tip']; ?></div></div>
					<?php endif; ?>
				</div>
				<?php if ( $box_content ) : ?>
					<div class="joinchat__box">
						<div class="joinchat__header">
							<svg><use href="#joinchat_svg__whatsapp"></use></svg>
							<div class="joinchat__close"><svg><use href="#joinchat_svg__close"></use></svg></div>
						</div>
						<div class="joinchat__box__scroll">
							<div class="joinchat__box__content">
								<?php echo $box_content; ?>
							</div>
						</div>
						<?php if ( $copy ) : ?>
							<div class="joinchat__copy"><?php echo $copy; ?> <a href="<?php echo $powered_link; ?>" rel="nofollow noopener" target="_blank"><svg><use href="#joinchat_svg__joinchat"></use></svg></a></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
			$html_output = ob_get_clean();

			echo apply_filters( 'joinchat_html_output', $html_output, $this->settings );
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

	/**
	 * Hide on Elementor preview mode.
	 * Set 'show' false when is editing on Elementor
	 *
	 * @since    2.2.3
	 * @param    object      /Elementor/Preview instance
	 */
	public function elementor_preview_disable( $elementor_preview ) {

		$this->show = apply_filters( 'joinchat_elementor_preview_show', false );

	}

}
