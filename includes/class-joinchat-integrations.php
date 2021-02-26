<?php

/**
 * Define the third party plugins integration functionality.
 *
 * @since      3.0.0
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChatIntegrations {

	/**
	 * Load third party plugins integrations.
	 *
	 * @since    3.0.0
	 * @since    4.1.10 Added Elementor integration
	 */
	public function load_integrations() {

		// Integration with WooCommerce
		if ( class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0', '>=' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-joinchat-woo-admin.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-joinchat-woo-public.php';

			if ( is_admin() ) {

				$plugin_woo_admin = new JoinChatWooAdmin();

				add_action( 'joinchat_run_pre', array( $plugin_woo_admin, 'init' ) );

			} else {

				$plugin_woo_public = new JoinChatWooPublic();

				add_action( 'joinchat_run_pre', array( $plugin_woo_public, 'init' ) );

			}
		}

		// Integration with Elementor
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-joinchat-elementor-admin.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-joinchat-elementor-public.php';

			if ( is_admin() ) {

				$plugin_elementor_admin = new JoinChatElementorAdmin();

				add_action( 'joinchat_run_pre', array( $plugin_elementor_admin, 'init' ) );

			} else {

				$plugin_elementor_public = new JoinChatElementorPublic();

				add_action( 'joinchat_run_pre', array( $plugin_elementor_public, 'init' ) );

			}
		}

	}

}
