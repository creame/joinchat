<?php

/**
 * The public-facing functionality of the WooCommerce integration.
 *
 * @since      3.0.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/admin
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_WooPublic {

	/**
	 * Initialize all hooks
	 *
	 * @since    3.0.0
	 * @param    array     $whatsappme       WhatsAppMe object.
	 * @return   void
	 */
	public function init($whatsappme){

		$loader = $whatsappme->get_loader();

		$loader->add_filter( 'whatsappme_extra_settings', $this, 'woo_settings' );
		$loader->add_filter( 'whatsappme_get_settings_site', $this, 'product_settings' );
		$loader->add_filter( 'whatsappme_visibility', $this, 'visibility', 10, 2 );
		$loader->add_filter( 'whatsappme_message_send_replacements', $this, 'replacements' );
		$loader->add_filter( 'whatsappme_excluded_fields', $this, 'excluded_fields' );

	}

	/**
	 * Add WooCommerce settings defaults
	 *
	 * @since    3.0.0
	 * @param    array     $settings       current settings.
	 * @return   array
	 */
	public function woo_settings($settings) {

		$woo_settings = array(
			'message_text_product'   => '',
			'message_send_product'   => '',
		);

		return array_merge( $settings, $woo_settings );
	}

	/**
	 * Replace general site CTA and send messages with the product ones
	 *
	 * @since    3.0.0
	 * @param    array     $settings       current site settings.
	 * @return   array
	 */
	public function product_settings($settings) {

		// Only applies to product pages
		if ( is_product() ) {
			if ( '' != $settings['message_text_product'] ) {
				$settings['message_text'] = apply_filters(
					'wpml_translate_single_string', $settings['message_text_product'],
					'WhatsApp me', 'Call To Action for Products' );
			}
			if ( '' != $settings['message_send_product'] ) {
				$settings['message_send'] = apply_filters(
					'wpml_translate_single_string', $settings['message_send_product'],
					'WhatsApp me', 'Message for Products' );
			}
		}

		return $settings;
	}

	/**
	 * Return visibility for Woocommerce pages
	 *
	 * @since    3.0.0
	 * @param    null     $visibility       by default $visibility is null.
	 * @return   mixed    true or false if WooCommerce page apply else return $visibility.
	 */
	public function visibility($visibility, $options) {

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
	 * @param    array     $replacements       current replacements.
	 * @return   array
	 */
	public function replacements($replacements) {

		// Only applies to product pages
		if ( is_product() ) {
			$product = wc_get_product();

			$replacements = array_merge($replacements, array(
				'/\{PRODUCT\}/i' => $product->get_name(),
				'/\{SKU\}/i'     => $product->get_sku(),
				'/\{PRICE\}/i'   => strip_tags( wc_price( $product->get_price() ) ),
			) );
		}

		return $replacements;
	}

	/**
	 * Exclude unnecessary Woocommerce settings in front
	 *
	 * @since    3.0.0
	 * @param    array     $fields       current excluded fields.
	 * @return   array
	 */
	public function excluded_fields($fields) {

		$excluded = array(
			'message_text_product',
			'message_send_product',
		);

		return array_merge( $fields, $excluded );
	}

}
