<?php

/**
 * Define the third party plugins integration functionality.
 *
 * @since      3.0.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/includes
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_Integrations {

	/**
	 * Load third party plugins integrations.
	 *
	 * @since    3.0.0
	 */
	public function load_integrations() {

		// Integration with WooCommerce
		if ( class_exists( 'WooCommerce' ) ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-whatsappme-wooadmin.php';
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-whatsappme-woopublic.php';

			if ( is_admin() ) {

				$plugin_wooadmin = new WhatsAppMe_WooAdmin();

				add_action( 'whatsappme_run_pre', array( $plugin_wooadmin, 'init' ) );

			} else {

				$plugin_woopublic = new WhatsAppMe_WooPublic();

				add_action( 'whatsappme_run_pre', array( $plugin_woopublic, 'init' ) );

			}
		}

	}

}
