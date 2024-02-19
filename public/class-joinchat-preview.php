<?php
/**
 * The preview functionality of the plugin.
 *
 * @package    Joinchat
 */

/**
 * The preview functionality of the plugin.
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/public
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Preview {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    5.0.0
	 */
	public function __construct() {

		jc_common()->preview = true;
		jc_common()->qr      = true;

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

	}

	/**
	 * Use blank template for preview
	 *
	 * @since      5.0.0
	 * @param  string $template current template.
	 * @return string
	 */
	public function blank_template( $template ) {

		return JOINCHAT_DIR . 'admin/partials/page-preview.php';

	}

	/**
	 * Disable page custom Joinchat settings ('_joinchat' postmeta)
	 *
	 * @since 5.0.0
	 * @param  mixed  $value      The value to return.
	 * @param  int    $object_id  ID of the object metadata is for.
	 * @param  string $meta_key   Metadata key.
	 * @return mixed
	 */
	public function disable_postmeta( $value, $object_id, $meta_key ) {

		return '_joinchat' === $meta_key ? false : $value;

	}

	/**
	 * Hide admin bar
	 *
	 * @since      5.0.0
	 * @param  bool $show_admin_bar Should show admin admin bar.
	 * @return bool
	 */
	public function hide_admin_bar( $show_admin_bar ) {

		return false;

	}

	/**
	 * Force to show Joinchat html
	 *
	 * @since      5.0.0
	 * @param  bool $show Should show Joinchat.
	 * @return bool
	 */
	public function always_show( $show ) {

		return true;

	}

	/**
	 * Add preview classes
	 *
	 * @since 5.0.0
	 * @param  array $classes  Current Joinchat classes.
	 * @param  array $settings Current Joinchat settings.
	 * @return array
	 */
	public function preview_classes( $classes, $settings ) {

		if ( '' === $settings['telephone'] ) {
			$classes[] = 'joinchat--disabled';
		}
		if ( $settings['mobile_only'] ) {
			$classes[] = 'joinchat--mobile_only';
		}

		return $classes;

	}

	/**
	 * Don't do nothing but ensures load Joinchat styles
	 *
	 * @since 5.0.0
	 * @param  string $content Joinchat html string.
	 * @return string
	 */
	public function preview_content( $content ) {

		return $content;

	}

	/**
	 * Change Joinchat html template
	 *
	 * @since 5.0.0
	 * @param  string $template Joinchat html template path.
	 * @return string
	 */
	public function preview_template( $template ) {

		return str_replace( '/html.php', '/preview.php', $template );

	}

	/**
	 * Ensure inline styles are present
	 *
	 * @since 5.0.0
	 * @param  string $css Current inline styles.
	 * @return string
	 */
	public function inline_style( string $css ) {

		return empty( $css ) ? 'body{}' : $css;

	}

	/**
	 * Remove all scripts (except jQuery)
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function remove_all_scripts() {

		global $wp_scripts;

		$wp_scripts->queue = array( 'jquery', 'joinchat-qr' );

	}

	/**
	 * Remove all non Joinchat styles
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function remove_all_styles() {

		global $wp_styles;

		$wp_styles->queue = array_filter( $wp_styles->queue, function( $handle ) { // phpcs:ignore
			return false !== strpos( $handle, 'joinchat' );
		} ); // phpcs:ignore

	}

	/**
	 * Preview header actions
	 *
	 * @since 5.0.0
	 * @return void
	 */
	public function preview_header() {

		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		wp_enqueue_scripts();
		wp_print_scripts();
		wp_print_styles();

	}
}
