<?php
/**
 * The public-facing functionality of the WooCommerce integration.
 *
 * @package    Joinchat
 */

/**
 * The public-facing functionality of the WooCommerce integration.
 *
 * @since      3.0.0
 * @package    Joinchat
 * @subpackage Joinchat/public
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Woo_Public {

	/**
	 * Product Button Show
	 *
	 * @since    4.4.0
	 * @access   private
	 * @var      bool     $btn_show    Product Button is showed.
	 */
	private $btn_show = false;

	/**
	 * Initialize all hooks
	 *
	 * @since    3.0.0
	 * @param    Joinchat $joinchat       Joinchat object.
	 * @return   void
	 */
	public function init( $joinchat ) {

		$loader = $joinchat->get_loader();

		$loader->add_filter( 'joinchat_extra_settings', $this, 'woo_settings' );
		$loader->add_filter( 'joinchat_settings_i18n', $this, 'settings_i18n' );
		$loader->add_filter( 'joinchat_get_settings_site', $this, 'shop_settings' );
		$loader->add_filter( 'joinchat_get_settings', $this, 'product_settings' );
		$loader->add_filter( 'joinchat_visibility', $this, 'visibility', 10, 2 );
		$loader->add_filter( 'joinchat_variable_replacements', $this, 'replacements' );
		$loader->add_filter( 'joinchat_excluded_fields', $this, 'excluded_fields' );
		$loader->add_filter( 'joinchat_script_lite_fields', $this, 'lite_fields' );

		$loader->add_filter( 'storefront_handheld_footer_bar_links', $this, 'storefront_footer_bar' );

		$loader->add_action( 'wp_footer', $this, 'enqueue_styles' );

	}

	/**
	 * Add WooCommerce settings defaults
	 *
	 * @since    3.0.0
	 * @param    array $settings       current settings.
	 * @return   array
	 */
	public function woo_settings( $settings ) {

		$woo_settings = array(
			'message_text_product' => '',
			'message_text_on_sale' => '',
			'message_send_product' => '',
			'woo_btn_position'     => 'none',
			'woo_btn_text'         => '',
		);

		return array_merge( $settings, $woo_settings );
	}

	/**
	 * WooCommerce settings translations
	 *
	 * @since    3.1.2
	 * @param    array $settings       translatable settings.
	 * @return   array
	 */
	public function settings_i18n( $settings ) {

		$settings['message_text_product'] = 'Call to Action for Products';
		$settings['message_text_on_sale'] = 'Call to Action for Products on sale';
		$settings['message_send_product'] = 'Message for Products';
		$settings['woo_btn_text']         = 'Product Button Text';

		return $settings;
	}

	/**
	 * Replace general site CTA and send messages with the product ones
	 *
	 * @since    3.0.0
	 * @since    4.1.3 renamed from product_settings() to shop_settings()
	 * @param    array $settings       current site settings.
	 * @return   array
	 */
	public function shop_settings( $settings ) {

		// Applies to product pages.
		if ( is_product() ) {
			$product = wc_get_product();

			if ( $product->is_on_sale() && $settings['message_text_on_sale'] ) {
				$settings['message_text'] = $settings['message_text_on_sale'];
			} elseif ( $settings['message_text_product'] ) {
				$settings['message_text'] = $settings['message_text_product'];
			}
			if ( $settings['message_send_product'] ) {
				$settings['message_send'] = $settings['message_send_product'];
			}

			// Applies to shop catalog pages.
		} elseif ( is_woocommerce() ) {
			$shop_settings = get_post_meta( wc_get_page_id( 'shop' ), '_joinchat', true );

			if ( is_array( $shop_settings ) ) {
				$settings = array_merge( $settings, $shop_settings );
			}
		}

		// Add Product Button.
		if ( is_product() && 'none' !== $settings['woo_btn_position'] ) {
			list( $hook, $priority ) = explode( '__', "{$settings['woo_btn_position']}__10" );
			add_action( $hook, array( $this, 'product_button' ), (int) apply_filters( 'joinchat_woo_btn_priority', intval( $priority ) ) );
		}

		return $settings;

	}

	/**
	 * Add SKU for variable products
	 *
	 * @since    4.5.20
	 * @param    array $settings       current Joinchat settings.
	 * @return   array
	 */
	public function product_settings( $settings ) {

		if ( ! is_product() ) {
			return $settings;
		}

		$product = wc_get_product();

		if ( ! $product->is_type( 'variable' ) ) {
			return $settings;
		}

		if ( false !== strpos( $settings['message_text'], '{SKU}' ) || false !== strpos( $settings['message_send'], '{SKU}' ) ) {
			$settings['sku'] = $product->get_sku();
		}

		return $settings;

	}

	/**
	 * Return visibility for Woocommerce pages
	 *
	 * @since    3.0.0
	 * @param    null|bool $visibility  by default $visibility is null.
	 * @param    array     $options array of visibility settings.
	 * @return   mixed    true or false if WooCommerce page apply else return $visibility.
	 */
	public function visibility( $visibility, $options ) {

		$global = isset( $options['all'] ) ? 'yes' === $options['all'] : true;
		$woo    = isset( $options['woocommerce'] ) ? 'yes' === $options['woocommerce'] : $global;

		// Product page.
		if ( is_product() ) {
			return isset( $options['product'] ) ? 'yes' === $options['product'] : $woo;
		}

		// Cart page.
		if ( is_cart() ) {
			return isset( $options['cart'] ) ? 'yes' === $options['cart'] : $woo;
		}

		// Checkout page.
		if ( is_checkout() && ! is_wc_endpoint_url() ) {
			return isset( $options['checkout'] ) ? 'yes' === $options['checkout'] : $woo;
		}

		// Thankyou page.
		if ( is_wc_endpoint_url( 'order-received' ) ) {
			return isset( $options['thankyou'] ) ? 'yes' === $options['thankyou'] : $woo;
		}

		// Customer account pages.
		if ( is_account_page() ) {
			return isset( $options['account_page'] ) ? 'yes' === $options['account_page'] : $woo;
		}

		if ( is_woocommerce() ) {
			return $woo;
		}

		return $visibility;

	}

	/**
	 * Woocommerce product replacement for messages
	 *
	 * @since    3.0.0
	 * @param    array $replacements       current replacements.
	 * @return   array
	 */
	public function replacements( $replacements ) {

		// Only applies to product pages.
		if ( is_product() ) {
			$product = wc_get_product();

			$woo_replacements = array(
				'PRODUCT'  => $product->get_name(),
				'SKU'      => $product->get_sku(),
				'REGULAR'  => $this->get_regular_price( $product ),
				'PRICE'    => $this->get_price( $product ),
				'DISCOUNT' => $this->get_discount( $product ),
			);

			if ( $product->is_type( 'variable' ) ) {
				$woo_replacements['SKU'] = '<sku>' . $woo_replacements['SKU'] . '</sku>';
			}

			$replacements = array_merge( $replacements, $woo_replacements );
		}

		return $replacements;
	}

	/**
	 * Exclude unnecessary Woocommerce settings in front
	 *
	 * @since    3.0.0
	 * @param    array $fields       current excluded fields.
	 * @return   array
	 */
	public function excluded_fields( $fields ) {

		$excluded = array(
			'message_text_product',
			'message_text_on_sale',
			'message_send_product',
			'woo_btn_position',
			'woo_btn_text',
		);

		return array_merge( $fields, $excluded );
	}

	/**
	 * Add "sku" field for script lite
	 *
	 * @since    4.5.20
	 * @param    array $fields       current script lite fields.
	 * @return   array
	 */
	public function lite_fields( $fields ) {

		return array_merge( $fields, array( 'sku' ) );

	}

	/**
	 * Return text formated price.
	 * Follow WooCommerce settings for show included/excluded taxes
	 *
	 * @since    3.1.2
	 * @param    WC_Product $product   current product.
	 * @param    float      $price     price to format.
	 * @return   string formated price
	 */
	public function format_price( $product, $price ) {

		$string = html_entity_decode( wp_strip_all_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $price ) ) ) ) );

		// Escape $ for regex replacement.
		return str_replace( '$', '\$', $string );

	}

	/**
	 * Return regular price of product (if is variable return min price)
	 *
	 * @since    3.1.2
	 * @param    WC_Product $product   current product.
	 * @return   float price
	 */
	public function get_regular_price( $product ) {

		$price = 'variable' === $product->get_type() ? $product->get_variation_regular_price( 'min' ) : $product->get_regular_price();

		return $this->format_price( $product, $price );

	}

	/**
	 * Return price of product (if is variable return min price)
	 *
	 * @since    3.1.2
	 * @param    WC_Product $product   current product.
	 * @return   float price
	 */
	public function get_price( $product ) {

		$price = 'variable' === $product->get_type() ? $product->get_variation_price( 'min' ) : $product->get_price();

		return $this->format_price( $product, $price );

	}

	/**
	 * Return percent discount of product on sale
	 *
	 * @since    3.1.2
	 * @param    WC_Product $product   current product.
	 * @return   string discount
	 */
	public function get_discount( $product ) {

		$regular_price = 'variable' === $product->get_type() ? $product->get_variation_regular_price( 'min' ) : $product->get_regular_price();
		$sale_price    = 'variable' === $product->get_type() ? $product->get_variation_price( 'min' ) : $product->get_price();

		$percentage = '';
		if ( is_numeric( $regular_price ) && is_numeric( $sale_price ) && $regular_price > 0 ) {
			$percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
		}

		return $percentage ? "-$percentage%" : '';

	}

	/**
	 * Storefront theme footer bar compatibility
	 *
	 * Don't change any link but add a joinchat class to move up on mobile
	 *
	 * @since    4.1.12
	 * @param    array $links  footer bar links.
	 * @return   array
	 */
	public function storefront_footer_bar( $links ) {

		add_filter(
			'joinchat_classes',
			function( $classes ) {
				return array_merge( $classes, array( 'joinchat--footer-bar' ) );
			}
		);

		return $links;

	}

	/**
	 * Product Button output
	 *
	 * @since    4.4.0
	 * @return   void
	 */
	public function product_button() {

		// Only for main single product.
		if ( ! is_main_query() ) {
			return;
		}

		$this->btn_show = true;

		printf(
			'<div class="joinchat__woo-btn__wrapper"><div class="joinchat__woo-btn joinchat_app">%s</div></div>',
			esc_html( jc_common()->settings['woo_btn_text'] )
		);

	}

	/**
	 * Enqueue Styles
	 *
	 * @since    4.4.0
	 * @return void
	 */
	public function enqueue_styles() {

		if ( $this->btn_show && ! wp_style_is( 'joinchat', 'done' ) ) {

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_style( 'joinchat-woo', plugins_url( "css/joinchat-woo{$min}.css", __FILE__ ), array(), JOINCHAT_VERSION, 'all' );

		}

	}

}
