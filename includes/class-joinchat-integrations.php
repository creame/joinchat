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
	 */
	public function load_integrations() {

		// Integration with WooCommerce
		if ( class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0', '>=' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-joinchat-wooadmin.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-joinchat-woopublic.php';

			if ( is_admin() ) {

				$plugin_wooadmin = new JoinChatWooAdmin();

				add_action( 'joinchat_run_pre', array( $plugin_wooadmin, 'init' ) );

			} else {

				$plugin_woopublic = new JoinChatWooPublic();

				add_action( 'joinchat_run_pre', array( $plugin_woopublic, 'init' ) );

			}
		}

	}

}
