<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @since      3.0.0      Added $show property and more hooks
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/public
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_Public {

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
			),
			apply_filters( 'whatsappme_extra_settings', array() )
		);

		$settings = $default_settings;
		$show     = false;

		$site_settings = get_option( 'whatsappme' );

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
			$settings_i18n = WhatsAppMe_Util::settings_i18n();

			foreach ( $settings_i18n as $key => $label ) {
				$settings[ $key ] = $settings[ $key ] ? apply_filters( 'wpml_translate_single_string', $settings[ $key ], 'WhatsApp me', $label ) : '';
			}

			// Filter for site settings (can be overriden by post settings)
			$settings = apply_filters( 'whatsappme_get_settings_site', $settings, $obj );

			// Post custom settings override site settings
			$post_settings = is_a( $obj, 'WP_Post' ) ? get_post_meta( $obj->ID, '_whatsappme', true ) : '';

			if ( is_array( $post_settings ) ) {
				// Move old 'hide' to new 'view' field
				if ( isset( $post_settings['hide'] ) ) {
					$post_settings['view'] = 'no';
					unset( $post_settings['hide'] );
				}

				$settings = array_merge( $settings, $post_settings );
			}

			// Prepare settings
			$settings['telephone']     = preg_replace( '/^0+|\D/', '', $settings['telephone'] );
			$settings['mobile_only']   = 'yes' == $settings['mobile_only'];
			$settings['whatsapp_web']  = 'yes' == $settings['whatsapp_web'];
			$settings['message_badge'] = 'yes' == $settings['message_badge'] && '' != $settings['message_text'];
			$settings['position']      = 'right' == $settings['position'] ? 'right' : 'left';
			$settings['message_send']  = WhatsAppMe_Util::replace_variables( $settings['message_send'] );
			// Set true to link http://web.whatsapp.com instead http://api.whatsapp.com
			$settings['whatsapp_web'] = apply_filters( 'whatsappme_whatsapp_web', 'yes' == $settings['whatsapp_web'] );

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
		$this->settings = apply_filters( 'whatsappme_get_settings', $settings, $obj );
		// Apply filters to alter 'show' value
		$this->show = apply_filters( 'whatsappme_show', $show, $this->settings, $obj );

		// Ensure not show if not phone
		if ( '' == $this->settings['telephone'] ) {
			$this->show = false;
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @return   void
	 */
	public function enqueue_styles() {

		if ( $this->show ) {
			$styles = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'whatsappme.css' : 'whatsappme.min.css';
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/' . $styles, array(), $this->version, 'all' );
		}

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 * @since    2.2.2     minified
	 * @return   void
	 */
	public function enqueue_scripts() {

		if ( $this->show ) {
			$script = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'whatsappme.js' : 'whatsappme.min.js';
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/' . $script, array( 'jquery' ), $this->version, true );
		}

	}

	/**
	 * Outputs WhatsApp button html and his settings on footer
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function footer_html() {
		global $wp;

		if ( $this->show ) {

			// Clean unnecessary settings on front
			$excluded_fields = apply_filters(
				'whatsappme_excluded_fields', array(
					'visibility',
					'position',
					'button_tip',
					'button_image',
					'message_start',
				)
			);

			$data = array_diff_key( $this->settings, array_flip( $excluded_fields ) );

			$copy = apply_filters( 'whatsappme_copy', 'Powered by' );

			$powered_url  = urlencode( home_url( $wp->request ) );
			$powered_site = urlencode( get_bloginfo( 'name' ) );
			$powered_link = "https://wame.chat/powered/?site={$powered_site}&url={$powered_url}";

			// Set custom img tag and bypass default image logic
			$image = apply_filters( 'whatsappme_image', null );

			if ( is_null( $image ) && $this->settings['button_image'] ) {
				$img_path = get_attached_file( $this->settings['button_image'] );

				if ( apply_filters( 'whatsappme_image_original', WhatsAppMe_Util::is_animated_gif( $img_path ) ) ) {
					$image = '<img src="' . wp_get_attachment_url( $this->settings['button_image'] ) . '" alt="">';
				} elseif ( is_array( WhatsAppMe_Util::thumb( $img_path, 58, 58 ) ) ) {
					$image = '<img src="' . WhatsAppMe_Util::thumb( $img_path, 58, 58 )['url'] . '" srcset="' .
						WhatsAppMe_Util::thumb( $img_path, 116, 116 )['url'] . ' 2x, ' .
						WhatsAppMe_Util::thumb( $img_path, 174, 174 )['url'] . ' 3x" alt="">';
				}
			}

			?>
			<div class="whatsappme <?php echo apply_filters( 'whatsappme_classes', "whatsappme--{$this->settings['position']}" ); ?>" data-settings="<?php echo esc_attr( json_encode( $data ) ); ?>">
				<div class="whatsappme__button">
					<svg class="whatsappme__button__open" viewBox="0 0 24 24"><path fill="#fff" d="M3.516 3.516c4.686-4.686 12.284-4.686 16.97 0 4.686 4.686 4.686 12.283 0 16.97a12.004 12.004 0 01-13.754 2.299l-5.814.735a.392.392 0 01-.438-.44l.748-5.788A12.002 12.002 0 013.517 3.517zm3.61 17.043l.3.158a9.846 9.846 0 0011.534-1.758c3.843-3.843 3.843-10.074 0-13.918-3.843-3.843-10.075-3.843-13.918 0a9.846 9.846 0 00-1.747 11.554l.16.303-.51 3.942a.196.196 0 00.219.22l3.961-.501zm6.534-7.003l-.933 1.164a9.843 9.843 0 01-3.497-3.495l1.166-.933a.792.792 0 00.23-.94L9.561 6.96a.793.793 0 00-.924-.445 1291.6 1291.6 0 00-2.023.524.797.797 0 00-.588.88 11.754 11.754 0 0010.005 10.005.797.797 0 00.88-.587l.525-2.023a.793.793 0 00-.445-.923L14.6 13.327a.792.792 0 00-.94.23z"/></svg>
					<?php if ( $image ) : ?>
						<div class="whatsappme__button__image"><?php echo $image; ?></div>
					<?php endif; ?>
					<?php if ( $this->settings['message_start'] ) : ?>
						<div class="whatsappme__button__sendtext"><?php echo $this->settings['message_start']; ?></div>
					<?php endif; ?>
					<svg class="whatsappme__button__send" viewbox="0 0 400 400" fill="none" fill-rule="evenodd" stroke="#fff" stroke-linecap="round" stroke-width="33">
						<path class="wame_plain" stroke-dasharray="1096.67" stroke-dashoffset="1096.67" d="M168.83 200.504H79.218L33.04 44.284a1 1 0 0 1 1.386-1.188L365.083 199.04a1 1 0 0 1 .003 1.808L34.432 357.903a1 1 0 0 1-1.388-1.187l29.42-99.427"/>
						<path class="wame_chat" stroke-dasharray="1019.22" stroke-dashoffset="1019.22" d="M318.087 318.087c-52.982 52.982-132.708 62.922-195.725 29.82l-80.449 10.18 10.358-80.112C18.956 214.905 28.836 134.99 81.913 81.913c65.218-65.217 170.956-65.217 236.174 0 42.661 42.661 57.416 102.661 44.265 157.316"/>
					</svg>
					<?php if ( $this->settings['message_badge'] ) : ?>
						<div class="whatsappme__badge">1</div>
					<?php endif; ?>
					<?php if ( $this->settings['button_tip'] ) : ?>
						<div class="whatsappme__tooltip"><div><?php echo $this->settings['button_tip']; ?></div></div>
					<?php endif; ?>
				</div>
				<?php if ( $this->settings['message_text'] ) : ?>
					<div class="whatsappme__box">
						<div class="whatsappme__header">
							<svg viewBox="0 0 120 28"><path fill="#fff" fill-rule="evenodd" d="M117.2 17c0 .4-.2.7-.4 1-.1.3-.4.5-.7.7l-1 .2c-.5 0-.9 0-1.2-.2l-.7-.7a3 3 0 0 1-.4-1 5.4 5.4 0 0 1 0-2.3c0-.4.2-.7.4-1l.7-.7a2 2 0 0 1 1.1-.3 2 2 0 0 1 1.8 1l.4 1a5.3 5.3 0 0 1 0 2.3zm2.5-3c-.1-.7-.4-1.3-.8-1.7a4 4 0 0 0-1.3-1.2c-.6-.3-1.3-.4-2-.4-.6 0-1.2.1-1.7.4a3 3 0 0 0-1.2 1.1V11H110v13h2.7v-4.5c.4.4.8.8 1.3 1 .5.3 1 .4 1.6.4a4 4 0 0 0 3.2-1.5c.4-.5.7-1 .8-1.6.2-.6.3-1.2.3-1.9s0-1.3-.3-2zm-13.1 3c0 .4-.2.7-.4 1l-.7.7-1.1.2c-.4 0-.8 0-1-.2-.4-.2-.6-.4-.8-.7a3 3 0 0 1-.4-1 5.4 5.4 0 0 1 0-2.3c0-.4.2-.7.4-1 .1-.3.4-.5.7-.7a2 2 0 0 1 1-.3 2 2 0 0 1 1.9 1l.4 1a5.4 5.4 0 0 1 0 2.3zm1.7-4.7a4 4 0 0 0-3.3-1.6c-.6 0-1.2.1-1.7.4a3 3 0 0 0-1.2 1.1V11h-2.6v13h2.7v-4.5c.3.4.7.8 1.2 1 .6.3 1.1.4 1.7.4a4 4 0 0 0 3.2-1.5c.4-.5.6-1 .8-1.6.2-.6.3-1.2.3-1.9s-.1-1.3-.3-2c-.2-.6-.4-1.2-.8-1.6zm-17.5 3.2l1.7-5 1.7 5h-3.4zm.2-8.2l-5 13.4h3l1-3h5l1 3h3L94 7.3h-3zm-5.3 9.1l-.6-.8-1-.5a11.6 11.6 0 0 0-2.3-.5l-1-.3a2 2 0 0 1-.6-.3.7.7 0 0 1-.3-.6c0-.2 0-.4.2-.5l.3-.3h.5l.5-.1c.5 0 .9 0 1.2.3.4.1.6.5.6 1h2.5c0-.6-.2-1.1-.4-1.5a3 3 0 0 0-1-1 4 4 0 0 0-1.3-.5 7.7 7.7 0 0 0-3 0c-.6.1-1 .3-1.4.5l-1 1a3 3 0 0 0-.4 1.5 2 2 0 0 0 1 1.8l1 .5 1.1.3 2.2.6c.6.2.8.5.8 1l-.1.5-.4.4a2 2 0 0 1-.6.2 2.8 2.8 0 0 1-1.4 0 2 2 0 0 1-.6-.3l-.5-.5-.2-.8H77c0 .7.2 1.2.5 1.6.2.5.6.8 1 1 .4.3.9.5 1.4.6a8 8 0 0 0 3.3 0c.5 0 1-.2 1.4-.5a3 3 0 0 0 1-1c.3-.5.4-1 .4-1.6 0-.5 0-.9-.3-1.2zM74.7 8h-2.6v3h-1.7v1.7h1.7v5.8c0 .5 0 .9.2 1.2l.7.7 1 .3a7.8 7.8 0 0 0 2 0h.7v-2.1a3.4 3.4 0 0 1-.8 0l-1-.1-.2-1v-4.8h2V11h-2V8zm-7.6 9v.5l-.3.8-.7.6c-.2.2-.7.2-1.2.2h-.6l-.5-.2a1 1 0 0 1-.4-.4l-.1-.6.1-.6.4-.4.5-.3a4.8 4.8 0 0 1 1.2-.2 8.3 8.3 0 0 0 1.2-.2l.4-.3v1zm2.6 1.5v-5c0-.6 0-1.1-.3-1.5l-1-.8-1.4-.4a10.9 10.9 0 0 0-3.1 0l-1.5.6c-.4.2-.7.6-1 1a3 3 0 0 0-.5 1.5h2.7c0-.5.2-.9.5-1a2 2 0 0 1 1.3-.4h.6l.6.2.3.4.2.7c0 .3 0 .5-.3.6-.1.2-.4.3-.7.4l-1 .1a21.9 21.9 0 0 0-2.4.4l-1 .5c-.3.2-.6.5-.8.9-.2.3-.3.8-.3 1.3s.1 1 .3 1.3c.1.4.4.7.7 1l1 .4c.4.2.9.2 1.3.2a6 6 0 0 0 1.8-.2c.6-.2 1-.5 1.5-1a4 4 0 0 0 .2 1H70l-.3-1v-1.2zm-11-6.7c-.2-.4-.6-.6-1-.8-.5-.2-1-.3-1.8-.3-.5 0-1 .1-1.5.4a3 3 0 0 0-1.3 1.2v-5h-2.7v13.4H53v-5.1c0-1 .2-1.7.5-2.2.3-.4.9-.6 1.6-.6.6 0 1 .2 1.3.6.3.4.4 1 .4 1.8v5.5h2.7v-6c0-.6 0-1.2-.2-1.6 0-.5-.3-1-.5-1.3zm-14 4.7l-2.3-9.2h-2.8l-2.3 9-2.2-9h-3l3.6 13.4h3l2.2-9.2 2.3 9.2h3l3.6-13.4h-3l-2.1 9.2zm-24.5.2L18 15.6c-.3-.1-.6-.2-.8.2A20 20 0 0 1 16 17c-.2.2-.4.3-.7.1-.4-.2-1.5-.5-2.8-1.7-1-1-1.7-2-2-2.4-.1-.4 0-.5.2-.7l.5-.6.4-.6v-.6L10.4 8c-.3-.6-.6-.5-.8-.6H9c-.2 0-.6.1-.9.5C7.8 8.2 7 9 7 10.7c0 1.7 1.3 3.4 1.4 3.6.2.3 2.5 3.7 6 5.2l1.9.8c.8.2 1.6.2 2.2.1.6-.1 2-.8 2.3-1.6.3-.9.3-1.5.2-1.7l-.7-.4zM14 25.3c-2 0-4-.5-5.8-1.6l-.4-.2-4.4 1.1 1.2-4.2-.3-.5A11.5 11.5 0 0 1 22.1 5.7 11.5 11.5 0 0 1 14 25.3zM14 0A13.8 13.8 0 0 0 2 20.7L0 28l7.3-2A13.8 13.8 0 1 0 14 0z"/></svg>
							<div class="whatsappme__close"><svg viewBox="0 0 24 24"><path fill="#fff" d="M24 2.4L21.6 0 12 9.6 2.4 0 0 2.4 9.6 12 0 21.6 2.4 24l9.6-9.6 9.6 9.6 2.4-2.4-9.6-9.6L24 2.4z"/></svg></div>
						</div>
						<div class="whatsappme__message"><div class="whatsappme__message__wrap"><div class="whatsappme__message__content"><?php echo WhatsAppMe_Util::formated_message( $this->settings['message_text'] ); ?></div></div></div>
						<?php if ( $copy ) : ?>
							<div class="whatsappme__copy"><?php echo $copy; ?> <a href="<?php echo $powered_link; ?>" rel="nofollow noopener" target="_blank"><svg viewBox="0 0 72 17"><path fill="#fff" fill-rule="evenodd" d="M25.371 10.429l2.122-6.239h.045l2.054 6.239h-4.22zm32.2 2.397c-.439.495-.88.953-1.325 1.375-.797.755-1.332 1.232-1.604 1.43-.622.438-1.156.706-1.604.805-.447.1-.787.13-1.02.09a3.561 3.561 0 0 1-.7-.239c-.66-.318-1.02-.864-1.079-1.64-.058-.774.03-1.619.263-2.533.35-1.987 1.108-4.133 2.274-6.438a73.481 73.481 0 0 0-2.8 3.04c-.816.954-1.7 2.096-2.653 3.428a44.068 44.068 0 0 0-2.77 4.441c-.738 0-1.341-.159-1.808-.477-.427-.278-.748-.695-.962-1.252-.214-.556-.165-1.41.146-2.563l.204-.626c.097-.298.204-.606.32-.924.117-.318.234-.626.35-.924.117-.298.195-.507.234-.626v.06c.272-.756.603-1.56.991-2.415a56.92 56.92 0 0 1 1.4-2.832 62.832 62.832 0 0 0-3.266 3.875 61.101 61.101 0 0 0-2.945 3.995 57.072 57.072 0 0 0-2.886 4.71c-.387 0-.736-.044-1.048-.131l.195.545h-3.72l-1.23-3.786h-6.093L23.158 17h-3.605l6.16-17h3.674l4.357 12.16c.389-1.35.97-2.736 1.74-4.16a41.336 41.336 0 0 0 2.013-4.232.465.465 0 0 0 .058-.18c0-.039.02-.098.058-.178.04-.08.078-.199.117-.358.039-.159.097-.337.175-.536.039-.12.078-.219.117-.298a.465.465 0 0 0 .058-.18c.078-.277.175-.575.292-.893.116-.318.194-.597.233-.835V.25c-.039-.04-.039-.08 0-.119l.233-.12c.117-.039.292.02.525.18.156.08.292.179.408.298.272.199.564.427.875.685.311.259.583.557.816.895a2.9 2.9 0 0 1 .467 1.043c.078.358.039.735-.117 1.133a8.127 8.127 0 0 1-.35.775c0 .08-.038.159-.116.238a2.93 2.93 0 0 1-.175.298 7.05 7.05 0 0 0-.35.656c-.039.04-.058.07-.058.09 0 .02-.02.05-.059.089a61.988 61.988 0 0 1-1.633 2.385c-.544.755-.913 1.35-1.108 1.788a79.39 79.39 0 0 1 3.5-4.233 101.59 101.59 0 0 1 3.12-3.398C45.651 1.82 46.612.986 47.468.43c.739.278 1.341.596 1.808.954.428.318.768.676 1.02 1.073.253.398.244.835-.029 1.312l-1.4 2.325a36.928 36.928 0 0 0-1.749 3.279 53.748 53.748 0 0 1 1.633-1.848 46.815 46.815 0 0 1 4.024-3.875c.7-.597 1.38-1.113 2.041-1.55.739.278 1.341.596 1.808.953.428.318.768.676 1.02 1.073.253.398.243.835-.029 1.312-.155.318-.408.795-.758 1.43a152.853 152.853 0 0 0-2.04 3.846 97.87 97.87 0 0 0-.467.924c-.35.835-.632 1.55-.846 2.146-.214.597-.282.934-.204 1.014a.63.63 0 0 0 .291-.06c.234-.119.564-.348.992-.685.428-.338.875-.736 1.341-1.193.467-.457.914-.914 1.341-1.37.217-.232.409-.45.575-.657a15.4 15.4 0 0 1 .957-2.514c.34-.696.708-1.333 1.108-1.91.399-.576.778-1.044 1.137-1.402a19.553 19.553 0 0 1 1.796-1.7 32.727 32.727 0 0 1 1.497-1.164 8.821 8.821 0 0 1 1.317-.835C66.292.989 66.83.83 67.269.83c.32 0 .649.11.988.328.34.22.649.478.928.776.28.299.519.607.718.925.2.318.3.557.3.716.04.597-.06 1.253-.3 1.97a7.14 7.14 0 0 1-1.107 2.058 8.534 8.534 0 0 1-1.826 1.76 6.522 6.522 0 0 1-2.395 1.074c-.2.08-.36.06-.48-.06a.644.644 0 0 1-.179-.477c0-.358.14-.616.42-.776.837-.318 1.536-.735 2.095-1.253.559-.517.998-1.034 1.317-1.551.4-.597.699-1.213.898-1.85 0-.199-.09-.308-.27-.328a4.173 4.173 0 0 0-.448-.03 4.83 4.83 0 0 0-1.318.597c-.399.239-.848.577-1.347 1.014-.499.438-1.028 1.015-1.586 1.73-.918 1.154-1.587 2.298-2.006 3.432-.42 1.134-.629 1.979-.629 2.536 0 .915.19 1.482.569 1.7.38.22.728.329 1.048.329.638 0 1.347-.15 2.125-.448a16.248 16.248 0 0 0 2.305-1.104 30.05 30.05 0 0 0 2.126-1.342 27.256 27.256 0 0 0 1.646-1.224c.08-.04.18-.1.3-.179l.24-.12a.54.54 0 0 1 .239-.059c.08 0 .16.02.24.06.08.04.119.16.119.358 0 .239-.08.457-.24.656a19.115 19.115 0 0 1-2.245 1.82 35.445 35.445 0 0 1-2.185 1.403c-.759.437-1.497.855-2.215 1.253a8.461 8.461 0 0 1-1.647.387c-.499.06-.968.09-1.407.09-.998 0-1.796-.16-2.395-.477-.599-.319-1.048-.706-1.347-1.164a4.113 4.113 0 0 1-.599-1.372c-.1-.457-.15-.843-.15-1.161zm-42.354-1.111L17.887 0h3.514L17.02 17h-3.56L10.7 5.428h-.046L7.94 17H4.312L0 0h3.582L6.16 11.571h.045L9.035 0h3.354l2.783 11.715h.045z"/></svg></a></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Check visibility on current page
	 *
	 * @since    2.0.0
	 * @since    3.0.0       Added filter to 'whatsappme_visibility'
	 * @param    array $options    array of visibility settings
	 * @return   boolean     is visible or not on current page
	 */
	public function check_visibility( $options ) {

		// Custom visibility, bypass all checks if not null
		$visibility = apply_filters( 'whatsappme_visibility', null, $options );
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

		$this->show = apply_filters( 'whatsappme_elementor_preview_show', false );

	}

}
