<?php

/**
 * The public-facing functionality of the WooCommerce integration.
 *
 * @since      3.0.0
 * @package    JoinChat
 * @subpackage JoinChat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinChatWooPublic {

	/**
	 * Initialize all hooks
	 *
	 * @since    3.0.0
	 * @param    array $joinchat       JoinChat object.
	 * @return   void
	 */
	public function init( $joinchat ) {

		$loader = $joinchat->get_loader();

		$loader->add_filter( 'joinchat_extra_settings', $this, 'woo_settings' );
		$loader->add_filter( 'joinchat_settings_i18n', $this, 'settings_i18n' );
		$loader->add_filter( 'joinchat_get_settings_site', $this, 'shop_settings' );
		$loader->add_filter( 'joinchat_visibility', $this, 'visibility', 10, 2 );
		$loader->add_filter( 'joinchat_variable_replacements', $this, 'replacements' );
		$loader->add_filter( 'joinchat_excluded_fields', $this, 'excluded_fields' );

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

		// Applies to product pages
		if ( is_product() ) {
			$product = wc_get_product();

			if ( $product->is_on_sale() && $settings['message_text_on_sale'] ) {
				$settings['message_text'] = $settings['message_text_on_sale'];
			} else {
				$settings['message_text'] = $settings['message_text_product'] ?: $settings['message_text'];
			}
			$settings['message_send'] = $settings['message_send_product'] ?: $settings['message_send'];
		}
		// Applies to shop catalog pages
		elseif ( is_woocommerce() ) {
			$shop_settings = get_post_meta( wc_get_page_id( 'shop' ), '_joinchat', true );

			if ( is_array( $shop_settings ) ) {
				$settings = array_merge( $settings, $shop_settings );

				// Allow override general settings with empty string with "{}"
				$settings['message_text'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_text'] );
				$settings['message_send'] = preg_replace( '/^\{\s*\}$/', '', $settings['message_send'] );
			}
		}

		return $settings;
	}

	/**
	 * Return visibility for Woocommerce pages
	 *
	 * @since    3.0.0
	 * @param    null $visibility       by default $visibility is null.
	 * @return   mixed    true or false if WooCommerce page apply else return $visibility.
	 */
	public function visibility( $visibility, $options ) {

		$global = isset( $options['all'] ) ? 'yes' == $options['all'] : true;
		$woo    = isset( $options['woocommerce'] ) ? 'yes' == $options['woocommerce'] : $global;

		// Product page
		if ( is_product() ) {
			return isset( $options['product'] ) ? 'yes' == $options['product'] : $woo;
		}

		// Cart page
		if ( is_cart() ) {
			return isset( $options['cart'] ) ? 'yes' == $options['cart'] : $woo;
		}

		// Checkout page
		if ( is_checkout() ) {
			return isset( $options['checkout'] ) ? 'yes' == $options['checkout'] : $woo;
		}

		// Customer account pages
		if ( is_account_page() ) {
			return isset( $options['account_page'] ) ? 'yes' == $options['account_page'] : $woo;
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

		// Only applies to product pages
		if ( is_product() ) {
			$product = wc_get_product();

			$woo_replacements = array(
				'PRODUCT'  => $product->get_name(),
				'SKU'      => $product->get_sku(),
				'REGULAR'  => $this->get_regular_price( $product ),
				'PRICE'    => $this->get_price( $product ),
				'DISCOUNT' => $this->get_discount( $product ),
			);

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
		);

		return array_merge( $fields, $excluded );
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

		$string = strip_tags( wc_price( wc_get_price_to_display( $product, array( 'price' => $price ) ) ) );

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

}
