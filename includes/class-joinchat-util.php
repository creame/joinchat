<?php
/**
 * Utility class.
 *
 * @package    Joinchat
 */

defined( 'WPINC' ) || exit;

/**
 * Utility class.
 *
 * Include static methods.
 *
 * @since      3.1.0
 * @since      5.0.0     Renamed from JoinchatUtil.
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Util {

	/**
	 * Encode emojis if utf8mb4 not supported by DB
	 *
	 * @since    4.3.0
	 * @access   public
	 * @return   void
	 */
	public static function maybe_encode_emoji() {

		global $wpdb;

		if ( 'utf8mb4' !== $wpdb->get_col_charset( $wpdb->options, 'option_value' ) && ! has_filter( 'sanitize_text_field', 'wp_encode_emoji' ) ) {
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
		$value = wp_unslash( $value );

		if ( is_array( $value ) ) {
			return array_map( self::class . '::clean_input', $value );
		} elseif ( is_string( $value ) ) {
			$value = self::clean_nl( $value );
			// Split lines, clean and re-join lines.
			return implode( "\n", array_map( 'sanitize_text_field', explode( "\n", trim( $value ) ) ) );
		} else {
			return $value;
		}
	}

	/**
	 * Clean new line format
	 *
	 * @since  5.0.12
	 * @param  string $value string to clean.
	 * @return string string with "\n" new lines.
	 */
	public static function clean_nl( $value ) {
		return str_replace( array( "\r\n", "\r" ), array( "\n", "\n" ), $value );
	}

	/**
	 * Check if value is set and is 'yes'
	 *
	 * @since  5.0.12
	 * @param  string $values array of values.
	 * @param  string $key    value key to check.
	 * @return string 'yes' or 'no'
	 */
	public static function yes_no( $values, $key ) {
		return isset( $values[ $key ] ) && 'yes' === $values[ $key ] ? 'yes' : 'no';
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

		$number = is_string( $number ) ? $number : '';

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

		$img_path = (int) $img > 0 ? get_attached_file( $img ) : $img;

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
	 * Return if attachment is video.
	 *
	 * @since    5.2.0
	 * @access   public
	 * @param    mixed $id attachment ID or null or empty.
	 * @return   bool  true if is video, false otherwise
	 */
	public static function is_video( $id ) {

		if ( (int) $id > 0 ) {
			$attachment_mime = get_post_mime_type( $id );

			return strpos( $attachment_mime, 'video/' ) === 0;
		}

		return false;

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
		$img_path = (int) $img > 0 ? get_attached_file( $img ) : $img;

		return $img_path && file_exists( $img_path ) ? (bool) preg_match( '#(\x00\x21\xF9\x04.{4}\x00\x2C.*){2,}#s', file_get_contents( $img_path ) ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Format raw message text for html output.
	 * Also apply styles transformations like WhatsApp app.
	 *
	 * @since    6.0.0
	 * @since    6.3.2 Delegated to Joinchat_Formatter::formatted_message().
	 * @param    string $string    string to apply format replacements.
	 * @param    bool   $as_array  return array of messages.
	 * @return   string|array     string formated
	 */
	public static function formatted_message( $string, $as_array = false ) {
		return Joinchat_Formatter::formatted_message( $string, $as_array );
	}

	/**
	 * Format raw message text for html output.
	 * Also apply styles transformations like WhatsApp or MarkDown.
	 *
	 * @since    3.1.0
	 * @since    3.1.2      Allowed callback replecements
	 * @since    6.0.0      Deprecated, use formatted_message() instead.
	 * @param    string $string    string to apply format replacements.
	 * @return   string     string formated
	 */
	public static function formated_message( $string ) {
		return Joinchat_Formatter::formatted_message( $string );
	}

	/**
	 * Format message send, replace vars.
	 *
	 * @since    3.1.0
	 * @since    6.3.2      Add $context param.
	 * @param    string $string    string to apply variable replacements.
	 * @param    string $context   output context: display (legacy), raw, html or attr.
	 * @return   string     string with replaced variables
	 */
	public static function replace_variables( $string, $context = 'display' ) {

		// If empty or don't has vars return early.
		if ( empty( $string ) || false === strpos( $string, '{' ) ) {
			return $string;
		}

		$replacements = self::get_variable_replacements( $context );

		// Patterns as regex {VAR}.
		$patterns = array();
		foreach ( $replacements as $var => $replacement ) {
			$patterns[] = "/\{$var\}/u";
		}

		return preg_replace( $patterns, $replacements, $string );

	}

	/**
	 * Build variable replacements for a given output context.
	 *
	 * @since 6.3.2
	 * @param string $context output context.
	 * @return array<string, string>
	 */
	private static function get_variable_replacements( $context ) {

		global $wp;

		$replacements = apply_filters(
			'joinchat_variable_replacements',
			array(
				'SITE'  => get_bloginfo( 'name' ),
				'HOME'  => esc_url_raw( home_url() ),
				'URL'   => esc_url_raw( user_trailingslashit( home_url( $wp->request ) ) ),
				'HREF'  => esc_url_raw( home_url( add_query_arg( null, null ) ) ),
				'TITLE' => self::get_title(),
			),
			$context
		);

		foreach ( $replacements as $var => $replacement ) {
			$replacements[ $var ] = self::escape_variable_replacement( $var, (string) $replacement, $context );
		}

		return $replacements;

	}

	/**
	 * Escape a replacement according to the output context.
	 *
	 * @since 6.3.2
	 * @param string $var         variable name.
	 * @param string $replacement replacement value.
	 * @param string $context     output context.
	 * @return string
	 */
	private static function escape_variable_replacement( $var, $replacement, $context ) {

		$replacement = str_replace( '&quot;', '"', $replacement );

		if ( 'raw' === $context ) {
			return $replacement;
		}

		if ( 'attr' === $context ) {
			return esc_attr( wp_strip_all_tags( $replacement ) );
		}

		if ( 'html' === $context ) {
			if ( false !== strpos( $replacement, '<' ) ) {
				return wp_kses( $replacement, self::variable_allowed_html() );
			}

			return esc_html( $replacement );
		}

		if ( in_array( $var, array( 'HOME', 'URL', 'HREF' ), true ) ) {
			return esc_url( $replacement );
		}

		return $replacement;

	}

	/**
	 * Allowed HTML tags for variable replacements in formatted messages.
	 *
	 * @since 6.3.2
	 * @return array<string, array<string, bool>>
	 */
	private static function variable_allowed_html() {
		return (array) apply_filters( 'joinchat_variable_allowed_html', array() );
	}

	/**
	 * Get current page title
	 *
	 * @since    3.1.0
	 * @return   string     message formated string
	 */
	public static function get_title() {

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

		$json_options = defined( 'JSON_UNESCAPED_UNICODE' ) ?
			JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES :
			JSON_HEX_APOS | JSON_HEX_QUOT;

		return wp_json_encode( $data, apply_filters( 'joinchat_json_options', $json_options ) );

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

		return 'manage_options' === self::capability() && apply_filters( 'joinchat_submenu', false );

	}

	/**
	 * Plugin admin page url
	 *
	 * @since    4.2.0
	 * @since    5.0.0 added $page param.
	 * @param  string $page  page slug.
	 * @return string
	 */
	public static function admin_url( $page = JOINCHAT_SLUG ) {

		return add_query_arg( 'page', $page, admin_url( self::options_submenu() ? 'options-general.php' : 'admin.php' ) );

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

		return function_exists( 'register_block_type' ) && is_wp_version_compatible( '5.9' );

	}

	/**
	 * Is Joinchat settings admin screen
	 *
	 * @since    5.0.0
	 * @since    5.2.1 added $include_onboard param.
	 * @param bool $include_onboard Include onboard page.
	 * @return bool
	 */
	public static function is_admin_screen( $include_onboard = false ) {

		if ( did_action( 'load_joinchat_settings_page' ) ) {
			return true;
		}

		if ( $include_onboard && did_action( 'load_joinchat_onboard_page' ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Return link to https://join.chat with utm
	 *
	 * @since    5.0.0
	 * @param  string $path        URL path after join.chat/lang/.
	 * @param  string $utm_source  utm_source param.
	 * @return string
	 */
	public static function link( $path = '', $utm_source = '' ) {

		$lang = false !== strpos( strtolower( get_user_locale() ), 'es' ) ? 'es' : 'en';
		$path = empty( $path ) ? '' : trim( $path, '/' ) . '/';
		$args = array(
			'utm_source'   => $utm_source,
			'utm_medium'   => 'wpadmin',
			'utm_campaign' => 'v' . str_replace( '.', '_', JOINCHAT_VERSION ),
		);

		return add_query_arg( $args, "https://join.chat/$lang/$path" );

	}

	/**
	 * Simple CSS minifier
	 *
	 * View (https://gist.github.com/MeanEYE/36d4abe94ea99014284628a50f5a6d9b).
	 *
	 * @since  5.0.11
	 * @param  string $css CSS string.
	 * @return string      minified CSS string.
	 */
	public static function min_css( $css ) {

		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {

			$rules = array(
				'/\/\*.*?(?=\*\/)\*\//imus'         => '',
				'/([^\d])-?(0+)(px|pt|rem|em|vw|vh|vmax|vmin|cm|mm|m\%)/imus' => '\1\2',
				'/\s*([>~:;,\[\]\{\}])\s*/imus'     => '\1',
				'/\s*([\(\)])\s*([^+-\/\*\^])/imus' => '\1\2',
				'/([\+])\s*([^\d])/imus'            => '\1\2',
				'/#([\dabcdef])\1([\dabcdef])\2([\dabcdef])\3/imus' => '#\1\2\3',
				'/;\}/imus'                         => '}',
			);

			$css = preg_replace( array_keys( $rules ), $rules, $css );
		}

		return $css;

	}

	/**
	 * Get client IP address from request.
	 *
	 * Checks multiple headers commonly used by proxies and CDNs,
	 * validates IPs and prefers public IPs over private/reserved ones.
	 *
	 * @since 6.2.2
	 * @param bool $anonymize Whether to anonymize the IP address (GDPR compliance).
	 * @return string Client IP address or empty string if not available.
	 */
	public static function get_client_ip( $anonymize = false ) {

		// Headers to check in order of preference.
		$headers = array(
			'HTTP_X_FORWARDED_FOR',      // Standard proxy header.
			'HTTP_CF_CONNECTING_IP',     // Cloudflare.
			'HTTP_X_REAL_IP',            // Nginx proxy.
			'HTTP_X_CLIENT_IP',          // Alternative.
			'HTTP_X_CLUSTER_CLIENT_IP',  // Rackspace LB, Riverbed Stingray.
			'HTTP_FORWARDED_FOR',        // RFC 7239.
			'HTTP_FORWARDED',            // RFC 7239.
		);

		$client_ip = '';

		foreach ( $headers as $header ) {
			$server_header = isset( $_SERVER[ $header ] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER[ $header ] ) ) : '';

			if ( '' === $server_header ) {
				continue;
			}

			// Headers like X-Forwarded-For can contain multiple IPs (comma-separated).
			$ips = array_map( 'trim', explode( ',', $server_header ) );

			foreach ( $ips as $ip ) {
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
					$client_ip = $ip;
					break 2;
				}
			}
		}

		if ( empty( $client_ip ) ) {
			$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( (string) $_SERVER['REMOTE_ADDR'] ) ) : '';
			$ip          = trim( $remote_addr );
			if ( '' !== $ip && filter_var( $ip, FILTER_VALIDATE_IP ) !== false ) {
				$client_ip = $ip;
			}
		}

		if ( ! empty( $client_ip ) && $anonymize ) {
			return wp_privacy_anonymize_ip( $client_ip );
		}

		return $client_ip;
	}

	/**
	 * Convert RGB to HSL
	 *
	 * @since 6.0.0
	 * @param int $r Red value.
	 * @param int $g Green value.
	 * @param int $b Blue value.
	 * @return array HSL values.
	 */
	public static function rgb2hsl( $r, $g, $b ) {
		$r /= 255;
		$g /= 255;
		$b /= 255;

		$max = max( $r, $g, $b );
		$min = min( $r, $g, $b );

		$h;
		$s;
		$l = ( $max + $min ) / 2;
		$d = $max - $min;

		if ( $d == 0 ) {
			$h = $s = 0;
		} else {
			$s = $d / ( 1 - abs( 2 * $l - 1 ) );

			if ( $max == $r ) {
				$h = 60 * fmod( ( ( $g - $b ) / $d ), 6 );
				if ( $b > $g ) {
					$h += 360;
				}
			} elseif ( $max == $g ) {
				$h = 60 * ( ( $b - $r ) / $d + 2 );
			} else {
				$h = 60 * ( ( $r - $g ) / $d + 4 );
			}
		}

		return array( round( $h, 0 ), round( $s * 100, 0 ), round( $l * 100, 0 ) );
	}
}


/**
 * Joinchat Util class alias
 *
 * @since      3.1.0
 * @since      5.0.0     Deprecated, use Joinchat_Util instead.
 * @since      6.0.0     Removed
 * @since      6.0.2     Re-added with deprecated notice.
 */
class JoinChatUtil {

	/**
	 * Call Joinchat_Util alias
	 *
	 * @param string $name       function name.
	 * @param mixed  $arguments  function arguments.
	 * @return mixed
	 */
	public static function __callStatic( $name, $arguments ) {

		add_action( 'admin_notices', array( __CLASS__, 'deprecated_notice' ) );

		if ( method_exists( 'Joinchat_Util', $name ) ) {
			return call_user_func_array( array( 'Joinchat_Util', $name ), $arguments );
		}
		trigger_error( esc_html( 'Call to undefined method ' . __CLASS__ . "::$name()" ), E_USER_ERROR );
	}

	public static function deprecated_notice() {
		?>
		<div class="notice notice-warning is-dismissible">
			<p><code>JoinChatUtil</code> class is deprecated, use <code>Joinchat_Util</code> instead.</p>
		</div>
		<?php
	}
}

/**
 * Retrieves the number of times a filter has been applied during the current request.
 *
 * In WordPress since 6.1.0
 */
if ( ! function_exists( 'did_filter' ) ) {
	function did_filter( $hook_name ) {
		global $wp_filters;

		if ( ! isset( $wp_filters[ $hook_name ] ) ) {
			return 0;
		}

		return $wp_filters[ $hook_name ];
	}
}

/**
 * Checks compatibility with the current WordPress version.
 *
 * In WordPress since 5.2.0
 */
if ( ! function_exists( 'is_wp_version_compatible' ) ) {
	function is_wp_version_compatible( $version ) {
		return version_compare( get_bloginfo( 'version' ), $version, '>=' );
	}
}
