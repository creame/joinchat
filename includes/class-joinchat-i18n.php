<?php

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChat_i18n {

	const DOMAIN_GROUP = 'Join.chat'; // TODO: in future change to "Joinchat".

	/**
	 * Initialize the class.
	 *
	 * @since    4.2.0
	 * @param  JoinChatLoader $loader loader instance.
	 * @return void
	 */
	public function __construct( $loader ) {

		$loader->add_action( 'init', $this, 'load_plugin_textdomain', 11 );

		if ( defined( 'WPML_PLUGIN_PATH' ) || defined( 'POLYLANG_VERSION' ) ) {

			$loader->add_action( 'joinchat_settings_validation', $this, 'settings_save', 10, 2 );
			$loader->add_filter( 'joinchat_get_settings_site', $this, 'settings_load' );

		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain( 'creame-whatsapp-me', false, dirname( JOINCHAT_BASENAME ) . '/languages' );

	}

	/**
	 * Return list of settings that can be translated
	 *
	 * Note: don't translate string labels to prevent missing translations if
	 * public front lang is different of admin lang
	 *
	 * @since    4.2   (before this was in JoinChatUtil)
	 * @access   private
	 * @param    null|array $settings list of settings.
	 * @return   array setting keys and string names
	 */
	private function settings_i18n( $settings = null ) {

		$localized = array(
			'telephone'     => 'Telephone',
			'button_tip'    => 'Tooltip',
			'message_text'  => 'Call to Action',
			'message_send'  => 'Message',
			'message_start' => 'Button Text',
			'optin_text'    => 'Opt-in Text',
		);

		if ( isset( $settings['header'] ) && ! in_array( $settings['header'], array( '', '__jc__', '__wa__' ), true ) ) {
			$localized['header'] = 'Header';
		}

		return apply_filters( 'joinchat_settings_i18n', $localized, $settings );

	}

	/**
	 * Register strings for translation
	 *
	 * Register traslatable fields and show notice if has changes.
	 * view: https://wpml.org/wpml-hook/wpml_register_single_string/
	 *
	 * @since  4.2
	 * @param  array $settings new values of settings.
	 * @param  array $old_settings old values of settings.
	 * @return void
	 */
	public function settings_save( $settings, $old_settings ) {

		$settings_i18n    = $this->settings_i18n( $settings );
		$default_language = apply_filters( 'wpml_default_language', null );
		$translate_notice = false;

		foreach ( $settings_i18n as $key => $label ) {
			$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
			do_action( 'wpml_register_single_string', self::DOMAIN_GROUP, $label, $value, false, $default_language );

			if ( isset( $old_settings[ $key ] ) && $old_settings[ $key ] !== $value ) {
				$translate_notice = true;
			}
		}

		// Show notice with link to string translations.
		if ( $translate_notice ) {

			if ( defined( 'WPML_PLUGIN_PATH' ) ) {
				$args = array(
					'page'    => 'wpml-string-translation/menu/string-translation.php',
					'context' => self::DOMAIN_GROUP,
				);
			} else {
				$args = array(
					'page'  => 'mlang_strings',
					'group' => self::DOMAIN_GROUP,
					'lang'  => 'all',
				);
			}

			// Note: message is wrapped with <strong>...</strong> tags.
			$message = sprintf(
				'%s</strong>&nbsp;&nbsp;%s&nbsp;&nbsp;<strong><a href="%s">%s</a>',
				/* translators: %s: site language. */
				sprintf( __( 'Default site language (%s)', 'creame-whatsapp-me' ), strtoupper( $default_language ) ),
				__( 'There are changes in fields that can be translated.', 'creame-whatsapp-me' ),
				esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) ),
				__( 'Check translations', 'creame-whatsapp-me' )
			);

			add_settings_error( 'joinchat', 'review_i18n', $message, 'info' );

		}

	}

	/**
	 * Get settings translations for current language
	 *
	 * @since  4.2
	 * @param  array $settings list of settings.
	 * @return array
	 */
	public function settings_load( $settings ) {

		$settings_i18n = $this->settings_i18n( $settings );

		foreach ( $settings_i18n as $key => $label ) {
			if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) {
				$settings[ $key ] = apply_filters( 'wpml_translate_single_string', $settings[ $key ], self::DOMAIN_GROUP, $label );
			}
		}

		return $settings;

	}

}
