<?php

/**
 * Utility class.
 *
 * Include static methods.
 *
 * @since      3.1.0
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChatUtil {

	/**
	 * Encode emojis if utf8mb4 not supported by DB
	 *
	 * @since    4.3.0
	 * @access   public
	 * @return   void
	 */
	public static function maybe_encode_emoji() {

		global $wpdb;

		_deprecated_function( 'JoinchatUtil::maybe_encode_emoji', '5.0.0', 'Joinchat_Util::maybe_encode_emoji' );

		if ( function_exists( 'wp_encode_emoji' )
				&& 'utf8mb4' !== $wpdb->get_col_charset( $wpdb->options, 'option_value' )
				&& ! has_filter( 'sanitize_text_field', 'wp_encode_emoji' ) ) {
			add_filter( 'sanitize_text_field', 'wp_encode_emoji' );
		}
	}

	/**
	 * Clean user input fields
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    mixed $value to clean.
	 * @return   mixed $value cleaned
	 */
	public static function clean_input( $value ) {

		_deprecated_function( 'JoinchatUtil::clean_input', '5.0.0', 'Joinchat_Util::clean_input' );

		if ( is_array( $value ) ) {
			return array_map( 'self::clean_input', $value );
		} elseif ( is_string( $value ) ) {
			// Split lines, clean and re-join lines.
			return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", trim( $value ) ) ) );
		} else {
			return $value;
		}
	}

	/**
	 * Clean WhatsApp number
	 *
	 * View (https://faq.whatsapp.com/general/contacts/how-to-add-an-international-phone-number)
	 *
	 * @since    4.3.0
	 * @access   public
	 * @param    string $number phone number to clean.
	 * @return   string number cleaned
	 */
	public static function clean_whatsapp( $number ) {

		_deprecated_function( 'JoinchatUtil::clean_whatsapp', '5.0.0', 'Joinchat_Util::clean_whatsapp' );

		// Remove any leading 0s or special calling codes.
		$clean = preg_replace( '/^0+|\D/', '', $number );

		// Argentina (country code "54") should have a "9" between the country code and area code
		// and prefix "15" must be removed so the final number will have 13 digits total.
		// (intlTelInput saved numbers already has in international mode).
		$clean = preg_replace( '/^54(0|1|2|3|4|5|6|7|8)/', '549$1', $clean );
		$clean = preg_replace( '/^(54\d{5})15(\d{6})/', '$1$2', $clean );

		// Mexico (country code "52") need to have "1" after "+52".
		$clean = preg_replace( '/^52(0|2|3|4|5|6|7|8|9)/', '521$1', $clean );

		return apply_filters( 'joinchat_clean_whatsapp', $clean, $number );
	}

	/**
	 * Apply mb_substr() if available or fallback to substr()
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    string $str The input string.
	 * @param    int    $start The first position used in str.
	 * @param    int    $length The maximum length of the returned string.
	 * @return   string     The portion of str specified by the start and length parameters
	 */
	public static function substr( $str, $start, $length = null ) {

		_deprecated_function( 'JoinchatUtil::substr', '5.0.0', 'Joinchat_Util::substr' );

		return function_exists( 'mb_substr' ) ? mb_substr( $str, $start, $length ) : substr( $str, $start, $length );

	}

	/**
	 * Return thumbnail url and size.
	 *
	 * Create thumbnail of size if not exists and return url an size info.
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    mixed $img Image path or attachment ID.
	 * @param    int   $width The widht of thumbnail.
	 * @param    int   $height The height of thumbnail.
	 * @param    bool  $crop If crop to exact thumbnail size or not.
	 * @return   array  With thumbnail info (url, width, height)
	 */
	public static function thumb( $img, $width, $height, $crop = true ) {

		_deprecated_function( 'JoinchatUtil::thumb', '5.0.0', 'Joinchat_Util::thumb' );

		$img_path = intval( $img ) > 0 ? get_attached_file( $img ) : $img;

		// Try fallback if file don't exists (filter to true to skip thumbnail generation).
		if ( apply_filters( 'joinchat_disable_thumbs', ! $img_path || ! file_exists( $img_path ) ) ) {
			$src = wp_get_attachment_image_src( $img, array( $width, $height ) );

			if ( is_array( $src ) ) {
				return array(
					'url'    => $src[0],
					'width'  => $src[1],
					'height' => $src[2],
				);
			}

			return false;
		}

		$uploads  = wp_upload_dir( null, false );
		$img_info = pathinfo( $img_path );
		$new_path = "{$img_info['dirname']}/{$img_info['filename']}-{$width}x{$height}.{$img_info['extension']}";

		if ( ! file_exists( $new_path ) ) {
			$new_img = wp_get_image_editor( $img_path );

			if ( ! is_wp_error( $new_img ) ) {
				$new_img->resize( $width, $height, $crop );
				$new_img = $new_img->save( $new_path );

				$thumb = array(
					'url'    => str_replace( $uploads['basedir'], $uploads['baseurl'], $new_path ),
					'width'  => $new_img['width'],
					'height' => $new_img['height'],
				);
			} else {
				// Fallback to original image.
				@list($w, $h) = getimagesize( $img_path );

				$thumb = array(
					'url'    => str_replace( $uploads['basedir'], $uploads['baseurl'], $img_path ),
					'width'  => $w,
					'height' => $h,
				);
			}
		} else {
			@list($w, $h) = getimagesize( $new_path );

			$thumb = array(
				'url'    => str_replace( $uploads['basedir'], $uploads['baseurl'], $new_path ),
				'width'  => $w,
				'height' => $h,
			);
		}

		return $thumb;

	}

	/**
	 * Return if image is animated gif.
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    mixed $img Image path or attachment ID.
	 * @return   bool  true if is an animated gif, false otherwise
	 */
	public static function is_animated_gif( $img ) {

		_deprecated_function( 'JoinchatUtil::is_animated_gif', '5.0.0', 'Joinchat_Util::is_animated_gif' );

		$img_path = intval( $img ) > 0 ? get_attached_file( $img ) : $img;

		return $img_path && file_exists( $img_path ) ? (bool) preg_match( '#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', file_get_contents( $img_path ) ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

	}

	/**
	 * Format raw message text for html output.
	 * Also apply styles transformations like WhatsApp app.
	 *
	 * @since    3.1.0
	 * @since    3.1.2      Allowed callback replecements
	 * @param    string $string    string to apply format replacements.
	 * @return   string     string formated
	 */
	public static function formated_message( $string ) {

		_deprecated_function( 'JoinchatUtil::formated_message', '5.0.0', 'Joinchat_Util::formated_message' );

		$replacements = apply_filters(
			'joinchat_format_replacements',
			array(
				'/(^|\W)_(.+?)_(\W|$)/u'   => '$1<em>$2</em>$3',
				'/(^|\W)\*(.+?)\*(\W|$)/u' => '$1<strong>$2</strong>$3',
				'/(^|\W)~(.+?)~(\W|$)/u'   => '$1<del>$2</del>$3',
			)
		);

		// Split text into lines and apply replacements line by line.
		$lines = explode( "\n", $string );
		foreach ( $lines as $key => $line ) {
			$escaped_line = esc_html( $line );

			foreach ( $replacements as $pattern => $replacement ) {
				if ( is_callable( $replacement ) ) {
					$escaped_line = preg_replace_callback( $pattern, $replacement, $escaped_line );
				} else {
					$escaped_line = preg_replace( $pattern, $replacement, $escaped_line );
				}
			}

			$lines[ $key ] = $escaped_line;
		}

		return self::replace_variables( implode( '<br>', $lines ) );

	}

	/**
	 * Format message send, replace vars.
	 *
	 * @since    3.1.0
	 * @param    string $string    string to apply variable replacements.
	 * @return   string     string with replaced variables
	 */
	public static function replace_variables( $string ) {

		_deprecated_function( 'JoinchatUtil::replace_variables', '5.0.0', 'Joinchat_Util::replace_variables' );

		// If empty or don't has vars return early.
		if ( empty( $string ) || false === strpos( $string, '{' ) ) {
			return $string;
		}

		global $wp;

		$replacements = apply_filters(
			'joinchat_variable_replacements',
			array(
				'SITE'  => get_bloginfo( 'name' ),
				'URL'   => home_url( $wp->request ),
				'HREF'  => home_url( add_query_arg( null, null ) ),
				'TITLE' => self::get_title(),
			)
		);

		// Patterns as regex {VAR}.
		$patterns = array();
		foreach ( $replacements as $var => $replacement ) {
			$patterns[] = "/\{$var\}/u";
		}

		// Prevent malformed json.
		foreach ( $replacements as $var => $replacement ) {
			$replacements[ $var ] = str_replace( '&quot;', '"', $replacement );
		}

		return preg_replace( $patterns, $replacements, $string );

	}

	/**
	 * Get current page title
	 *
	 * @since    3.1.0
	 * @return   string     message formated string
	 */
	public static function get_title() {

		_deprecated_function( 'JoinchatUtil::get_title', '5.0.0', 'Joinchat_Util::get_title' );

		$filter = function ( $parts ) {
			return empty( $parts['title'] ) ? $parts : array( 'title' => $parts['title'] );
		};

		add_filter( 'pre_get_document_title', '__return_empty_string', 100 ); // "Disable" third party bypass.
		add_filter( 'document_title_parts', $filter, 100 ); // Filter only 'title' part.

		$title = wp_get_document_title();

		remove_filter( 'pre_get_document_title', '__return_empty_string', 100 ); // "Re-enable" third party bypass.
		remove_filter( 'document_title_parts', $filter, 100 ); // Remove our filter.

		return apply_filters( 'joinchat_get_title', $title );

	}

	/**
	 * Encode JSON with filtered options
	 *
	 * @since    4.0.9
	 * @param    array $data    data to encode.
	 * @return   string     data json encoded
	 */
	public static function to_json( $data ) {

		_deprecated_function( 'JoinchatUtil::to_json', '5.0.0', 'Joinchat_Util::to_json' );

		$json_options = defined( 'JSON_UNESCAPED_UNICODE' ) ?
			JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES :
			JSON_HEX_APOS | JSON_HEX_QUOT;

		return json_encode( $data, apply_filters( 'joinchat_json_options', $json_options ) );

	}

	/**
	 * Return required capability to change settings
	 *
	 * Default capability 'manage_options'
	 *
	 * @since    4.2.0
	 * @param  string $capability required capability.
	 * @return string
	 */
	public static function capability( $capability = '' ) {

		_deprecated_function( 'JoinchatUtil::capability', '5.0.0', 'Joinchat_Util::capability' );

		return apply_filters( 'joinchat_capability', $capability ?: 'manage_options' ); //phpcs:ignore WordPress.PHP.DisallowShortTernary

	}

	/**
	 * Plugin admin page is in options submenu
	 *
	 * @since    4.2.0
	 * @since    4.4.0 return false by default
	 * @return bool
	 */
	public static function options_submenu() {

		_deprecated_function( 'JoinchatUtil::options_submenu', '5.0.0', 'Joinchat_Util::options_submenu' );

		return 'manage_options' === self::capability() && apply_filters( 'joinchat_submenu', false );

	}

	/**
	 * Plugin admin page url
	 *
	 * @since    4.2.0
	 * @return string
	 */
	public static function admin_url() {

		_deprecated_function( 'JoinchatUtil::admin_url', '5.0.0', 'Joinchat_Util::admin_url' );

		return admin_url( self::options_submenu() ? 'options-general.php' : 'admin.php' ) . '?page=joinchat';

	}

	/**
	 * Can use Gutenberg
	 *
	 * Require at least WordPress 5.9
	 *
	 * @since    4.5.2
	 * @return bool
	 */
	public static function can_gutenberg() {

		_deprecated_function( 'JoinchatUtil::can_gutenberg', '5.0.0', 'Joinchat_Util::can_gutenberg' );

		return function_exists( 'register_block_type' ) && version_compare( get_bloginfo( 'version' ), '5.9', '>=' );

	}
}
