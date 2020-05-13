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

		// Set a simple CTA hash, empty '' if no CTA (for javascript store viewed CTAs)
		$this->settings['message_hash'] = ltrim( hash( 'crc32', $this->settings['message_text'] ), '0' );

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
			$min             = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
			$color           = $this->settings['color'];
			list($r, $g, $b) = sscanf( $color, '#%02x%02x%02x' );

			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . "css/{$this->plugin_name}{$min}.css", array(), $this->version, 'all' );
			wp_add_inline_style( $this->plugin_name, apply_filters( 'joinchat_inline_style', ".joinchat{ --red:$r; --green:$g; --blue:$b; }", $color ) );
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
					'message_text',
					'color',
					'dark_mode',
				)
			);

			$data = array_diff_key( $this->settings, array_flip( $excluded_fields ) );

			$copy = apply_filters( 'joinchat_copy', 'Powered by' );

			$powered_url  = urlencode( home_url( $wp->request ) );
			$powered_site = urlencode( get_bloginfo( 'name' ) );
			$powered_lang = _x( 'en', 'url lang slug (only available for spanish "es")', 'creame-whatsapp-me' );
			$powered_link = "https://join.chat/{$powered_lang}/powered/?site={$powered_site}&url={$powered_url}";

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
			?>
			<div class="joinchat <?php echo apply_filters( 'joinchat_classes', $joinchat_classes ); ?>" data-settings="<?php echo esc_attr( json_encode( $data ) ); ?>">
				<div class="joinchat__button">
					<div class="joinchat__button__open"></div>
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
							<?php if ( $copy ) : ?>
								<a class="joinchat__copy" href="<?php echo $powered_link; ?>" rel="nofollow noopener" target="_blank">
									<?php echo $copy; ?> <svg viewbox="0 0 1424 318"><title>Join.chat</title><path d="M170.93 7c1.395 0 3.255.583 5.58 1.75 2.325 1.165 3.487 2.331 3.487 3.497l-.013.532-.03.662c-.042.827-.115 2.012-.22 3.554l-.574 8.06c-.418 6.108-.837 14.2-1.255 24.275-.415 9.985-.645 20.527-.69 31.626l.002 31.293.027 5.908c.027 4.503.072 9.813.136 15.928l.265 23.666c.127 12.388.19 22.877.19 31.466 0 21.982-5.813 42.824-17.44 62.525-11.628 19.701-27.876 35.67-48.743 47.905S67.997 318 43.289 318c-13.912 0-24.605-2.748-32.08-8.243-7.475-5.496-11.212-13.22-11.212-23.175 0-7.258 2.336-13.48 7.008-18.664 4.671-5.185 10.952-7.777 18.842-7.777 6.852 0 13.081 1.97 18.688 5.91 5.412 3.805 9.664 7.947 12.754 12.428l.326.482a96.787 96.787 0 0010.278 12.91c3.738 3.94 7.164 5.91 10.278 5.91 3.945 0 7.164-2.023 9.655-6.066 2.449-3.975 4.496-11.704 6.143-23.19l.086-.607c1.634-11.63 2.465-27.476 2.491-47.537v-116.21l.103-.075.001-27.831c0-1.537-.206-2.557-.618-3.06l-.08-.089c-.413-.414-1.377-.829-2.892-1.243l-.595-.156-11.856-2.099c-1.86-.233-2.79-2.449-2.79-6.647 0-3.731.93-5.947 2.79-6.647 26.968-10.495 56.145-26.587 87.531-48.277 1.163-.7 2.093-1.049 2.79-1.049zm1205 43c3.926 0 5.992.835 6.199 2.505 1.24 9.605 2.066 21.819 2.48 36.642h.488c3.02-.005 8.54-.058 16.557-.156 7.836-.097 13.55-.149 17.144-.156h.832c1.653 0 2.79.678 3.41 2.035s.929 4.019.929 7.986-.31 6.524-.93 7.673c-.62 1.148-1.756 1.722-3.409 1.722h-1.912c-15.123-.008-26.056-.113-32.8-.313v62.01c0 13.78 1.705 23.279 5.114 28.499 3.41 5.22 8.73 7.829 15.961 7.829 1.447 0 2.996-.313 4.65-.94 1.652-.626 2.685-.94 3.098-.94 1.86 0 3.72.993 5.58 2.976 1.859 1.984 2.479 3.706 1.859 5.168-4.133 10.648-11.468 19.886-22.005 27.716-10.538 7.83-22.625 11.744-36.262 11.744-16.116 0-28.41-4.854-36.881-14.563-3.314-3.798-5.98-8.164-7.998-13.097l-.422.42-.568.56c-17.407 17.12-32.986 25.68-46.738 25.68-18.674 0-31.745-13.069-39.215-39.206-4.98 12.348-11.982 21.97-21.007 28.864-9.026 6.895-19.244 10.342-30.656 10.342-11.826 0-21.526-4.168-29.1-12.503-7.572-8.335-11.359-18.574-11.359-30.717 0-9.467 1.66-17.133 4.98-22.999 3.32-5.865 9.025-10.959 17.117-15.281 13.14-6.924 35.318-13.848 66.536-20.771l1-.221v-10.617c-.072-10.763-1.731-19.264-4.977-25.503-3.32-6.38-7.884-9.57-13.694-9.57-2.82 0-4.466 1.551-4.94 4.653l-.04.287-2.178 14.818-.088.638c-1.512 10.59-5.217 18.557-11.116 23.904-6.017 5.454-13.486 8.181-22.408 8.181-5.187 0-9.544-1.543-13.072-4.63-3.527-3.088-5.29-7.307-5.29-12.658 0-10.702 8.766-21.712 26.298-33.032S1214.6 88 1237.007 88c41.082 0 61.829 15.23 62.24 45.688l.01.928v57.47c.019 4.635.226 8.426.622 11.372.415 3.087.986 5.454 1.712 7.1.726 1.647 1.66 2.676 2.8 3.088 1.142.411 2.335.411 3.58 0 1.245-.412 2.8-1.235 4.668-2.47.682-.507 1.224-.806 1.625-.896-.622-4.09-.932-8.452-.932-13.086v-85.811c0-1.462-.207-2.401-.62-2.819-.413-.417-1.446-.835-3.1-1.252l-11.157-1.566c-1.653-.209-2.479-2.297-2.479-6.264 0-4.384.826-6.681 2.48-6.89 15.909-3.758 29.03-8.664 39.36-14.72 10.331-6.054 20.662-14.51 30.993-25.367 1.653-1.67 4.029-2.505 7.128-2.505zM290.13 88c27.5 0 49.688 7.203 66.563 21.61 16.875 14.406 25.312 33.958 25.312 58.655 0 25.726-9.01 45.947-27.031 60.662S313.255 251 283.88 251c-27.5 0-49.688-7.203-66.563-21.61-16.874-14.406-25.312-33.958-25.312-58.655 0-25.726 9.01-45.947 27.031-60.662S260.755 88 290.13 88zm588.15 0c18.56 0 33.407 4.116 44.542 12.348 11.136 8.233 16.704 17.803 16.704 28.71 0 6.175-2.166 11.269-6.496 15.282s-9.898 6.02-16.703 6.02c-12.992 0-24.024-8.541-33.098-25.623-5.568-10.496-9.847-17.34-12.837-20.53s-6.238-4.785-9.743-4.785c-7.424 0-11.136 5.454-11.136 16.362 0 13.583 3.093 28.247 9.28 43.992 6.186 15.744 13.92 28.247 23.199 37.508 8.042 8.027 16.497 12.04 25.364 12.04 7.63 0 15.363-3.293 23.2-9.879 1.443-1.029 3.505-.617 6.186 1.235 2.68 1.852 3.712 3.602 3.093 5.248-5.155 12.349-14.744 22.948-28.767 31.797-14.022 8.85-30.21 13.275-48.563 13.275-23.303 0-42.377-7.41-57.225-22.227-14.847-14.818-22.271-34.164-22.271-58.038 0-24.491 8.97-44.403 26.911-59.736C827.86 95.666 850.647 88 878.28 88zm-402.36-2.78c1.228 0 2.864.52 4.91 1.56 2.044 1.039 3.067 2.079 3.067 3.119 0 .832-.205 4.055-.614 9.67-.409 5.616-.818 13.415-1.227 23.398-.385 9.395-.589 19.344-.611 29.845l-.002 1.975v74.247l.004.246c.076 2.265 1.221 3.624 3.436 4.077l.241.045 10.43 2.184.135.022c.142.028.277.074.405.135.125-.045.257-.076.394-.093l10.534-2.174.244-.045c2.316-.467 3.474-1.9 3.474-4.301v-81.921c-.024-1.298-.23-2.14-.617-2.529-.414-.414-1.446-.828-3.099-1.242l-10.534-1.863-.148-.023c-1.554-.305-2.331-2.263-2.331-5.876 0-3.312.826-5.278 2.479-5.899 21.069-8.28 45.856-22.561 74.36-42.846.827-.62 1.653-.931 2.48-.931 1.239 0 2.891.517 4.957 1.552s3.098 2.07 3.098 3.105v.07c-.013.815-.22 4.828-.62 12.039a392.8 392.8 0 00-.619 21.733c4.544-10.142 11.722-18.784 21.534-25.925 9.811-7.14 21.12-10.711 33.927-10.711 16.318 0 29.177 4.657 38.575 13.971 9.399 9.315 14.098 22.355 14.098 39.12v88.42c.08 2.335 1.318 3.702 3.714 4.102l10.534 2.174.136.022c1.562.313 2.343 2.582 2.343 6.808 0 4.347-.826 6.52-2.479 6.52h-.08c-1.25-.017-7.576-.38-18.975-1.087-11.67-.724-21.947-1.086-30.829-1.086s-18.848.362-29.9 1.086c-11.05.725-17.092 1.087-18.125 1.087-1.652 0-2.478-2.173-2.478-6.52 0-3.933.826-6.21 2.478-6.83l8.366-2.174.303-.078c1.476-.394 2.408-.834 2.795-1.319.413-.517.62-1.5.62-2.95v-61.884c-.066-14.105-2.079-24.007-6.04-29.706-4.028-5.796-11.206-8.693-21.534-8.693-3.098 0-5.37.31-6.816.931v99.636c.025 1.294.231 2.183.617 2.666.413.518 1.446.983 3.098 1.397l8.366 2.174.152.063c1.551.701 2.326 2.957 2.326 6.767 0 4.347-.826 6.52-2.478 6.52h-.085c-1.243-.018-7.205-.38-17.886-1.087-10.948-.724-20.862-1.086-29.744-1.086s-19.21.362-30.984 1.086c-11.774.725-18.177 1.087-19.21 1.087-.165 0-.32-.022-.469-.065-.107.032-.22.052-.337.06l-.127.005h-.08c-1.238-.017-7.5-.38-18.788-1.092-11.555-.728-21.73-1.092-30.525-1.092-8.794 0-19.02.364-30.678 1.092S397.483 249 396.461 249c-1.637 0-2.455-2.184-2.455-6.551 0-4.246.773-6.527 2.32-6.841l.134-.022 10.431-2.184.241-.045c2.215-.453 3.36-1.812 3.436-4.077l.004-.246v-82.046l-.002-.267c-.024-1.304-.228-2.15-.611-2.54-.384-.39-1.306-.78-2.768-1.17l-.3-.079-10.43-1.871-.147-.024c-1.539-.306-2.308-2.273-2.308-5.904 0-3.327.818-5.303 2.454-5.927 23.725-9.359 49.393-23.71 77.003-43.05 1.023-.625 1.84-.937 2.455-.937zM1014.74 10c1.24 0 2.892.513 4.957 1.538 2.066 1.025 3.099 2.05 3.099 3.076 0 .82-.207 3.999-.62 9.535-.413 5.537-.826 13.227-1.24 23.07-.412 9.843-.619 20.3-.619 31.374v42.756l.391-.674c5.136-8.727 12.235-16.09 21.298-22.088 9.295-6.152 19.83-9.228 31.603-9.228 16.318 0 29.177 4.614 38.575 13.842 9.399 9.228 14.098 22.146 14.098 38.757v87.599c.08 2.312 1.318 3.667 3.714 4.063l10.534 2.153.136.022c1.562.31 2.343 2.559 2.343 6.746 0 4.306-.826 6.459-2.479 6.459h-.08c-1.25-.017-7.576-.376-18.975-1.077-11.67-.717-21.947-1.076-30.829-1.076s-18.848.359-29.9 1.076c-11.05.718-17.092 1.077-18.125 1.077-1.652 0-2.478-2.153-2.478-6.46 0-3.896.826-6.151 2.478-6.767l8.366-2.153.303-.077c1.476-.39 2.408-.826 2.795-1.307.413-.512.62-1.487.62-2.922v-61.31c-.066-13.974-2.08-23.784-6.04-29.43-4.028-5.742-11.206-8.613-21.534-8.613-3.098 0-5.37.308-6.816.923v98.711c.025 1.282.231 2.163.617 2.641.413.513 1.446.974 3.098 1.384l8.366 2.153.152.063c1.551.695 2.326 2.93 2.326 6.705 0 4.306-.826 6.459-2.478 6.459h-.085c-1.243-.018-7.205-.376-17.886-1.077-10.948-.717-20.862-1.076-29.744-1.076s-19.21.359-30.984 1.076c-11.774.718-18.177 1.077-19.21 1.077-1.653 0-2.479-2.153-2.479-6.46 0-4.306.826-6.561 2.479-6.767l10.534-2.153.244-.044c2.316-.463 3.474-1.883 3.474-4.262V70.624c-.026-1.277-.232-2.106-.617-2.489-.414-.41-1.446-.82-3.099-1.23l-10.534-1.846-.148-.023c-1.554-.302-2.331-2.242-2.331-5.821 0-3.281.826-5.23 2.479-5.844 23.96-9.228 49.884-23.377 77.77-42.448 1.032-.615 1.858-.923 2.478-.923zM271.77 99.927c-7.676 0-11.514 6.807-11.514 20.42 0 16.503 3.734 38.213 11.203 65.131 7.468 26.919 14.52 43.679 21.159 50.28 3.112 3.093 6.327 4.64 9.646 4.64 7.676 0 11.514-6.807 11.514-20.42 0-16.502-3.734-38.213-11.203-65.131-7.468-26.919-14.52-43.678-21.159-50.279-3.112-3.094-6.327-4.641-9.646-4.641zm939.17 64.935c-6.093 0-9.14 4.29-9.14 12.873 0 8.378 2.364 15.837 7.092 22.375 4.727 6.54 9.823 9.809 15.286 9.809 2.196 0 4.012-.646 5.45-1.937l.223-.209v-22.228c-.114-5.728-2.318-10.681-6.615-14.86-3.992-3.882-8.09-5.823-12.292-5.823zM450.63.002c10.302 0 18.802 3.439 25.499 10.317 6.697 6.877 10.045 15.422 10.045 25.635 0 10.212-3.4 18.757-10.2 25.635-6.593 6.878-15.042 10.317-25.344 10.317-10.303 0-18.803-3.44-25.5-10.317-6.696-6.878-10.045-15.423-10.045-25.635 0-10.213 3.349-18.758 10.045-25.635C431.827 3.441 440.327.002 450.63.002zm297.39 249c8.835 0 16.17-2.736 22.008-8.208 5.995-5.472 8.992-12.236 8.992-20.292s-2.958-14.82-8.874-20.292-13.291-8.208-22.126-8.208-16.21 2.736-22.126 8.208-8.874 12.236-8.874 20.292 2.958 14.82 8.874 20.292 13.291 8.208 22.126 8.208z"/></svg>
								</a>
							<?php endif; ?>
							<div class="joinchat__close" title="<?php _e( 'Close', 'creame-whatsapp-me' ); ?>"></div>
						</div>
						<div class="joinchat__box__scroll">
							<div class="joinchat__box__content">
								<?php echo $box_content; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>
				<svg height="0" width="0"><defs><clipPath id="joinchat__message__peak"><path d="M17 25V0C17 12.877 6.082 14.9 1.031 15.91c-1.559.31-1.179 2.272.004 2.272C9.609 18.182 17 18.088 17 25z"/></clipPath></defs></svg>
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
