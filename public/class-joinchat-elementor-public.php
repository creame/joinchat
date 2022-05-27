<?php

/**
 * The public-facing functionality of the Elementor integration.
 *
 * @since      4.1.10
 * @package    JoinChat
 * @subpackage JoinChat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinChatElementorPublic {

	/**
	 * Initialize all hooks
	 *
	 * @since    4.1.10
	 * @param    array $joinchat       JoinChat object.
	 * @return   void
	 */
	public function init( $joinchat ) {

		$loader = $joinchat->get_loader();

		$loader->add_filter( 'joinchat_show', $this, 'elementor_preview_disable' );

	}


	/**
	 * Hide on Elementor preview mode.
	 * Set 'show' false when is editing on Elementor
	 *
	 * @since    4.1.10
	 * @param  bool $show current show button.
	 * @return bool
	 */
	public function elementor_preview_disable( $show ) {

		$is_preview   = isset( $_GET['elementor-preview'] ) || ( isset( $_GET['render_mode'] ) && 'template-preview' === $_GET['render_mode'] ); // phpcs:ignore WordPress.Security.NonceVerification
		$preview_show = apply_filters( 'joinchat_elementor_preview_show', false );

		return $is_preview ? $show && $preview_show : $show;

	}

}
