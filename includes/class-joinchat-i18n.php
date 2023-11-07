<?php
/**
 * Internationalization functionality.
 *
 * @package    Joinchat
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_I18n {

	const DOMAIN_GROUP = 'Join.chat'; // TODO: in future change to "Joinchat".

	/**
	 * Initialize the class.
	 *
	 * @since    4.2.0
	 * @param  Joinchat_Loader $loader loader instance.
	 * @return void
	 */
	public function __construct( $loader ) {

		$loader->add_action( 'init', $this, 'load_plugin_textdomain', 11 );

		if ( defined( 'WPML_PLUGIN_PATH' ) || defined( 'POLYLANG_VERSION' ) ) {

			$loader->add_action( 'admin_notices', $this, 'settings_notice' );
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

		if ( is_admin() ) {
			load_plugin_textdomain( 'creame-whatsapp-me', false, dirname( JOINCHAT_BASENAME ) . '/languages' );
		}

	}

	/**
	 * Return list of settings that can be translated
	 *
	 * Note: don't translate string labels to prevent missing translations if
	 * public front lang is different of admin lang
	 *
	 * @since    4.2   (before this was in JoinchatUtil)
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
	 * Show notice with default language info at Joinchat settings page
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function settings_notice() {

		if ( ! Joinchat_Util::is_admin_screen() || count( get_settings_errors( 'joinchat' ) ) ) {
			return;
		}

		$spaces  = '&nbsp;&nbsp;';
		$message = sprintf(
			"<strong>%s$spaces%s (%s)</strong>$spaces%s$spaces<a href=\"%s\">%s</a>",
			$this->default_language_flag(), // Flag <img>.
			esc_html__( 'Default site language', 'creame-whatsapp-me' ),
			esc_html( $this->default_language_name() ),
			esc_html__( 'Settings are defined in the main language', 'creame-whatsapp-me' ),
			esc_url( $this->translations_link() ),
			esc_html__( 'Manage translations', 'creame-whatsapp-me' )
		);

		echo '<div class="notice notice-info"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
		if ( ! $translate_notice ) {
			return;
		}

		// Note: message is wrapped with <strong>...</strong> tags.
		$spaces  = '&nbsp;&nbsp;';
		$message = sprintf(
			"%s$spaces%s (%s)$spaces%s$spaces<a href=\"%s\" class=\"button\">%s</a>",
			$this->default_language_flag(), // Flag <img>.
			esc_html__( 'Default site language', 'creame-whatsapp-me' ),
			esc_html( $this->default_language_name() ),
			esc_html__( 'There are changes in fields that can be translated', 'creame-whatsapp-me' ),
			esc_url( $this->translations_link() ),
			esc_html__( 'Manage translations', 'creame-whatsapp-me' )
		);

		add_settings_error( JOINCHAT_SLUG, 'review_i18n', $message, 'warning' );

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
			if ( ! empty( $settings[ $key ] ) ) {
				$settings[ $key ] = apply_filters( 'wpml_translate_single_string', $settings[ $key ], self::DOMAIN_GROUP, $label );
			}
		}

		return $settings;

	}

	/**
	 * Return strings translations url for WPML/Polylang
	 *
	 * @since 5.0.0
	 * @return string
	 */
	private function translations_link() {

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

		return add_query_arg( $args, admin_url( 'admin.php' ) );

	}

	/**
	 * Return default language name for WPML/Polylang
	 *
	 * @since 5.0.0
	 * @return string
	 */
	private function default_language_name() {

		if ( defined( 'WPML_PLUGIN_PATH' ) ) {
			$default_language = apply_filters( 'wpml_default_language', null );
			$current_language = apply_filters( 'wpml_current_language', null );

			$name = apply_filters( 'wpml_translated_language_name', null, $default_language, $current_language );
		} else {
			$name = pll_default_language( 'name' );
		}

		return $name;

	}

	/**
	 * Return default language flag for WPML/Polylang
	 *
	 * @since 5.0.0
	 * @return string
	 */
	private function default_language_flag() {

		if ( defined( 'WPML_PLUGIN_PATH' ) ) {
			$languages = apply_filters( 'wpml_active_languages', null, array() );
			$language  = $languages[ apply_filters( 'wpml_default_language', null ) ];

			$img = '<img src="' . esc_url( $language['country_flag_url'] ) . '" alt="' . esc_attr( $language['language_code'] ) . '" height="12" width="18" />';
		} else {
			$languages = pll_the_languages( array( 'raw' => 1, 'show_flags' => 1 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			$language  = $languages[ pll_default_language() ];

			$img = $language['flag'];
		}

		return $img;

	}
}
