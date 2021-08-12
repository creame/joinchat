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

		/**
		 * WooCommerce Integration
		 */
		if ( class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0', '>=' ) ) {

			if ( is_admin() ) {

				require_once JOINCHAT_DIR . 'admin/class-joinchat-woo-admin.php';

				$plugin_woo_admin = new JoinChatWooAdmin();

				add_action( 'joinchat_run_pre', array( $plugin_woo_admin, 'init' ) );

			} else {

				require_once JOINCHAT_DIR . 'public/class-joinchat-woo-public.php';

				$plugin_woo_public = new JoinChatWooPublic();

				add_action( 'joinchat_run_pre', array( $plugin_woo_public, 'init' ) );

			}

			add_filter( 'joinchat_elementor_finder_items', array( $this, 'elementor_finder_woocommerce_item' ), 10, 2 );
		}

		/**
		 * Elementor Integration
		 */
		if ( defined( 'ELEMENTOR_VERSION' ) ) {

			if ( is_admin() ) {

				require_once JOINCHAT_DIR . 'admin/class-joinchat-elementor-admin.php';

				$plugin_elementor_admin = new JoinChatElementorAdmin();

				add_action( 'joinchat_run_pre', array( $plugin_elementor_admin, 'init' ) );

			} else {

				require_once JOINCHAT_DIR . 'public/class-joinchat-elementor-public.php';

				$plugin_elementor_public = new JoinChatElementorPublic();

				add_action( 'joinchat_run_pre', array( $plugin_elementor_public, 'init' ) );

			}

			// Add Elementor Finder integration (since 4.1.12).
			add_action( 'elementor/finder/categories/init', array( $this, 'elementor_finder_integration' ) );
		}

	}

	/**
	 * Elementor Finder integration.
	 *
	 * Add Join.chat category to Elementor Finder.
	 *
	 * @since    4.1.12
	 * @param Categories_Manager $categories_manager
	 * @return void
	 */
	public function elementor_finder_integration( $categories_manager ) {

		require_once JOINCHAT_DIR . 'includes/class-joinchat-elementor-finder.php';

		$categories_manager->add_category( 'joinchat', new JoinChatElementorFinder() );

	}

	/**
	 * Add WooCommerce item in Join.chat category for Elementor Finder.
	 *
	 * @since    4.1.12
	 * @param  array  $items current Elementor Finder joina.chat items
	 * @param  string $settings_url Join.chat settings base url
	 * @return array
	 */
	public function elementor_finder_woocommerce_item( $items, $settings_url ) {

		$items['woocommerce'] = array(
			'title'       => _x( 'WooCommerce Settings', 'Title in Elementor Finder', 'creame-whatsapp-me' ),
			'url'         => $settings_url . '&tab=woocommerce',
			'icon'        => 'woocommerce',
			'keywords'    => explode( ',', 'joinchat,whatsapp,' . _x( 'woocommerce,shop,product', 'Keywords in Elementor Finder', 'creame-whatsapp-me' ) ),
			'description' => __( 'Join.chat settings page', 'creame-whatsapp-me' ),
		);

		return $items;

	}

}
