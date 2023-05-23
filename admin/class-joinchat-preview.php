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
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Common class for admin and front methods.
	 *
	 * @since    4.2.0
	 * @access   private
	 * @var      JoinchatCommon    $common    instance.
	 */
	private $common;

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

	public function preview_classes( $joinchat_classes, $settings ) {

		if ( '' === $settings['telephone'] ) {
			$joinchat_classes[] = 'joinchat--disabled';
		}
		if ( $settings['mobile_only'] ) {
			$joinchat_classes[] = 'joinchat--mobile_only';
		}

		return $joinchat_classes;

	}

	public function dequeue_script( string $src, string $handle ) {

		return 'joinchat' === $handle ? '' : $src;

	}

}
