<?php

/**
 * Utility class.
 *
 * Include static methods.
 *
 * @since      3.1.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/includes
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_Util {

	/**
	 * Return list of settings that can be translated
	 *
	 * Note: don't translate string $name to prevent missing translations if
	 * public front lang is different of admin lang
	 *
	 * @since    3.1.2
	 * @access   public
	 * @return   array setting keys and string names
	 */
	public static function settings_i18n() {

		return apply_filters(
			'whatsappme_settings_i18n', array(
				'button_tip'    => 'Tooltip',
				'message_text'  => 'Call to Action',
				'message_send'  => 'Message',
				'message_start' => 'Start WhatsApp Button',
			)
		);

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

		if ( ! file_exists( $img_path ) ) {
			return false;
		}

		$uploads      = wp_get_upload_dir();
		$img_info     = pathinfo( $img_path );
		$new_img_path = "{$img_info['dirname']}/{$img_info['filename']}-{$width}x{$height}.{$img_info['extension']}";

		if ( ! file_exists( $new_img_path ) ) {
			$new_img = wp_get_image_editor( $img_path );
			$new_img->resize( $width, $height, $crop );
			$new_img = $new_img->save( $new_img_path );

			$thumb = array(
				'url'    => str_replace( $uploads['basedir'], $uploads['baseurl'], $new_img_path ),
				'width'  => $new_img['width'],
				'height' => $new_img['height'],
			);
		} else {
			@list($w, $h) = getimagesize( $new_img_path );

			$thumb = array(
				'url'    => str_replace( $uploads['basedir'], $uploads['baseurl'], $new_img_path ),
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

		return file_exists( $img_path ) ? (bool) preg_match( '#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', file_get_contents( $img_path ) ) : false;
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
			'whatsappme_format_replacements', array(
				'/_(\S[^_]*\S)_/u'    => '<em>$1</em>',
				'/\*(\S[^\*]*\S)\*/u' => '<strong>$1</strong>',
				'/~(\S[^~]*\S)~/u'    => '<del>$1</del>',
			)
		);

		$replacements = apply_filters_deprecated( 'whatsappme_message_replacements', array( $replacements ), '3.0.3', 'whatsappme_format_replacements' );

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
	 * @param    string $string    string to apply variable replacements
	 * @return   string     string with replaced variables
	 */
	public static function replace_variables( $string ) {
		global $wp;

		$replacements = apply_filters(
			'whatsappme_variable_replacements', array(
				'SITE'  => get_bloginfo( 'name' ),
				'URL'   => home_url( $wp->request ),
				'TITLE' => self::get_title(),
			)
		);

		// Convert VAR to regex {VAR}
		$patterns = array_map(
			function ( $var ) {
				return "/\{$var\}/u";
			}, array_keys( $replacements )
		);

		$replacements = apply_filters_deprecated( 'whatsappme_message_send_replacements', array( array_combine( $patterns, $replacements ) ), '3.0.3', 'whatsappme_variable_replacements' );

		return preg_replace( array_keys( $replacements ), $replacements, $string );

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

		return apply_filters( 'whatsappme_get_title', $title );

	}

}
