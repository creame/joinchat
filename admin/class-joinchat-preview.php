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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @since    3.0.0     Added $tabs initilization and removed get_settings()
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->common      = JoinchatCommon::instance();

	}

	public function blank_template( $template ) {

		return JOINCHAT_DIR . 'admin/partials/page-preview.php';

	}

	public function hide_admin_bar( $show_admin_bar ) {

		return false;

	}

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

		$wp_styles->queue = array_filter(
			$wp_styles->queue,
			function( $handle ) {
				return false !== strpos( $handle, 'joinchat' );
			}
		);

	}
}
