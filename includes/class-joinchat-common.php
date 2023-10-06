<?php
/**
 * Front and back common functionality.
 *
 * @package    Joinchat
 */

/**
 * Front and Back Common class.
 *
 * @since      4.2.0
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Common {

	/**
	 * International Telephone Input library version.
	 *
	 * @since    4.5.10
	 */
	const INTL_TEL_INPUT_VERSION = '18.1.8';

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
	 * Require QR Script on front.
	 *
	 * @since    4.5.0
	 * @var bool
	 */
	public $qr = false;

	/**
	 * Is joinchat preview
	 *
	 * @since    5.0.0
	 * @var bool
	 */
	public $preview = false;

	/**
	 * Instantiates Manager.
	 *
	 * @since    4.5.0
	 * @return Joinchat_Common
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
	 * @since    5.0.0 Ensure load settings only once.
	 */
	private function __construct() {

		add_action( 'admin_init', array( $this, 'load_settings' ), 5 );
		add_action( 'wp', array( $this, 'load_settings' ) );

	}

	/**
	 * Return the default settings.
	 *
	 * @since    4.2.0  default_settings()
	 * @since    5.0.0  renamed to defaults() & added $key param.
	 * @param  string|false $key  Setting key or false.
	 * @return mixed
	 */
	public function defaults( $key = false ) {

		$defaults = array(
			'telephone'     => '',
			'mobile_only'   => 'no',
			'button_image'  => '',
			'button_tip'    => '',
			'button_delay'  => 3,
			'whatsapp_web'  => 'no',
			'qr'            => 'no',
			'qr_text'       => __( 'Scan the code', 'creame-whatsapp-me' ),
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
			'custom_css'    => '',
			'clear'         => 'no',
		);

		$defaults = array_merge( $defaults, apply_filters( 'joinchat_extra_settings', array() ) );

		if ( empty( $key ) ) {
			return $defaults;
		}

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : false;

	}

	/**
	 * Load saved settings.
	 *
	 * @since    4.2.0
	 * @since    4.5.7  Intitialize intltel.
	 * @since    5.0.0  Only run once and add filter 'joinchat_settings'
	 * @return array
	 */
	public function load_settings() {

		if ( ! is_null( $this->settings ) ) {
			return $this->settings;
		}

		$default_settings = $this->defaults();

		// Can hook 'option_joinchat' and 'default_option_joinchat' filters.
		$settings = array_merge( $default_settings, (array) get_option( 'joinchat', $default_settings ) );

		// Migrate addons 'remove_brand' setting to 'header' (v. < 4.1).
		if ( isset( $settings['remove_brand'] ) ) {
			$remove             = $settings['remove_brand'];
			$settings['header'] = 'wa' === $remove ? '__wa__' : ( 'no' === $remove ? '__jc__' : '' );
		}

		// Clean unused saved settings.
		$settings = array_intersect_key( $settings, $default_settings );

		$this->settings = apply_filters( 'joinchat_settings', $settings );

		return $this->settings;

	}

	/**
	 * Get International Telephone Input library version
	 *
	 * Return IntlTelInput library version or false to disable.
	 *
	 * @since    4.5.10
	 * @return string|false
	 */
	public function get_intltel() {

		return apply_filters( 'joinchat_enhanced_phone', self::INTL_TEL_INPUT_VERSION );

	}

	/**
	 * Get public custom post types
	 *
	 * Custom post types with public url.
	 *
	 * @since    4.5.17
	 * @return array
	 */
	public function get_custom_post_types() {

		return apply_filters( 'joinchat_custom_post_types', array_keys( get_post_types( array( 'has_archive' => true ), 'names' ) ) );

	}

	/**
	 * Get public post_types
	 *
	 * @since    4.5.0
	 * @return array
	 */
	public function get_public_post_types() {

		$builtin_post_types = array( 'post', 'page' );        // Built-in post types.
		$custom_post_types  = $this->get_custom_post_types(); // Custom post types with public url.

		// Add/remove posts types for "Joinchat" meta box.
		return apply_filters( 'joinchat_post_types_meta_box', array_merge( $builtin_post_types, $custom_post_types ) );

	}

	/**
	 * Get taxonomies to include Joinchat meta box
	 *
	 * @since    5.0.9
	 * @return array
	 */
	public function get_taxonomies_meta_box() {

		return apply_filters( 'joinchat_taxonomies_meta_box', array( 'category', 'post_tag' ) );

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


/**
 * Returns the One True Instance of Joinchat_Common.
 *
 * @since 5.0.0
 * @return Joinchat_Common
 */
function jc_common() {

	return Joinchat_Common::instance();

}
