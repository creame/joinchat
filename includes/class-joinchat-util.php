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
	 * Return list of settings that can be translated
	 *
	 * Note: don't translate string $name to prevent missing translations if
	 * public front lang is different of admin lang
	 *
	 * @since    3.1.2
	 * @since    4.1   added optional param $settings
	 * @access   public
	 * @param    null|array $settings
	 * @return   array setting keys and string names
	 */
	public static function settings_i18n( $settings = null ) {

		$localized = array(
			'telephone'     => 'Telephone',
			'button_tip'    => 'Tooltip',
			'message_text'  => 'Call to Action',
			'message_send'  => 'Message',
			'message_start' => 'Button Text',
		);

		if ( isset( $settings['header'] ) && ! in_array( $settings['header'], array( '', '__jc__', '__wa__' ) ) ) {
			$localized['header'] = 'Header';
		}

		return apply_filters( 'joinchat_settings_i18n', $localized, $settings );

	}

	/**
	 * Clean user input fields
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    mixed $value to clean
	 * @return   mixed $value cleaned
	 */
	public static function clean_input( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'self::clean_input', $value );
		} elseif ( is_string( $value ) ) {
			// Split lines, clean and re-join lines
			return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", trim( $value ) ) ) );
		} else {
			return $value;
		}
	}

	/**
	 * Apply mb_substr() if available or fallback to substr()
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    string $str The input string
	 * @param    int    $start The first position used in str
	 * @param    int    $length The maximum length of the returned string
	 * @return   string     The portion of str specified by the start and length parameters
	 */
	public static function substr( $str, $start, $length = null ) {
		return function_exists( 'mb_substr' ) ? mb_substr( $str, $start, $length ) : substr( $str, $start, $length );
	}

	/**
	 * Return thumbnail url and size.
	 *
	 * Create thumbnail of size if not exists and return url an size info.
	 *
	 * @since    3.1.0
	 * @access   public
	 * @param    mixed $img Image path or attachment ID
	 * @param    int   $width The widht of thumbnail
	 * @param    int   $height The height of thumbnail
	 * @param    bool  $crop If crop to exact thumbnail size or not
	 * @return   array  With thumbnail info (url, width, height)
	 */
	public static function thumb( $img, $width, $height, $crop = true ) {

		$img_path = intval( $img ) > 0 ? get_attached_file( $img ) : $img;

		// Try fallback if file don't exists (filter to true to skip thumbnail generation)
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

		$uploads  = wp_get_upload_dir();
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
				// Fallback to original image
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
	 * @param    mixed $img Image path or attachment ID
	 * @return   bool  true if is an animated gif, false otherwise
	 */
	public static function is_animated_gif( $img ) {
		$img_path = intval( $img ) > 0 ? get_attached_file( $img ) : $img;

		return $img_path && file_exists( $img_path ) ? (bool) preg_match( '#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', file_get_contents( $img_path ) ) : false;
	}

	/**
	 * Format raw message text for html output.
	 * Also apply styles transformations like WhatsApp app.
	 *
	 * @since    3.1.0
	 * @since    3.1.2      Allowed callback replecements
	 * @param    string $string    string to apply format replacements
	 * @return   string     string formated
	 */
	public static function formated_message( $string ) {

		$replacements = apply_filters(
			'joinchat_format_replacements',
			array(
				'/(^|\W)_(.+?)_(\W|$)/u'   => '$1<em>$2</em>$3',
				'/(^|\W)\*(.+?)\*(\W|$)/u' => '$1<strong>$2</strong>$3',
				'/(^|\W)~(.+?)~(\W|$)/u'   => '$1<del>$2</del>$3',
			)
		);

		// Split text into lines and apply replacements line by line
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
	 * @since    4.1.7      Added replacements fixes
	 * @param    string $string    string to apply variable replacements
	 * @return   string     string with replaced variables
	 */
	public static function replace_variables( $string ) {
		global $wp;

		$replacements = apply_filters(
			'joinchat_variable_replacements',
			array(
				'SITE'  => get_bloginfo( 'name' ),
				'URL'   => home_url( $wp->request ),
				'TITLE' => self::get_title(),
			)
		);

		// Patterns as regex {VAR}
		$patterns = array();
		foreach ( $replacements as $var => $replacement ) {
			$patterns[] = "/\{$var\}/u";
		}

		$fixes = array(
			'&quot;' => '"',   // Prevent malformed json
			'$'      => '\$',  // Prevent regex reference
		);

		foreach ( $replacements as $var => $replacement ) {
			$replacements[ $var ] = str_replace( array_keys( $fixes ), $fixes, $replacement );
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

		if ( is_home() || is_singular() ) {
			$title = single_post_title( '', false );
		} elseif ( is_tax() ) {
			$title = single_term_title( '', false );
		} elseif ( function_exists( 'wp_get_document_title' ) ) {
			$title = wp_get_document_title();

			// Try to remove sitename from $title for cleaner title
			$sep   = apply_filters( 'document_title_separator', '-' );
			$site  = get_bloginfo( 'name', 'display' );
			$title = str_replace( esc_html( convert_chars( wptexturize( " $sep " . $site ) ) ), '', $title );
		} else {
			$title = get_bloginfo( 'name' );
		}

		return apply_filters( 'joinchat_get_title', $title );

	}

	/**
	 * Encode JSON with filtered options
	 *
	 * @since    4.0.9
	 * @param    array $data    data to encode
	 * @return   string     data json encoded
	 */
	public static function to_json( $data ) {

		$json_options = defined( 'JSON_UNESCAPED_UNICODE' ) ?
			JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES :
			JSON_HEX_APOS | JSON_HEX_QUOT;

		return json_encode( $data, apply_filters( 'joinchat_json_options', $json_options ) );

	}
}
