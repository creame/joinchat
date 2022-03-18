<?php

/**
 * Fornt and Back Common class.
 *
 * @since      4.2.0
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChatCommon {

	/**
	 * Initialize the class.
	 *
	 * @since    4.2.0
	 */
	public function __construct() {}

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
			'dark_mode'     => 'no',
			'header'        => '__jc__', // values: '__jc__', '__wa__' or other custom text
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
	 * @return array
	 */
	public function load_settings() {

		$default_settings = $this->default_settings();

		// Can hook 'option_joinchat' and 'default_option_joinchat' filters
		$settings = array_merge( $default_settings, (array) get_option( 'joinchat', $default_settings ) );

		// Migrate addons 'remove_brand' setting to 'header' (v. < 4.1)
		if ( isset( $settings['remove_brand'] ) ) {
			$remove             = $settings['remove_brand'];
			$settings['header'] = 'wa' == $remove ? '__wa__' : ( 'no' == $remove ? '__jc__' : '' );
		}

		// Clean unused saved settings
		$settings = array_intersect_key( $settings, $default_settings );

		return $settings;

	}

}
