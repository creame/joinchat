<?php
/**
 * Third party plugins integration functionality.
 *
 * @package    Joinchat
 */

/**
 * Define the third party plugins integration functionality.
 *
 * @since      3.0.0
 * @package    Joinchat
 * @subpackage Joinchat/includes
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Integrations {

	/**
	 * Load third party plugins integrations.
	 *
	 * @since    3.0.0
	 * @since    4.1.10 Added Elementor integration
	 */
	public function load_integrations() {

		/**
		 * Page Builders
		 */
		add_filter( 'joinchat_show', array( $this, 'page_builder_show' ) );

		/**
		 * WooCommerce Integration
		 */
		if ( class_exists( 'WooCommerce' ) && version_compare( WC_VERSION, '3.0', '>=' ) ) {

			if ( is_admin() ) {

				require_once JOINCHAT_DIR . 'admin/class-joinchat-woo-admin.php';

				$plugin_woo_admin = new Joinchat_Woo_Admin();
				add_action( 'joinchat_run_pre', array( $plugin_woo_admin, 'init' ) );

			} else {

				require_once JOINCHAT_DIR . 'public/class-joinchat-woo-public.php';

				$plugin_woo_public = new Joinchat_Woo_Public();
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

				$plugin_elementor_admin = new Joinchat_Elementor_Admin();
				add_action( 'joinchat_run_pre', array( $plugin_elementor_admin, 'init' ) );

			}

			// Add Elementor Finder integration (since 4.1.12).
			$hook = version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ? 'elementor/finder/register' : 'elementor/finder/categories/init';
			add_action( $hook, array( $this, 'elementor_finder_integration' ) );
		}

		/**
		 * WP Rocket
		 */
		add_filter( 'rocket_rucss_external_exclusions', array( $this, 'rocket_rucss_external_exclusions' ) );
	}

	/**
	 * Elementor Finder integration.
	 *
	 * Add Joinchat category to Elementor Finder.
	 *
	 * @since    4.1.12
	 * @param Categories_Manager $categories_manager instance.
	 * @return void
	 */
	public function elementor_finder_integration( $categories_manager ) {

		require_once JOINCHAT_DIR . 'includes/class-joinchat-elementor-finder.php';

		if ( version_compare( ELEMENTOR_VERSION, '3.5.0', '>=' ) ) {
			$categories_manager->register( new Joinchat_Elementor_Finder() );
		} else {
			$categories_manager->add_category( 'joinchat', new Joinchat_Elementor_Finder() );
		}

	}

	/**
	 * Add WooCommerce item in Joinchat category for Elementor Finder.
	 *
	 * @since    4.1.12
	 * @param  array $items current Elementor Finder joina.chat items.
	 * @return array
	 */
	public function elementor_finder_woocommerce_item( $items ) {

		$items['woocommerce'] = array(
			'title'       => _x( 'WooCommerce Settings', 'Title in Elementor Finder', 'creame-whatsapp-me' ),
			'url'         => add_query_arg( 'tab', 'woocommerce', Joinchat_Util::admin_url() ),
			'icon'        => 'woocommerce',
			'keywords'    => explode( ',', 'joinchat,whatsapp,' . _x( 'woocommerce,shop,product', 'Keywords in Elementor Finder', 'creame-whatsapp-me' ) ),
			'description' => __( 'Joinchat settings page', 'creame-whatsapp-me' ),
		);

		return $items;

	}

	/**
	 * Hide on Page Builder live edition mode.
	 *
	 * @since    4.5.19
	 * @param  bool $show current show button.
	 * @return bool
	 */
	public function page_builder_show( $show ) {

		// phpcs:disable WordPress.Security.NonceVerification
		$is_builder = false
			|| isset( $_GET['fl_builder'] )                                                     // Beaver Builder.
			|| isset( $_GET['is-editor-iframe'] )                                               // Brizy Page Builder.
			|| isset( $_GET['elementor-preview'] )                                              // Elementor editor.
			|| ( isset( $_GET['render_mode'] ) && 'template-preview' === $_GET['render_mode'] ) // Elementor template preview.
			|| isset( $_GET['ct_builder'] )                                                     // Oxygen Builder.
			|| isset( $_GET['siteorigin_panels_live_editor'] )                                  // Page Builder by SiteOrigin.
			|| isset( $_GET['vcv-editable'] )                                                   // Visual Composer.
			|| ( isset( $_GET['load_for'] ) && 'wppb_editor_iframe' === $_GET['load_for'] );    // WP Page Builder.
		// phpcs:enable

		$builder_show = apply_filters( 'joinchat_page_builder_show', false );

		return $is_builder ? $show && $builder_show : $show;

	}

	/**
	 * Exclude CSS Files styles from being removed by WP Rocket’s Remove Unused CSS optimization.
	 *
	 * @since    5.0.9
	 * @param  array $external_exclusions List of excluded CSS files.
	 * @return array
	 */
	public function rocket_rucss_external_exclusions( $external_exclusions = array() ) {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		$files = array(
			plugins_url( "public/css/joinchat{$min}.css", JOINCHAT_FILE ),
			plugins_url( "public/css/joinchat-btn{$min}.css", JOINCHAT_FILE ),
			plugins_url( "public/css/joinchat-woo{$min}.css", JOINCHAT_FILE ),
		);

		$parts       = explode( '/', WP_CONTENT_URL );
		$content_dir = '/' . trailingslashit( end( $parts ) );

		foreach ( $files as $file ) {
			$external_exclusions[] = substr( $file, strpos( $file, $content_dir ) );
		}

		return $external_exclusions;

	}
}
