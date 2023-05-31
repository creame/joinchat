<?php

/**
 * The preview functionality of the plugin.
 *
 * TODO: add docs
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinchatPreview {

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
	 * blank_template
	 *
	 * @param  mixed $template
	 * @return void
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
	 * hide_admin_bar
	 *
	 * @param  mixed $show_admin_bar
	 * @return void
	 */
	public function hide_admin_bar( $show_admin_bar ) {

		return false;

	}

	/**
	 * always_show
	 *
	 * @param  mixed $show
	 * @return void
	 */
	public function always_show( $show ) {

		return true;

	}

	/**
	 * Add preview classes
	 *
	 * @since 5.0.0
	 * @param  array $classes
	 * @param  array $settings
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
	 * Ensure inline styles are present
	 *
	 * @since 5.0.0
	 * @param  string $css Current inline styles.
	 * @return string
	 */
	public function inline_style( string $css ) {

		return empty( $css ) ? 'a{}' : $css;

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

		$wp_styles->queue = array_filter( $wp_styles->queue, function( $handle ) { return false !== strpos( $handle, 'joinchat' ); } ); // phpcs:ignore

	}
}
