<?php
/**
 * Message formatter class.
 *
 * @package    Joinchat
 */

defined( 'WPINC' ) || exit;

/**
 * Formatter class.
 *
 * Message formatter replacements.
 *
 * @since      6.0.0
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Formatter {

	/**
	 * Whether the formatter hooks have already been initialized.
	 *
	 * @since    6.3.2
	 * @var bool
	 */
	private static $initialized = false;

	/**
	 * URL characters that would trigger text formats and their placeholders.
	 *
	 * @since    6.4.3
	 * @var array<string, string>
	 */
	private const URL_CHARS = array(
		'_' => '&#95;',
		'*' => '&#42;',
		'~' => '&#126;',
		'`' => '&#96;',
	);

	/**
	 * Initialize the formatter hooks.
	 *
	 * @since    6.3.2
	 * @return void
	 */
	public static function init() {

		if ( self::$initialized ) {
			return;
		}

		add_filter( 'joinchat_format_replacements', array( __CLASS__, 'formatter' ) );
		add_filter( 'joinchat_format_replacements', array( __CLASS__, 'save_variables' ), 100 );

		self::$initialized = true;
	}

	/**
	 * Format CTA message text for HTML output.
	 *
	 * Legacy wrapper remains in Joinchat_Util::formatted_message() for
	 * backwards compatibility.
	 *
	 * @since 6.3.2
	 * @param string $string   string to apply format replacements.
	 * @param bool   $as_array return array of messages.
	 * @return string|array
	 */
	public static function formatted_message( $string, $as_array = false ) {

		self::init();

		if ( empty( $string ) ) {
			return $as_array ? array() : '';
		}

		// Format replacements.
		$replacements = apply_filters( 'joinchat_format_replacements', array() );

		// Split text into lines.
		$lines = explode( "\n", Joinchat_Util::clean_nl( $string ) );

		// Apply replacements line by line.
		if ( count( $replacements ) ) {
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
		}

		// Join lines and replace remaining text variables after formatter callbacks
		// have already handled their own URL/attribute contexts.
		$context   = $as_array ? 'raw' : 'html';
		$formatted = Joinchat_Util::replace_variables( implode( '<br>', $lines ), $context );

		// Out of bubble messages (notes).
		$formatted = preg_replace( '/(^(?:&gt;){3,}<br>)/u', '>>>', $formatted );
		$formatted = preg_replace( '/(<br>(?:&gt;){3,}<br>)/u', '<br>===<br>>>>', $formatted );
		$formatted = preg_replace( '/(^(?:=){3,}<br>)/u', '', $formatted );

		// Split message in bubbles.
		$messages = preg_split( '/<br>={3,}<br>/u', $formatted );

		// Return array of messages.
		if ( $as_array ) {
			return $messages;
		}

		// Wrap messages in divs & add classes.
		foreach ( $messages as $key => $msg ) {
			$class = '';

			if ( substr( $msg, 0, 3 ) === '>>>' ) {
				$class = ' joinchat__bubble--note';
				$msg   = substr( $msg, 3 );
			} elseif ( wp_strip_all_tags( $msg ) === '' ) {
				$class = ' joinchat__bubble--media';
			}

			$messages[ $key ] = sprintf( '<div class="joinchat__bubble%s">%s</div>', $class, $msg );
		}

		$messages = join( "\n", $messages );
		$messages = str_replace( ' src="', ' data-src="', $messages ); // For delayed loading.

		return $messages;
	}

	/**
	 * Add replacements for extended syntax
	 *
	 * @since    6.0.0
	 * @param    array $replacements       current replacements.
	 * @return   array
	 */
	public static function formatter( $replacements ) {

		$before = array(
			// Protect link and image URLs from text formats.
			'/\]\([^)]+\)|\{(?:LINK|BTN|IMG) [^ }]+/u' => function ( $groups ) {
				return self::protect_attr( $groups[0] );
			},
			// MarkDown bold to WhatsApp bold.
			'/(^|\W)\*\*(\S(?:.*?\S)??)\*\*(?!\w)/u'   => '$1*$2*',
			'/(^|\W)__(\S(?:.*?\S)??)__(?!\w)/u'       => '$1*$2*',
		);

		$formats = array();

		/**
		 * WhatsApp italic
		 * *text* => <em>text</em>
		 */
		$formats['/(^|\W)_(\S(?:.*?\S)??)_(?!\w)/u'] = '$1<em>$2</em>';

		/**
		 * WhatsApp bold
		 * *text* => <strong>text</strong>
		 */
		$formats['/(^|\W)\*(\S(?:.*?\S)??)\*(?!\w)/u'] = '$1<strong>$2</strong>';

		/**
		 * WhatsApp strikethrough
		 * ~text~ => <del>text</del>
		 */
		$formats['/(^|\W)~(\S(?:.*?\S)??)~(?!\w)/u'] = '$1<del>$2</del>';

		/**
		 * Markdown code
		 * `your code` => <code>your code</code>
		 */
		$formats['/(^|\W)`(\S(?:.*?\S)??)`(?!\w)/u'] = '$1<code>$2</code>';

		/**
		 * Markdown horizontal rule
		 * --- => <hr>
		 */
		$formats['/^-{3,}$/u'] = '<hr>';

		/**
		 * Markdown image
		 * ![alt text](image.jpg width) => <img src="image.jpg" alt="alt text" width="width">
		 */
		$formats['/!\[(?<alt>[^\]]*)\]\((?<src>[^\) ]*)(?: (?<w>\d+))?\)/u'] = function ( $groups ) {
			return self::image( $groups );
		};

		/**
		 * Markdown link
		 * [title](https://www.example.com) => <a href="https://www.example.com">title</a>
		 */
		$formats['/\[(?<text>[^\[\]]+)?\]\((?<href>[^)]+)\)/u'] = function ( $groups ) {
			return self::link( $groups );
		};

		/**
		 * Joinchat image
		 * {IMG image.jpg width alt_text} => <img src="image.jpg" alt="alt_text" width="width">
		 */
		$formats['/{IMG (?<src>[^ }]+)(?: (?<w>\d+))?(?: (?<alt>[^}]+))?}/u'] = function ( $groups ) {
			return self::image( $groups );
		};

		/**
		 * Joinchat link
		 * {LINK https://www.example.com title} => <a href="https://www.example.com">title</a>
		 */
		$formats['/{LINK (?<href>[^ }]+)(?: (?<text>[^}]+))?}/u'] = function ( $groups ) {
			return self::link( $groups );
		};

		/**
		 * Joinchat link button
		 * {BTN https://www.example.com title} => <a class="joinchat__btn" href="https://www.example.com">title</a>
		 */
		$formats['/{BTN (?<href>[^ }]+)(?: (?<text>[^}]+))?}/u'] = function ( $groups ) {
			return self::button( $groups );
		};

		/**
		 * Joinchat random text
		 * {RAND texto 1||texto 2||texto n} => "texto 1" or "texto 2" or "texto n"
		 */
		$formats['/{RAND (?<text>[^}]+)}/u'] = function ( $groups ) {
			return self::random( $groups );
		};

		return array_merge( $before, $replacements, $formats );
	}

	/**
	 * Preserve variables to prevents conflicts with other replacements
	 *
	 * @since    3.0.0
	 * @param    array $replacements       current replacements.
	 * @return   array
	 */
	public static function save_variables( $replacements ) {

		$transform = array( '/{([A-Z]+)}/' => '&lcub;$1&rcub;' );
		$restore   = array( '/&lcub;([A-Z]+)&rcub;/' => '{$1}' );

		return $transform + $replacements + $restore;
	}

	/**
	 * Link html output with regex groups
	 *
	 * $groups['href']
	 * $groups['text']
	 * $groups['class']
	 *
	 * @since    6.0.0
	 * @param    array $groups      regex groups of link.
	 * @return   string html <a>
	 */
	private static function link( $groups ) {

		// A leading "!" forces the link to open in a new tab.
		$url     = self::restore_url( $groups['href'] );
		$new_tab = '!' === substr( $url, 0, 1 );
		$href    = self::restore_and_replace( $new_tab ? substr( $url, 1 ) : $url, 'raw' );
		$text    = ! empty( $groups['text'] ) ? self::restore_and_replace( $groups['text'], 'html' ) : preg_replace( '/^https?:\/\//u', '', $href );
		$class   = ! empty( $groups['class'] ) ? ' class="' . esc_attr( $groups['class'] ) . '"' : '';
		$rel     = '';
		$blank   = $new_tab ? ' target="_blank"' : '';

		// Check if is an external link and add rel attribute for better SEO & Security.
		$host = wp_parse_url( $href, PHP_URL_HOST );

		if ( $host && wp_parse_url( get_home_url(), PHP_URL_HOST ) !== $host ) {
			$rel   = ' rel="external nofollow noopener noreferrer"';
			$blank = ' target="_blank"';
		}

		$output = sprintf( '<a href="%s"%s%s%s>%s</a>', esc_url( $href ), $class, $rel, $blank, $text );

		return $output;
	}

	/**
	 * Button link html output with regex groups
	 *
	 * $groups['href']
	 * $groups['text']
	 *
	 * @since    6.0.0
	 * @param    array $groups       regex groups of link.
	 * @return   string html <a>
	 */
	private static function button( $groups ) {

		$groups['class'] = 'joinchat__btn';

		return self::link( $groups );
	}

	/**
	 * Random text
	 *
	 * $groups['text']
	 *
	 * @since    6.0.0
	 * @param    array $groups       regex groups of random.
	 * @return   string html <jc-rand><jc-opt>texto 1</jc-opt><jc-opt>texto 2</jc-opt></jc-rand>
	 */
	private static function random( $groups ) {

		$texts = explode( '||', self::restore_variables( $groups['text'] ) );
		foreach ( $texts as $key => $text ) {
			$texts[ $key ] = '<jc-opt>' . Joinchat_Util::replace_variables( trim( $text ), 'html' ) . '</jc-opt>';
		}

		return '<jc-rand>' . join( '', $texts ) . '</jc-rand>';
	}

	/**
	 * Image html output with regex groups
	 *
	 * If image url is numeric try to get the image of media library an
	 * resize it to correct Joinchat chat window size.
	 *
	 * Can use 'FEATURED' or 'THUMB' src for post thumbnail image.
	 *
	 * $groups['src']
	 * $groups['alt']
	 * $groups['w']
	 *
	 * @since    6.0.0
	 *
	 * @param    array $groups       regex groups of image.
	 * @return   string html <img>
	 */
	private static function image( $groups ) {

		$chat_max = 340; // Chat window max width.

		$src = self::restore_and_replace( self::restore_url( $groups['src'] ), 'raw' );
		$w   = isset( $groups['w'] ) && is_numeric( $groups['w'] ) ? $groups['w'] : $chat_max;
		$alt = isset( $groups['alt'] ) ? self::restore_and_replace( wp_strip_all_tags( $groups['alt'] ), 'attr' ) : '';
		$alt = 'alt="' . $alt . '"';

		// If is post featured image.
		if ( in_array( strtoupper( $src ), array( 'THUMB', 'FEATURED' ), true ) ) {
			$src = (int) get_post_thumbnail_id();
		}

		$is_video = false;
		$sizes    = array();
		$width    = $w <= $chat_max ? "width=\"$w\"" : '';
		$class    = $w <= $chat_max ? 'class="joinchat--inline"' : '';

		if ( is_numeric( $src ) && (int) $src > 0 ) {
			if ( wp_attachment_is( 'image', $src ) ) {
				$info = wp_get_attachment_image_src( $src, 'full' );

				if ( is_array( $info ) ) {
					if ( 0 === $info[1] ) {
						$sizes[1] = $info[0];
					} else {
						$class = min( $w, $info[1] ) < $chat_max ? 'class="joinchat--inline"' : '';

						if ( apply_filters( 'joinchat_image_original', Joinchat_Util::is_animated_gif( $src ), $src, 'cta' ) ) {
							$sizes[1] = $info[0];
						} else {
							$w_max = min( $w, $chat_max ); // Image max width.
							$ratio = $info[2] / $info[1];  // Image aspect ratio.

							for ( $i = 1; $i < 4; $i++ ) {
								if ( $w_max * $i < $info[1] ) {
									$thumb       = Joinchat_Util::thumb( $src, $w_max * $i, round( $w_max * $i * $ratio ) );
									$sizes[ $i ] = esc_url( is_array( $thumb ) ? $thumb['url'] : $info[0] );
								} else {
									$sizes[ $i ] = esc_url( $info[0] );
									$i           = 4; // end for.
								}
							}
						}
					}
				}
			} elseif ( wp_attachment_is( 'video', $src ) ) {
				$is_video = true;
				$sizes[1] = esc_url( wp_get_attachment_url( $src ) );
			}
		} elseif ( ! empty( $src ) ) {
			$is_video = preg_match( '/\.(webm|mp4)$/', $src );
			$sizes[1] = esc_url( $src );
		}

		if ( ! count( $sizes ) ) {
			$output = '<code class="not-found">Image not found</code>';
		} elseif ( $is_video ) {
			$output = sprintf( '<video src="%s" %s %s autoplay loop muted playsinline></video>', $sizes[1], $width, $class );
		} else {
			$srcset = isset( $sizes[2] ) ? "srcset=\"{$sizes[2]} 2x" . ( isset( $sizes[3] ) ? ", {$sizes[3]} 3x\"" : '"' ) : '';
			$output = sprintf( '<img src="%s" %s %s %s %s loading="lazy">', $sizes[1], $srcset, $width, $class, $alt );
		}

		$output = preg_replace( '/\s+/', ' ', $output );

		return $output;
	}

	/**
	 * Keep text formats (italic, bold...) out of a URL or html attribute value.
	 *
	 * For replacements that output html before text formats are applied.
	 * The browser decodes these entities when it parses the attribute.
	 *
	 * @since 6.4.3
	 * @param string $value url or attribute value.
	 * @return string
	 */
	public static function protect_attr( $value ) {
		return strtr( $value, self::URL_CHARS );
	}

	/**
	 * Restore URL characters protected from text formats.
	 *
	 * @since 6.4.3
	 * @param string $url url to restore.
	 * @return string
	 */
	private static function restore_url( $url ) {
		return strtr( $url, array_flip( self::URL_CHARS ) );
	}

	/**
	 * Restore placeholder braces after save_variables() temporary encoding.
	 *
	 * @since 6.3.2
	 * @param string $string string to restore.
	 * @return string
	 */
	private static function restore_variables( $string ) {
		return str_replace( array( '&lcub;', '&rcub;' ), array( '{', '}' ), $string );
	}

	/**
	 * Restore placeholder braces and apply variable replacements.
	 *
	 * @since 6.3.2
	 * @param string $string  string to restore and replace.
	 * @param string $context context for variable replacements.
	 * @return string
	 */
	private static function restore_and_replace( $string, $context = 'html' ) {
		return Joinchat_Util::replace_variables( self::restore_variables( $string ), $context );
	}
}
