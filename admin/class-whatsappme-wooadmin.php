<?php

/**
 * The admin-specific functionality of the WooCommerce integration.
 *
 * @since      3.0.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/admin
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_WooAdmin {

	/**
	 * Initialize all hooks
	 *
	 * @since    3.0.0
	 * @param    array $whatsappme       WhatsAppMe object.
	 * @return   void
	 */
	public function init( $whatsappme ) {

		$loader = $whatsappme->get_loader();

		$loader->add_filter( 'whatsappme_extra_settings', $this, 'extra_settings' );
		$loader->add_filter( 'whatsappme_settings_validate', $this, 'settings_validate' );
		$loader->add_filter( 'whatsappme_settings_i18n', $this, 'settings_i18n' );
		$loader->add_filter( 'whatsappme_admin_tabs', $this, 'admin_tab' );
		$loader->add_filter( 'whatsappme_custom_post_types', $this, 'custom_post_types' );
		$loader->add_filter( 'whatsappme_tab_advanced_sections', $this, 'advanced_tab_section' );
		$loader->add_filter( 'whatsappme_tab_woocommerce_sections', $this, 'woo_tab_sections' );
		$loader->add_filter( 'whatsappme_vars_help', $this, 'vars_help', 10, 2 );
		$loader->add_filter( 'whatsappme_section_output', $this, 'section_ouput', 10, 2 );
		$loader->add_filter( 'whatsappme_field_output', $this, 'field_ouput', 10, 3 );
		$loader->add_filter( 'whatsappme_advanced_inheritance', $this, 'advanced_inheritance' );
		$loader->add_filter( 'whatsappme_styles_and_vars_help', $this, 'help_vars' );
		$loader->add_filter( 'whatsappme_metabox_vars', $this, 'metabox_vars' );
	}

	/**
	 * Add WooCommerce extra settings defaults
	 *
	 * @since    3.0.0
	 * @param    array $settings       current settings.
	 * @return   array
	 */
	public function extra_settings( $settings ) {

		$woo_settings = array(
			'message_text_product' => '',
			'message_text_on_sale' => '',
			'message_send_product' => '',
		);

		return array_merge( $settings, $woo_settings );
	}

	/**
	 * WooCommerce settings validation
	 *
	 * @since    3.0.0
	 * @param    array $input       form input.
	 * @return   array
	 */
	public function settings_validate( $input ) {

		$input['message_text_product'] = WhatsAppMe_Util::clean_input( $input['message_text_product'] );
		$input['message_text_on_sale'] = WhatsAppMe_Util::clean_input( $input['message_text_on_sale'] );
		$input['message_send_product'] = WhatsAppMe_Util::clean_input( $input['message_send_product'] );

		return $input;
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
		$settings['message_text_on_sale'] = 'Call to Action for Products on Sale';
		$settings['message_send_product'] = 'Message for Products';

		return $settings;
	}

	/**
	 * Add WooCommerce admin tab
	 *
	 * @since    3.0.0
	 * @param    array $tabs       current admin tabs.
	 * @return   array
	 */
	public function admin_tab( $tabs ) {

		$tabs['woocommerce'] = 'WooCommerce';

		return $tabs;
	}

	/**
	 * Remove WooCommerce product custom post type
	 *
	 * @since    3.0.0
	 * @param    array $custom_post_types       current tab sections and fields.
	 * @return   array
	 */
	public function custom_post_types( $custom_post_types ) {

		$custom_post_types = array_diff( $custom_post_types, array( 'product' ) );

		return $custom_post_types;
	}

	/**
	 * Woocommerce sections and fields for 'whatsappme_tab_advanced'
	 *
	 * @since    3.0.0
	 * @param    array $sections       current tab sections and fields.
	 * @return   array
	 */
	public function advanced_tab_section( $sections ) {

		$sections['woo'] = array(
			'view__woocommerce'  => __( 'Shop', 'creame-whatsapp-me' ),
			'view__product'      => '— ' . __( 'Product Page', 'creame-whatsapp-me' ),
			'view__cart'         => '— ' . __( 'Cart', 'creame-whatsapp-me' ),
			'view__checkout'     => '— ' . __( 'Checkout', 'creame-whatsapp-me' ),
			'view__account_page' => '— ' . __( 'My Account', 'creame-whatsapp-me' ),
		);

		return $sections;
	}

	/**
	 * Woocommerce sections and fields for 'whatsappme_tab_woocommerce'
	 *
	 * @since    3.0.0
	 * @param    array $sections       current tab sections and fields.
	 * @return   array
	 */
	public function woo_tab_sections( $sections ) {

		$woo_sections = array(
			'message_text_product' => __( 'Call to Action for Products', 'creame-whatsapp-me' ),
			'message_text_on_sale' => __( 'Call to Action for Products on Sale', 'creame-whatsapp-me' ),
			'message_send_product' => __( 'Message for Products', 'creame-whatsapp-me' ),
		);

		foreach ( $woo_sections as $key => $label ) {
			$woo_sections[ $key ] = "<label for=\"whatsappme_$key\">$label</label>" . WhatsAppMe_Admin::vars_help( $key );
		}

		$sections['chat'] = $woo_sections;

		return $sections;
	}

	/**
	 * Woocommerce sections and fields for 'whatsappme_tab_woocommerce'
	 *
	 * @since    3.0.0
	 * @param    array $sections       current tab sections and fields.
	 * @return   array
	 */
	public function vars_help( $vars, $field ) {

		if ( 'message_text_product' === $field || 'message_send_product' === $field ) {
			$vars = array_merge( $vars, array( 'PRODUCT', 'SKU', 'PRICE' ) );
		} elseif ( 'message_text_on_sale' === $field ) {
			$vars = array_merge( $vars, array( 'PRODUCT', 'SKU', 'REGULAR', 'PRICE', 'DISCOUNT' ) );
		}

		return $vars;
	}

	/**
	 * Woocommerce sections HTML output
	 *
	 * @since    3.0.0
	 * @param    string $output       current section output.
	 * @param    string $section_id   current section id.
	 * @return   string
	 */
	public function section_ouput( $output, $section_id ) {

		if ( 'whatsappme_tab_advanced__woo' == $section_id ) {

			$output = '<h2 class="title">' . __( 'WooCommerce', 'creame-whatsapp-me' ) . '</h2>';

		} elseif ( 'whatsappme_tab_woocommerce__chat' == $section_id ) {

			$output = '<h2 class="title">' . __( 'Product Chat Window', 'creame-whatsapp-me' ) . '</h2>' .
				'<p>' .
				__( 'You can define other different texts for the Chat Window on the product pages.', 'creame-whatsapp-me' ) .
				'</p>';

		}

		return $output;
	}

	/**
	 * Woocommerce fields HTML output
	 *
	 * @since    3.0.0
	 * @param    string $output       current field output.
	 * @param    string $field_id     current field id.
	 * @param    array  $settings     current whatsappme settings.
	 * @return   string
	 */
	public function field_ouput( $output, $field_id, $settings ) {

		$value = isset( $settings[ $field_id ] ) ? $settings[ $field_id ] : '';

		switch ( $field_id ) {
			case 'message_text_product':
				$output = '<textarea id="whatsappme_message_text_product" name="whatsappme[message_text_product]" rows="4" class="regular-text" ' .
					'placeholder="' . esc_attr__( "This *{PRODUCT}* can be yours for only *{PRICE}*!\nIf you have any questions, ask us.", 'creame-whatsapp-me' ) . '">' .
					$value . '</textarea>' .
					'<p class="description">' . __( 'Define a text for your products to encourage customers to contact', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'message_text_on_sale':
				$output = '<textarea id="whatsappme_message_text_on_sale" name="whatsappme[message_text_on_sale]" rows="4" class="regular-text" ' .
					'placeholder="' . esc_attr__( "Save {DISCOUNT}! This *{PRODUCT}* can be yours for only ~{REGULAR}~ *{PRICE}*.\nIf you have any questions, ask us.", 'creame-whatsapp-me' ) . '">' .
					$value . '</textarea>' .
					'<p class="description">' . __( 'Define a text for your products on sale to encourage customers to contact', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'message_send_product':
				$output = '<textarea id="whatsappme_message_send_product" name="whatsappme[message_send_product]" rows="3" class="regular-text" ' .
					'placeholder="' . esc_attr__( "*Hi {SITE}!*\nI have a question about *{PRODUCT} ({SKU})*", 'creame-whatsapp-me' ) . '">' . $value . '</textarea>' .
					'<p class="description">' . __( 'Predefined text for the first message the client will send you', 'creame-whatsapp-me' ) . '</p>';
				break;
		}

		return $output;
	}

	/**
	 * Modify $inheritance array to properly inherit
	 * WooCommerce fields on advanced visibily admin tab.
	 *
	 * @since    3.0.0
	 * @param    array $inheritance       current section output.
	 * @return   array
	 */
	public function advanced_inheritance( $inheritance ) {

		// 'woocommerce' inherit from 'all' (Global)
		$inheritance['all'][] = 'woocommerce';
		// WooCommerce pages inherit from 'woocommerce'
		$inheritance['woocommerce'] = array( 'product', 'cart', 'checkout', 'account_page' );

		return $inheritance;
	}

	/**
	 * Add WooCommerce variables info for help tab.
	 *
	 * @since    3.0.0
	 * @param    string $text       current help tab content.
	 * @return   string
	 */
	public function help_vars( $text ) {

		return $text .
			'<p> ' . __( '<strong>WooCommerce</strong>, in product pages you can also use:', 'creame-whatsapp-me' ) . '</p>' .
			'<p>' .
				'<span><code>{PRODUCT}</code>  ➜ ' . __( 'Product Name', 'creame-whatsapp-me' ) . '</span>, ' .
				'<span><code>{SKU}</code>  ➜ ABC98798</span>, ' .
				'<span><code>{PRICE}</code>  ➜ ' . strip_tags( wc_price( 7.95 ) ) . '</span> ' .
			'</p>' .
			'<p> ' . __( 'For the <strong>Call to Action for Products on Sale</strong>, you can also use:', 'creame-whatsapp-me' ) . '</p>' .
			'<p>' .
				'<span><code>{REGULAR}</code>  ➜ ' . strip_tags( wc_price( 9.95 ) ) . '</span>, ' .
				'<span><code>{PRICE}</code>  ➜ ' . strip_tags( wc_price( 7.95 ) ) . '</span>, ' .
				'<span><code>{DISCOUNT}</code>  ➜ -20%</span>' .
			'</p>';

	}

	/**
	 * Add Product metabox variables info.
	 *
	 * @since    3.0.0
	 * @param    array $vars       current default vars.
	 * @return   array
	 */
	public function metabox_vars( $vars ) {
		global $post;

		if ( 'product' == $post->post_type ) {
			$vars = array_merge( $vars, array( 'PRODUCT', 'PRICE', 'SKU' ) );
		}

		return $vars;
	}
}
