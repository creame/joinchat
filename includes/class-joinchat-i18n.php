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
	 * Allow settings translations.
	 *
	 * @since    5.2.4
	 */
	public function init() {

		if ( defined( 'WPML_PLUGIN_PATH' ) || defined( 'POLYLANG_VERSION' ) ) {

			add_action( 'admin_notices', array( $this, 'settings_notice' ) );
			add_action( 'joinchat_settings_validation', array( $this, 'register_translations' ), 10, 3 );
			add_filter( 'joinchat_get_settings_site', array( $this, 'load_translations' ), 20 );

			// Add custom hooks for third party plugins.
			add_action( 'joinchat_register_translations', array( $this, 'register_translations' ), 10, 3 );
			add_filter( 'joinchat_load_translations', array( $this, 'load_translations' ), 10, 2 );

		}

	}

	/**
	 * Return list of settings that can be translated
	 *
	 * Note: don't translate string labels to prevent missing translations if
	 * public front lang is different of admin lang
	 *
	 * @since    4.2   (before this was in JoinchatUtil)
	 * @since    6.0   Add $option parameter.
	 * @access   private
	 * @param     array  $settings list of settings.
	 * @param    string $option  Option name.
	 * @return    array setting keys and string names
	 */
	private function settings_i18n( $settings = array(), $option = JOINCHAT_SLUG ) {

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

		return (array) apply_filters( 'joinchat_settings_i18n', $localized, $settings, $option );

	}

	/**
	 * Get domain for WPML/Polylang strings
	 *
	 * @since  6.0
	 * @param  string $option Option name.
	 * @return string
	 */
	private function get_domain( $option = JOINCHAT_SLUG ) {

		return apply_filters( 'joinchat_i18n_domain', self::DOMAIN_GROUP, $option );

	}

	/**
	 * Show notice with default language info at Joinchat settings page
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function settings_notice() {

		if ( ! Joinchat_Util::is_admin_screen() || count( get_settings_errors( JOINCHAT_SLUG ) ) ) {
			return;
		}

		$need_translation = false;

		// Find untranslated strings on WPML.
		if ( function_exists( 'icl_get_string_translations' ) && defined( 'ICL_TM_COMPLETE' ) ) {
			$strings = wp_list_filter( icl_get_string_translations(), array( 'context' => self::DOMAIN_GROUP ) );

			foreach ( $strings as $string ) {
				if ( $string['status'] !== ICL_TM_COMPLETE ) {
					$need_translation = true;
					break;
				}
			}
		}

		$msg  = esc_html__( 'Settings are defined in the main language', 'creame-whatsapp-me' );
		$msg .= $need_translation ? ' <strong>(' . esc_html__( 'there are untranslated strings', 'creame-whatsapp-me' ) . ')</strong>' : '';

		printf(
			'<div class="notice notice-%s"><p>%s</p></div>',
			$need_translation ? 'warning' : 'info', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$this->language_notice( $msg ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);

	}

	/**
	 * Register strings for translation
	 *
	 * Register traslatable fields and show notice if has changes.
	 * view: https://wpml.org/wpml-hook/wpml_register_single_string/
	 *
	 * @since  4.2
	 * @since  6.0.0  Add $option parameter.
	 * @param  array  $settings new values of settings.
	 * @param  array  $old_settings old values of settings.
	 * @param  string $option option name.
	 * @return void
	 */
	public function register_translations( $settings, $old_settings, $option = JOINCHAT_SLUG ) {

		$settings_i18n = $this->settings_i18n( $settings, $option );

		if ( empty( $settings_i18n ) ) {
			return;
		}

		$default_language = apply_filters( 'wpml_default_language', null );
		$translate_notice = false;
		$domain           = $this->get_domain( $option );

		// Clear WPML cache to ensure new strings registration.
		if ( class_exists( 'WPML_WP_Cache' ) ) {
			$string_cache = new WPML_WP_Cache( 'WPML_Register_String_Filter' );
			$string_cache->flush_group_cache();
			$domain_cache = new WPML_WP_Cache( "WPML_Register_String_Filter::$domain" );
			$domain_cache->flush_group_cache();
		}

		foreach ( $settings_i18n as $key => $label ) {
			$value = isset( $settings[ $key ] ) ? $settings[ $key ] : '';
			do_action( 'wpml_register_single_string', $domain, $label, $value, false, $default_language );

			if ( isset( $old_settings[ $key ] ) && $old_settings[ $key ] !== $value ) {
				$translate_notice = true;
			}
		}

		// Show notice with link to string translations.
		if ( ! $translate_notice ) {
			return;
		}

		// Prevent add notice twice.
		if ( ! empty( wp_list_filter( get_settings_errors( JOINCHAT_SLUG ), array( 'code' => 'review_i18n' ) ) ) ) {
			return;
		}

		// Note: message is wrapped with <strong>...</strong> tags.
		$message = $this->language_notice( '</strong>' . esc_html__( 'There are changes in fields that can be translated', 'creame-whatsapp-me' ) . '<strong>' );

		add_settings_error( JOINCHAT_SLUG, 'review_i18n', $message, 'warning' );

	}

	/**
	 * Get settings translations for current language
	 *
	 * @since  4.2
	 * @since  5.1.6 Allow settings in array in format 'key__subkey'
	 * @since  6.0.0 Add $option parameter.
	 * @param  array  $settings list of settings.
	 * @param  string $option option name.
	 * @return array
	 */
	public function load_translations( $settings, $option = JOINCHAT_SLUG ) {

		$settings_i18n = $this->settings_i18n( $settings, $option );
		$domain        = $this->get_domain( $option );

		foreach ( $settings_i18n as $setting => $label ) {
			list( $key, $subkey ) = explode( '__', $setting . '__' );

			if ( empty( $subkey ) ) {
				if ( ! empty( $settings[ $key ] ) ) {
					$settings[ $key ] = apply_filters( 'wpml_translate_single_string', $settings[ $key ], $domain, $label );
				}
			} elseif ( ! empty( $settings[ $key ][ $subkey ] ) ) {
				$settings[ $key ][ $subkey ] = apply_filters( 'wpml_translate_single_string', $settings[ $key ][ $subkey ], $domain, $label );
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

	/**
	 * Return language notice content
	 *
	 * @since 5.1.2
	 * @param  mixed $msg Message content.
	 * @return string
	 */
	private function language_notice( $msg ) {

		$ds   = '&nbsp;&nbsp;';
		$lang = sprintf(
			"<strong>%s$ds%s (%s)</strong>",
			$this->default_language_flag(),
			esc_html__( 'Default site language', 'creame-whatsapp-me' ),
			esc_html( $this->default_language_name() )
		);

		if ( defined( 'ICL_SITEPRESS_VERSION' ) && version_compare( ICL_SITEPRESS_VERSION, '4.7', '>=' ) ) {
			$dashboard = sprintf(
				'<a href="%s">%s</a>',
				esc_url( add_query_arg( 'page', 'tm%2Fmenu%2Fmain.php', admin_url( 'admin.php' ) ) ),
				esc_html__( 'Translation Dashboard', 'sitepress' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			);

			$strings = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $this->translations_link() ),
				esc_html__( 'String Translation', 'wpml-string-translation' ) // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
			);

			$translate = sprintf(
				/* translators: %1$s: Translation Dashboard link, %2$s: String Translation link */
				esc_html__( 'Go to %1$s or %2$s', 'creame-whatsapp-me' ),
				$dashboard,
				$strings
			);
		} else {
			$translate = sprintf(
				'<a href="%s">%s</a>',
				esc_url( $this->translations_link() ),
				esc_html__( 'Manage translations', 'creame-whatsapp-me' )
			);
		}

		return "{$lang}{$ds}{$msg}.{$ds}{$translate}";

	}
}
