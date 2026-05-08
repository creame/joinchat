<?php
/**
 * Internationalization functionality.
 *
 * @package    Joinchat
 */

defined( 'WPINC' ) || exit;

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
	 * @since 5.2.4
	 * @since 6.2.0 Add TranslatePress support.
	 */
	public function init() {

		if ( ! $this->is_ml() ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'settings_notice' ) );

		// Allow third party plugins to load translations.
		add_filter( 'joinchat_load_translations', array( $this, 'load_translations' ), 10, 2 );

		if ( $this->is_ml( 'wpml' ) || $this->is_ml( 'polylang' ) ) {

			// Load translations for global settings.
			add_filter( 'joinchat_get_settings_site', array( $this, 'load_translations' ), 20 );

			add_action( 'joinchat_settings_validation', array( $this, 'register_translations' ), 10, 3 );
			// Allow third party plugins to register translations.
			add_action( 'joinchat_register_translations', array( $this, 'register_translations' ), 10, 3 );

		} else {

			// Load translations for TranslatePress after apply Post/Page settings.
			add_filter( 'joinchat_get_settings', array( $this, 'load_translations' ), 20 );
			add_filter( 'joinchat_show', array( $this, 'trp_hide' ) );
		}

	}

	/**
	 * Get current multilingual plugin
	 *
	 * @since 6.2.0
	 * @param string $plugin Plugin name (wpml, polylang or translatepress).
	 * @return bool
	 */
	private function is_ml( $plugin = '' ) {

		$plugin            = strtolower( $plugin );
		$is_wpml           = defined( 'WPML_PLUGIN_PATH' );
		$is_polylang       = defined( 'POLYLANG_VERSION' );
		$is_translatepress = defined( 'TRP_PLUGIN_SLUG' ) && 'translatepress-multilingual' === TRP_PLUGIN_SLUG;

		if ( '' === $plugin || 'any' === $plugin ) {
			return $is_wpml || $is_polylang || $is_translatepress;
		}

		if ( 'wpml' === $plugin ) {
			return $is_wpml;
		}

		if ( 'polylang' === $plugin ) {
			return $is_polylang;
		}

		if ( 'translatepress' === $plugin ) {
			return $is_translatepress;
		}

		return false;

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

		if ( isset( $settings['header'] ) && '__wa__' !== $settings['header'] ) {
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
	 * Return string prefix for TranslatePress strings
	 *
	 * @since  6.2.0
	 * @return string
	 */
	private function get_prefix( $option = JOINCHAT_SLUG ) {

		return apply_filters( 'joinchat_trp_prefix', 'JC/ ', $option );

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
		if ( $this->is_ml( 'wpml' ) && function_exists( 'icl_get_string_translations' ) && defined( 'ICL_TM_COMPLETE' ) ) {
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

		add_settings_error( $option, 'review_i18n', $message, 'warning' );

	}

	/**
	 * Get settings translations for current language
	 *
	 * @since  4.2
	 * @since  5.1.6 Allow settings in array in format 'key__subkey'
	 * @since  6.0.0 Add $option parameter.
	 * @since  6.2.0 Add TranslatePress support.
	 * @param  array  $settings list of settings.
	 * @param  string $option option name.
	 * @return array
	 */
	public function load_translations( $settings, $option = JOINCHAT_SLUG ) {

		$settings_i18n = $this->settings_i18n( $settings, $option );

		if ( $this->is_ml( 'wpml' ) || $this->is_ml( 'polylang' ) ) {
			$domain = $this->get_domain( $option );

			$translate = function ( $string, $label ) use ( $domain ) {
				return apply_filters( 'wpml_translate_single_string', $string, $domain, $label );
			};
		} else {
			$prefix = $this->get_prefix( $option );

			$translate = function ( $string, $label ) use ( $prefix ) {
				$string = trp_translate( $prefix . $string, null, false );
				return strpos( $string, $prefix ) === 0 ? substr( $string, strlen( $prefix ) ) : $string;
			};
		}

		foreach ( $settings_i18n as $setting => $label ) {
			list( $key, $subkey ) = explode( '__', $setting . '__' );

			if ( empty( $subkey ) ) {
				if ( ! empty( $settings[ $key ] ) ) {
					$settings[ $key ] = $translate( $settings[ $key ], $label );
				}
			} elseif ( ! empty( $settings[ $key ][ $subkey ] ) ) {
				$settings[ $key ][ $subkey ] = $translate( $settings[ $key ][ $subkey ], $label );
			}
		}

		return $settings;

	}

	/**
	 * Hide Joinchat during TranslatePress translation editing
	 *
	 * @since 6.2.0
	 * @param  bool $show Whether to show Joinchat.
	 * @return bool
	 */
	public function trp_hide( $show ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Not needed as this is a GET param used by TranslatePress.
		if ( isset( $_GET['trp-edit-translation'] ) && in_array( $_GET['trp-edit-translation'], array( 'true', 'preview' ), true ) ) {
			return false;
		}

		return $show;

	}

	/**
	 * Return strings translations url for WPML/Polylang/TranslatePress
	 *
	 * @since 5.0.0
	 * @since 6.2.0 Add TranslatePress support.
	 * @return string
	 */
	private function translations_link() {

		if ( $this->is_ml( 'wpml' ) ) {
			$args = array(
				'page'    => 'wpml-string-translation/menu/string-translation.php',
				'context' => self::DOMAIN_GROUP,
			);

			return add_query_arg( $args, admin_url( 'admin.php' ) );
		}

		if ( $this->is_ml( 'polylang' ) ) {
			$args = array(
				'page'  => 'mlang_strings',
				'group' => self::DOMAIN_GROUP,
				'lang'  => 'all',
			);

			return add_query_arg( $args, admin_url( 'admin.php' ) );
		}

		if ( $this->is_ml( 'translatepress' ) ) {
			$prefix = $this->get_prefix();

			return home_url() . '?trp-string-translation=true#/regular/?s=' . $prefix;
		}

		return '';

	}

	/**
	 * Return default language name for WPML/Polylang
	 *
	 * @since 5.0.0
	 * @since 6.2.0 Add TranslatePress support.
	 * @return string
	 */
	private function default_language_name() {

		if ( $this->is_ml( 'wpml' ) ) {
			$default_language = apply_filters( 'wpml_default_language', null );
			$current_language = apply_filters( 'wpml_current_language', null );

			return apply_filters( 'wpml_translated_language_name', null, $default_language, $current_language );
		}

		if ( $this->is_ml( 'polylang' ) ) {
			return pll_default_language( 'name' );
		}

		if ( $this->is_ml( 'translatepress' ) ) {
			$settings  = get_option( 'trp_settings', array() );
			$language  = isset( $settings['default_language'] ) ? $settings['default_language'] : 'en_US';
			$languages = trp_get_languages();

			return isset( $languages[ $language ] ) ? $languages[ $language ] : $language;
		}

		return get_locale();

	}

	/**
	 * Return default language flag for WPML/Polylang
	 *
	 * @since 5.0.0
	 * @since 6.2.0 Add TranslatePress support.
	 * @return string
	 */
	private function default_language_flag() {

		if ( $this->is_ml( 'wpml' ) ) {
			$languages = apply_filters( 'wpml_active_languages', null, array() );
			$language  = $languages[ apply_filters( 'wpml_default_language', null ) ];

			return '<img src="' . esc_url( $language['country_flag_url'] ) . '" alt="' . esc_attr( $language['language_code'] ) . '" height="12" width="18" />';
		}

		if ( $this->is_ml( 'polylang' ) ) {
			$languages = pll_the_languages( array( 'raw' => 1, 'show_flags' => 1 ) ); // phpcs:ignore WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
			$language  = $languages[ pll_default_language() ];

			return $language['flag'];
		}

		if ( $this->is_ml( 'translatepress' ) ) {
			$settings  = get_option( 'trp_settings', array() );
			$language  = isset( $settings['default_language'] ) ? $settings['default_language'] : 'en_US';
			$flag_path = apply_filters( 'trp_flags_path', TRP_PLUGIN_URL . 'assets/images/flags/', $language );
			$flag_file = apply_filters( 'trp_flag_file_name', $language . '.png', $language );

			return '<img src="' . esc_url( $flag_path . $flag_file ) . '" alt="' . esc_attr( $language ) . '" height="12" width="18" />';
		}

		return '';

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
