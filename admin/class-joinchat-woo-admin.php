<?php
/**
 * The admin functionality of the WooCommerce integration.
 *
 * @package    Joinchat
 */

/**
 * The admin functionality of the WooCommerce integration.
 *
 * @since      3.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Woo_Admin {

	/**
	 * Initialize all hooks
	 *
	 * @since    3.0.0
	 * @param    Joinchat $joinchat       Joinchat object.
	 * @return   void
	 */
	public function init( $joinchat ) {

		$loader = $joinchat->get_loader();

		$loader->add_filter( 'joinchat_extra_settings', $this, 'extra_settings' );
		$loader->add_filter( 'joinchat_settings_validate', $this, 'settings_validate' );
		$loader->add_filter( 'joinchat_settings_i18n', $this, 'settings_i18n' );
		$loader->add_filter( 'joinchat_admin_tabs', $this, 'admin_tab' );
		$loader->add_filter( 'joinchat_taxonomies_meta_box', $this, 'custom_taxonomies' );
		$loader->add_filter( 'joinchat_tab_visibility_sections', $this, 'visibility_tab_section' );
		$loader->add_filter( 'joinchat_tab_woocommerce_sections', $this, 'woo_tab_sections' );
		$loader->add_filter( 'joinchat_vars_help', $this, 'vars_help', 10, 2 );
		$loader->add_filter( 'joinchat_section_output', $this, 'section_ouput', 10, 2 );
		$loader->add_filter( 'joinchat_field_output', $this, 'field_ouput', 10, 3 );
		$loader->add_filter( 'joinchat_visibility_inheritance', $this, 'visibility_inheritance' );
		$loader->add_filter( 'joinchat_help_tab_styles_and_vars', $this, 'help_tab_vars' );
		$loader->add_filter( 'joinchat_metabox_vars', $this, 'metabox_vars', 10, 2 );
		$loader->add_filter( 'joinchat_metabox_placeholders', $this, 'metabox_placeholders', 10, 3 );

		if ( defined( 'PWB_PLUGIN_FILE' ) ) { // Perfect Brands for WooCommerce.
			$loader->add_filter( 'joinchat_term_metabox_output', $this, 'term_metabox_fix', 10, 4 );
		}

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
			'woo_btn_position'     => 'none',
			'woo_btn_text'         => '',
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

		$input['message_text_product'] = Joinchat_Util::clean_input( $input['message_text_product'] );
		$input['message_text_on_sale'] = Joinchat_Util::clean_input( $input['message_text_on_sale'] );
		$input['message_send_product'] = Joinchat_Util::clean_input( $input['message_send_product'] );
		$input['woo_btn_position']     = array_key_exists( $input['woo_btn_position'], $this->btn_positions() ) ? $input['woo_btn_position'] : 'none';
		$input['woo_btn_text']         = Joinchat_Util::clean_input( $input['woo_btn_text'] );

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
		$settings['woo_btn_text']         = 'Product Button Text';

		return $settings;
	}

	/**
	 * Add WooCommerce admin tab before Advanced tab
	 *
	 * @since    3.0.0
	 * @param    array $tabs       current admin tabs.
	 * @return   array
	 */
	public function admin_tab( $tabs ) {

		$pos = array_search( 'advanced', array_keys( $tabs ), true );

		return array_merge(
			array_slice( $tabs, 0, $pos ),
			array( 'woocommerce' => 'WooCommerce' ),
			array_slice( $tabs, $pos )
		);

	}

	/**
	 * Add WooCommerce product taxonomies for metabox
	 *
	 * @since    4.3.0
	 * @param    array $taxonomies list of taxonomies.
	 * @return   array
	 */
	public function custom_taxonomies( $taxonomies ) {

		$product_taxs = array( 'product_cat', 'product_tag' );

		if ( defined( 'PWB_PLUGIN_FILE' ) ) {
			$product_taxs[] = 'pwb-brand';
		}

		return array_merge( $taxonomies, $product_taxs );

	}

	/**
	 * Return Product Button available positions
	 *
	 * Array of WooCommerce action => named position
	 *
	 * @since    4.4.0
	 * @return   array
	 */
	private function btn_positions() {

		$has_price = '%s (' . __( 'Only when product has price', 'creame-whatsapp-me' ) . ')';

		$positions = array(
			'woocommerce_single_product_summary__5'      => __( 'After title', 'creame-whatsapp-me' ),
			'woocommerce_single_product_summary__10'     => __( 'After price', 'creame-whatsapp-me' ),
			'woocommerce_single_product_summary__20'     => __( 'After short description', 'creame-whatsapp-me' ),
			'woocommerce_single_product_summary__40'     => __( 'After meta info', 'creame-whatsapp-me' ),
			'woocommerce_single_product_summary__50'     => __( 'After share links', 'creame-whatsapp-me' ),
			'woocommerce_product_additional_information' => __( 'After "Additional information"', 'creame-whatsapp-me' ),
			'woocommerce_before_add_to_cart_form'        => sprintf( $has_price, __( 'Before "Add To Cart" form', 'creame-whatsapp-me' ) ),
			'woocommerce_before_add_to_cart_button'      => sprintf( $has_price, __( 'Before "Add To Cart" button', 'creame-whatsapp-me' ) ),
			'woocommerce_after_add_to_cart_button'       => sprintf( $has_price, __( 'After "Add To Cart" button', 'creame-whatsapp-me' ) ),
			'woocommerce_after_add_to_cart_form'         => sprintf( $has_price, __( 'After "Add To Cart" form', 'creame-whatsapp-me' ) ),
		);

		return array( 'none' => __( "Don't show", 'creame-whatsapp-me' ) ) + apply_filters( 'joinchat_woo_btn_positions', $positions );

	}

	/**
	 * Woocommerce sections and fields for 'joinchat_tab_visibility'
	 *
	 * @since    3.0.0
	 * @since    4.5.18                Remove 'product' CPT.
	 * @param    array $sections       current tab sections and fields.
	 * @return   array
	 */
	public function visibility_tab_section( $sections ) {

		// Remove product CPT field.
		unset( $sections['cpt']['view__cpt_product'] );
		if ( empty( $sections['cpt'] ) ) {
			unset( $sections['cpt'] );
		}

		$pos = array_search( 'global_end', array_keys( $sections ), true );

		return array_merge(
			array_slice( $sections, 0, $pos ),
			array(
				'woo' => array(
					'view__woocommerce'  => esc_html__( 'Shop', 'creame-whatsapp-me' ),
					'view__product'      => '— ' . esc_html__( 'Product Page', 'creame-whatsapp-me' ),
					'view__cart'         => '— ' . esc_html__( 'Cart', 'creame-whatsapp-me' ),
					'view__checkout'     => '— ' . esc_html__( 'Checkout', 'creame-whatsapp-me' ),
					'view__thankyou'     => '— ' . esc_html__( 'Thank You', 'creame-whatsapp-me' ),
					'view__account_page' => '— ' . esc_html__( 'My Account', 'creame-whatsapp-me' ),
				),
			),
			array_slice( $sections, $pos )
		);
	}

	/**
	 * Woocommerce sections and fields for 'joinchat_tab_woocommerce'
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
			$woo_sections[ $key ] = sprintf( '<label for="joinchat_%s">%s</label>', $key, esc_html( $label ) ) . Joinchat_Admin_Page::vars_help( $key );
		}

		$sections['chat']   = $woo_sections;
		$sections['button'] = array(
			'woo_btn_position' => '<label for="joinchat_woo_btn_position">' . esc_html__( 'Button Position', 'creame-whatsapp-me' ) . '</label>',
			'woo_btn_text'     => '<label for="joinchat_woo_btn_text">' . esc_html__( 'Button Text', 'creame-whatsapp-me' ) . '</label>',
		);

		return $sections;
	}

	/**
	 * Woocommerce variables for messages and CTAs
	 *
	 * @since    3.0.0
	 * @param    array  $vars   current dynamic variables.
	 * @param   string $field   field name.
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

		switch ( $section_id ) {
			case 'joinchat_tab_visibility__woo':
				$output = '<h2 class="title">' . esc_html__( 'WooCommerce', 'creame-whatsapp-me' ) . '</h2>';
				break;

			case 'joinchat_tab_woocommerce__chat':
				$output = '<h2 class="title">' . esc_html__( 'Product Chat Window', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'You can define other different texts for the Chat Window on the product pages.', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_woocommerce__button':
				$output = '<hr><h2 class="title">' . esc_html__( 'Product Button', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'Add a contact button on the single product page.', 'creame-whatsapp-me' ) . '</p>';
				break;
		}

		return $output;
	}

	/**
	 * Woocommerce fields HTML output
	 *
	 * @since    3.0.0
	 * @param    string $output       current field output.
	 * @param    string $field_id     current field id.
	 * @param    array  $settings     current joinchat settings.
	 * @return   string
	 */
	public function field_ouput( $output, $field_id, $settings ) {

		$value = isset( $settings[ $field_id ] ) ? $settings[ $field_id ] : '';

		switch ( $field_id ) {
			case 'message_text_product':
				$output = '<textarea id="joinchat_message_text_product" name="joinchat[message_text_product]" rows="4" class="regular-text autofill" ' .
					'placeholder="' . esc_attr__( "This *{PRODUCT}* can be yours for only *{PRICE}*!\nIf you have any questions, ask us.", 'creame-whatsapp-me' ) . '">' .
					esc_textarea( $value ) . '</textarea>' .
					'<p class="description">' . esc_html__( 'Define a text for your products to encourage customers to contact', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'message_text_on_sale':
				$output = '<textarea id="joinchat_message_text_on_sale" name="joinchat[message_text_on_sale]" rows="4" class="regular-text autofill" ' .
					'placeholder="' . esc_attr__( "Save {DISCOUNT}! This *{PRODUCT}* can be yours for only ~{REGULAR}~ *{PRICE}*.\nIf you have any questions, ask us.", 'creame-whatsapp-me' ) . '">' .
					esc_textarea( $value ) . '</textarea>' .
					'<p class="description">' . esc_html__( 'Define a text for your products on sale to encourage customers to contact', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'message_send_product':
				$output = '<textarea id="joinchat_message_send_product" name="joinchat[message_send_product]" rows="3" class="regular-text autofill" ' .
					'placeholder="' . esc_attr__( "*Hi {SITE}!*\nI have a question about *{PRODUCT} ({SKU})*", 'creame-whatsapp-me' ) . '">' .
					esc_textarea( $value ) . '</textarea>' .
					'<p class="description">' . esc_html__( 'Predefined text for the first message the client will send you', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'woo_btn_position':
				$options = $this->btn_positions();

				$output = '<select id="joinchat_woo_btn_position" name="joinchat[woo_btn_position]">';
				foreach ( $options as $key => $option ) {
					$output .= sprintf( '<option%s value="%s">%s</option>', $key === $value ? ' selected' : '', esc_attr( $key ), esc_html( $option ) );
				}
				$output .= '</select><p class="description">' . esc_html__( 'Select the position of the button on the product page', 'creame-whatsapp-me' ) .
					'<br><small>(' . esc_html__( 'The position may vary depending on the theme or some plugins, try until you find the best location', 'creame-whatsapp-me' ) . ')</small></p>';
				break;

			case 'woo_btn_text':
				$output = '<input id="joinchat_woo_btn_text" name="joinchat[woo_btn_text]" value="' . esc_attr( $value ) . '" type="text" maxlength="40" class="regular-text autofill" placeholder="' . esc_attr__( 'Ask for More Info', 'creame-whatsapp-me' ) . '">';
				break;
		}

		return $output;
	}

	/**
	 * Modify $inheritance array to properly inherit
	 * WooCommerce fields on visibility visibily admin tab.
	 *
	 * @since    3.0.0
	 * @param    array $inheritance       current section output.
	 * @return   array
	 */
	public function visibility_inheritance( $inheritance ) {

		// 'woocommerce' inherit from 'all' (Global).
		$inheritance['all'][] = 'woocommerce';
		// WooCommerce pages inherit from 'woocommerce'.
		$inheritance['woocommerce'] = array( 'product', 'cart', 'checkout', 'thankyou', 'account_page' );

		return $inheritance;
	}

	/**
	 * Add WooCommerce variables info for help tab.
	 *
	 * @since    3.0.0
	 * @param    string $tab       current help tab content.
	 * @return   string
	 */
	public function help_tab_vars( $tab ) {

		$tab['content'] .=
			'<p> ' . wp_kses( __( '<strong>WooCommerce</strong>, in product pages you can also use:', 'creame-whatsapp-me' ), array( 'strong' => array() ) ) . '</p>' .
			'<p>' .
				'<span><code>{PRODUCT}</code> ➜ ' . esc_html__( 'Product Name', 'creame-whatsapp-me' ) . '</span><br> ' .
				'<span><code>{SKU}</code> ➜ ABC98798</span><br> ' .
				'<span><code>{PRICE}</code> ➜ ' . wp_strip_all_tags( wc_price( 7.95 ) ) . '</span> ' .
			'</p>' .
			'<p> ' . wp_kses( __( 'For the <strong>Call to Action for Products on Sale</strong>, you can also use:', 'creame-whatsapp-me' ), array( 'strong' => array() ) ) . '</p>' .
			'<p>' .
				'<span><code>{REGULAR}</code> ➜ ' . wp_strip_all_tags( wc_price( 9.95 ) ) . '</span><br> ' .
				'<span><code>{PRICE}</code> ➜ ' . wp_strip_all_tags( wc_price( 7.95 ) ) . '</span><br> ' .
				'<span><code>{DISCOUNT}</code> ➜ -20%</span>' .
			'</p>';

		return $tab;

	}

	/**
	 * Add Product metabox variables info.
	 *
	 * @since    3.0.0
	 * @param    array           $vars current default vars.
	 * @param    WP_Post|WP_Term $obj current post|term.
	 * @return   array
	 */
	public function metabox_vars( $vars, $obj ) {

		if ( $obj instanceof WP_Post && 'product' === $obj->post_type ) {
			$product  = wc_get_product( $obj->ID );
			$woo_vars = array( 'PRODUCT', 'SKU', 'PRICE' );

			if ( $product->is_on_sale() ) {
				$woo_vars[] = 'REGULAR';
				$woo_vars[] = 'DISCOUNT';
			}

			$vars = array_merge( $vars, $woo_vars );
		}

		return $vars;
	}

	/**
	 * Add Product metabox placeholders info.
	 *
	 * @since    3.2.0
	 * @param    array           $placeholders  current placeholders.
	 * @param    WP_Post|WP_Term $obj           current post|term.
	 * @param    array           $settings      current settings.
	 * @return   array
	 */
	public function metabox_placeholders( $placeholders, $obj, $settings ) {

		if ( $obj instanceof WP_Post && 'product' === $obj->post_type ) {
			$product = wc_get_product( $obj->ID );

			if ( $settings['message_send_product'] ) {
				$placeholders['message_send'] = $settings['message_send_product'];
			}

			if ( $product->is_on_sale() && $settings['message_text_on_sale'] ) {
				$placeholders['message_text'] = $settings['message_text_on_sale'];
			} elseif ( $settings['message_text_product'] ) {
				$placeholders['message_text'] = $settings['message_text_product'];
			}
		}

		return $placeholders;
	}

	/**
	 * Fix term meteabox for Brands
	 *
	 * @since    4.4.2
	 * @param    string  $metabox_output  metabox html.
	 * @param    WP_Term $term            Current taxonomy term object.
	 * @param    array   $metadata        Jonchat term_meta.
	 * @param    string  $taxonomy        Current taxonomy slug.
	 * @return   string
	 */
	public function term_metabox_fix( $metabox_output, $term, $metadata, $taxonomy ) {

		if ( 'pwb-brand' === $taxonomy ) {
			$metabox_output = '<table class="form-table">' . $metabox_output;
		}

		return $metabox_output;

	}
}
