<?php
/**
 * Message formatter class.
 *
 * @package    Joinchat
 */

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
	 * Singleton instance.
	 *
	 * @since    6.0.0
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Instantiates Manager.
	 *
	 * @since    6.0.0
	 * @return Joinchat_Formatter
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	/**
	 * Initialize the class.
	 *
	 * @since    6.0.0
	 */
	private function __construct() {

		add_filter( 'joinchat_format_replacements', array( $this, 'formatter' ) );
		add_filter( 'joinchat_format_replacements', array( $this, 'save_variables' ), 100 );

		// add_action( 'joinchat_preview_header', $this, 'preview_styles' );
	}

	/**
	 * Add replacements for extended syntax
	 *
	 * @since    6.0.0
	 * @param    array $replacements       current replacements.
	 * @return   array
	 */
	public function formatter( $replacements ) {

		// MarkDown bold to WhatsApp bold.
		$before = array(
			'/(^|\W)\*\*(.+?)\*\*(\W|$)/u' => '$1*$2*$3',
			'/(^|\W)__(.+?)__(\W|$)/u'     => '$1*$2*$3',
		);

		$formats = array();

		/**
		 * WhatsApp italic
		 * *text* => <em>text</em>
		 */
		$formats['/(^|\W)_(.+?)_(\W|$)/u'] = '$1<em>$2</em>$3';

		/**
		 * WhatsApp bold
		 * *text* => <strong>text</strong>
		 */
		$formats['/(^|\W)\*(.+?)\*(\W|$)/u'] = '$1<strong>$2</strong>$3';

		/**
		 * WhatsApp strikethrough
		 * ~text~ => <del>text</del>
		 */
		$formats['/(^|\W)~(.+?)~(\W|$)/u'] = '$1<del>$2</del>$3';

		/**
		 * Markdown code
		 * `your code` => <code>your code</code>
		 */
		$formats['/(^|\W)`(.+?)`(\W|$)/u'] = '$1<code>$2</code>$3';

		/**
		 * Markdown horizontal rule
		 * --- => <hr>
		 */
		$formats['/^-{3,}$/u'] = '<hr>';

		/**
		 * Markdown image
		 * ![alt text](image.jpg width) => <img src="image.jpg" alt="alt text" width="width">
		 */
		$formats['/!\[(?<alt>.*?)\]\((?<src>[^\) ]*)(?: (?<w>\d+))?\)/u'] = array( $this, 'image' );

		/**
		 * Markdown link
		 * [title](https://www.example.com) => <a href="https://www.example.com">title</a>
		 */
		$formats['/\[(?<text>[^\[\]]+)?\]\((?<href>[^)]+)\)/u'] = array( $this, 'link' );

		/**
		 * Joinchat image
		 * {IMG image.jpg width alt_text} => <img src="image.jpg" alt="alt_text" width="width">
		 */
		$formats['/{IMG (?<src>[^ }]+)(?: (?<w>\d+))?(?: (?<alt>[^}]+))?}/u'] = array( $this, 'image' );

		/**
		 * Joinchat link
		 * {LINK https://www.example.com title} => <a href="https://www.example.com">title</a>
		 */
		$formats['/{LINK (?<href>[^ }]+)(?: (?<text>[^}]+))?}/u'] = array( $this, 'link' );

		/**
		 * Joinchat link button
		 * {BTN https://www.example.com title} => <a class="joinchat__btn" href="https://www.example.com">title</a>
		 */
		$formats['/{BTN (?<href>[^ }]+)(?: (?<text>[^}]+))?}/u'] = array( $this, 'button' );

		/**
		 * Joinchat random text
		 * {RAND texto 1||texto 2||texto n} => "texto 1" or "texto 2" or "texto n"
		 */
		$formats['/{RAND (?<text>[^}]+)}/u'] = array( $this, 'random' );

		return array_merge( $before, $replacements, $formats );

	}

	/**
	 * Preserve variables to prevents conflicts with other replacements
	 *
	 * @since    3.0.0
	 * @param    array $replacements       current replacements.
	 * @return   array
	 */
	public function save_variables( $replacements ) {

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
	public function link( $groups ) {

		$href  = $groups['href'];
		$text  = ! empty( $groups['text'] ) ? $groups['text'] : preg_replace( '/^https?:\/\//u', '', $href );
		$class = ! empty( $groups['class'] ) ? 'class="' . esc_attr( $groups['class'] ) . '"' : '';
		$rel   = '';
		$blank = '';

		// Check if is an external link and add rel attribute for better SEO & Security.
		if ( wp_parse_url( $href, PHP_URL_HOST ) !== wp_parse_url( get_home_url(), PHP_URL_HOST ) ) {
			$rel   = 'rel="external nofollow noopener noreferrer"';
			$blank = 'target="_blank"';
		}

		$output = sprintf( '<a href="%s" %s %s %s>%s</a>', esc_url_raw( $href ), $class, $rel, $blank, $text );
		$output = preg_replace( '/\s+/', ' ', $output );

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
	public function button( $groups ) {

		$groups['class'] = 'joinchat__btn';

		return $this->link( $groups );

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
	public function random( $groups ) {

		$texts = explode( '||', $groups['text'] );
		foreach ( $texts as $key => $text ) {
			$texts[ $key ] = '<jc-opt>' . trim( $text ) . '</jc-opt>';
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
	public function image( $groups ) {

		$chat_max = 340; // Chat window max width.

		$src = $groups['src'];
		$w   = isset( $groups['w'] ) && is_numeric( $groups['w'] ) ? $groups['w'] : $chat_max;

		if ( isset( $groups['alt'] ) && strpos( $groups['alt'], '&lcub;' ) !== false ) {
			$groups['alt'] = Joinchat_Util::replace_variables( str_replace( array( '&lcub;', '&rcub;' ), array( '{', '}' ), $groups['alt'] ) );
		}
		$alt = 'alt="' . ( isset( $groups['alt'] ) ? esc_attr( $groups['alt'] ) : '' ) . '"';

		// If is post featured image.
		if ( in_array( strtoupper( $src ), array( 'THUMB', 'FEATURED' ), true ) ) {
			$src = (int) get_post_thumbnail_id();
		}

		$is_video = false;
		$sizes    = array();
		$width    = $w < $chat_max ? "width=\"$w\"" : '';
		$class    = $w < $chat_max ? 'class="joinchat-inline"' : '';

		if ( is_numeric( $src ) && (int) $src > 0 ) {
			if ( wp_attachment_is( 'image', $src ) ) {
				$info = wp_get_attachment_image_src( $src, 'full' );

				if ( is_array( $info ) ) {
					if ( 0 === $info[1] ) {
						$sizes[1] = $info[0];
					} else {
						$class = min( $w, $info[1] ) < $chat_max ? 'class="joinchat-inline"' : '';

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
			$is_video = preg_match( '/\.(webp|mp4)$/', $src );
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
}
