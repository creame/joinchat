<?php

/**
 * Front and Back Common class.
 *
 * @since      4.2.0
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChatCommon {

	/**
	 * Singleton instance.
	 *
	 * @since    4.5.0
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Settings
	 *
	 * @since    4.5.0
	 * @var null|array
	 */
	public $settings = null;

	/**
	 * Intl-tel-input version.
	 *
	 * @since    4.5.0
	 * @var string|false
	 */
	public $intltel = null;

	/**
	 * Require QR Script on front.
	 *
	 * @since    4.5.0
	 * @var bool
	 */
	public $qr = false;

	/**
	 * Instantiates Manager.
	 *
	 * @since    4.5.0
	 * @return JoinChatCommon
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Initialize the class.
	 *
	 * @since    4.2.0
	 */
	public function __construct() {

		$this->intltel = '17.0.17';

	}

	/**
	 * Return the default settings.
	 *
	 * @since    4.2.0
	 * @return array
	 */
	public function default_settings() {

		$defaults = array(
			'telephone'     => '',
			'mobile_only'   => 'no',
			'button_image'  => '',
			'button_tip'    => '',
			'button_delay'  => 3,
			'whatsapp_web'  => 'no',
			'qr'            => 'no',
			'message_text'  => '',
			'message_views' => 2,
			'message_delay' => 10,
			'message_badge' => 'no',
			'message_send'  => '',
			'message_start' => __( 'Open chat', 'creame-whatsapp-me' ),
			'position'      => 'right',
			'visibility'    => array( 'all' => 'yes' ),
			'color'         => '#25d366',
			'dark_mode'     => 'no',     // values: 'no', 'yes' or 'auto'.
			'header'        => '__jc__', // values: '__jc__', '__wa__' or other custom text.
			'optin_text'    => '',
			'optin_check'   => 'no',
			'gads'          => '',
		);

		return array_merge( $defaults, apply_filters( 'joinchat_extra_settings', array() ) );

	}

	/**
	 * Load saved settings.
	 *
	 * @since    4.2.0
	 * @since    4.5.7  Intitialize intltel.
	 * @return array
	 */
	public function load_settings() {

		// Use International Telephone Input library version or false to disable.
		$this->intltel = apply_filters( 'joinchat_enhanced_phone', $this->intltel );

		$default_settings = $this->default_settings();

		// Can hook 'option_joinchat' and 'default_option_joinchat' filters.
		$settings = array_merge( $default_settings, (array) get_option( 'joinchat', $default_settings ) );

		// Migrate addons 'remove_brand' setting to 'header' (v. < 4.1).
		if ( isset( $settings['remove_brand'] ) ) {
			$remove             = $settings['remove_brand'];
			$settings['header'] = 'wa' === $remove ? '__wa__' : ( 'no' === $remove ? '__jc__' : '' );
		}

		// Clean unused saved settings.
		$this->settings = array_intersect_key( $settings, $default_settings );

		return $this->settings;

	}

	/**
	 * Get public post_types
	 *
	 * @since    4.5.0
	 * @return array
	 */
	public function get_public_post_types() {

		// Default post types.
		$builtin_post_types = array( 'post', 'page' );
		// Custom post types with public url.
		$custom_post_types = array_keys( get_post_types( array( 'has_archive' => true ), 'names' ) );

		// Add/remove posts types for "Joinchat" meta box.
		return apply_filters( 'joinchat_post_types_meta_box', array_merge( $builtin_post_types, $custom_post_types ) );

	}

	/**
	 * Get post/term form placeholders
	 *
	 * @since 4.5.0
	 * @param  WP_Post|WP_Term $obj  Current post or term.
	 * @return array
	 */
	public function get_obj_placeholders( $obj ) {

		return apply_filters(
			'joinchat_metabox_placeholders',
			array(
				'telephone'    => $this->settings['telephone'],
				'message_text' => $this->settings['message_text'],
				'message_send' => $this->settings['message_send'],
			),
			$obj,
			$this->settings
		);

	}

	/**
	 * Get post/term dynamic variables for form help text
	 *
	 * @since 4.5.0
	 * @param  WP_Post|WP_Term $obj  Current post or term.
	 * @return array
	 */
	public function get_obj_vars( $obj ) {

		return apply_filters( 'joinchat_metabox_vars', array( 'SITE', 'TITLE', 'URL', 'HREF' ), $obj );

	}

}
