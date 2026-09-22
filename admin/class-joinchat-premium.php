<?php
/**
 * The Joinchat Premium upsell functionality of the plugin.
 *
 * @package    Joinchat
 */

defined( 'WPINC' ) || exit;

/**
 * The Joinchat Premium upsell functionality of the plugin.
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Premium {

	/**
	 * Add link to options page on plugins page
	 *
	 * @since    5.0.0
	 * @access   public
	 * @param    array $links       current plugin links.
	 * @return   array
	 */
	public function action_link( $links ) {

		$links['premium'] = sprintf(
			'<a href="%1$s" target="_blank" style="font-weight:bold;color:#f9603a;">%2$s</a>',
			esc_url( Joinchat_Util::link( 'premium', 'action' ) ),
			esc_html__( 'Premium', 'creame-whatsapp-me' )
		);

		return $links;

	}

	/**
	 * Add Premium admin tab
	 *
	 * @since    5.0.0
	 * @param    array $tabs       current admin tabs.
	 * @return   array
	 */
	public function admin_tab( $tabs ) {

		$tabs['premium'] = esc_html__( 'Go Premium', 'creame-whatsapp-me' );

		return $tabs;
	}

	/**
	 * Premium sections and fields for 'joinchat_tab_premium'
	 *
	 * @since    5.0.0
	 * @param    array $sections       current tab sections and fields.
	 * @return   array
	 */
	public function tab_sections( $sections ) {

		return array( 'info' => array() );

	}

	/**
	 * Premium sections HTML output
	 *
	 * @since    5.0.0
	 * @param    string $output       current section output.
	 * @param    string $section_id   current section id.
	 * @return   string
	 */
	public function section_ouput( $output, $section_id ) {

		if ( 'joinchat_tab_premium__info' === $section_id ) {
			ob_start();
			include __DIR__ . '/partials/premium.php';
			$output = ob_get_clean();

			return $output;
		}

		if ( 'joinchat_tab_visibility__global' === $section_id ) {
			$ad = sprintf(
				'<a class="joinchat-ad" href="#go-premium" aria-hidden="true">%s <span>Premium</span></a>',
				esc_html__( 'Add visibility by country', 'creame-whatsapp-me' )
			);

			return str_replace( '</a>', '</a> ' . $ad, $output );
		}

		return $output;
	}

	/**
	 * Show header coupon
	 *
	 * @since  5.0.12
	 * @return void
	 */
	public function header_coupon() {

		printf(
			'<a class="joinchat-coupon joinchat-coupon--ai" href="%s" target="_blank" aria-hidden="true">%s</a>',
			esc_url( Joinchat_Util::link( 'wp-try-ai', 'coupon' ) ),
			esc_html__( 'AI AGENT. Try it free for 15 days', 'creame-whatsapp-me' )
		);

		printf(
			'<a class="joinchat-coupon" href="%s" target="_blank" aria-hidden="true">%s</a>',
			esc_url( Joinchat_Util::link( 'wp-coupon', 'coupon' ) ),
			esc_html__( 'Unlock Premium. NOW ON SALE', 'creame-whatsapp-me' )
		);

	}

	/**
	 * Add premium ads to specific fields.
	 *
	 * @since  6.4.0
	 * @param  string $output    current field output.
	 * @param  string $field_id  current field id.
	 * @param  array  $settings  current field settings.
	 * @return string
	 */
	public function field_ads( $output, $field_id, $settings ) {

		if ( 'telephone' === $field_id ) {
			$ad = sprintf(
				'<a class="joinchat-ad" href="#go-premium" aria-hidden="true">%s <span>Premium</span></a>',
				esc_html__( 'Add more phones', 'creame-whatsapp-me' )
			);

			return str_replace( '<p class="description"', $ad . '<p class="description"', $output );
		}

		if ( 'button_ico' === $field_id ) {
			$ad = sprintf(
				'<a class="joinchat-ad" href="#go-premium" aria-hidden="true">%s <span>Premium</span></a>',
				esc_html__( 'Add more channels', 'creame-whatsapp-me' )
			);

			return str_replace( '<p class="description"', $ad . '<p class="description"', $output );
		}

		if ( 'message_text' === $field_id ) {
			$ad = sprintf(
				'<a class="joinchat-ad" href="#go-premium" aria-hidden="true">%s <span>Premium</span></a>',
				esc_html__( 'Automates support', 'creame-whatsapp-me' )
			);

			return str_replace( '<p class="description"', $ad . '<p class="description"', $output );
		}

		if ( 'gads' === $field_id ) {
			$ad = sprintf(
				'<a class="joinchat-ad" href="#go-premium" aria-hidden="true">%s <span>Premium</span></a>',
				esc_html__( 'Advanced tracking on GA4/GTM', 'creame-whatsapp-me' )
			);

			return str_replace( '</p>', ' ' . $ad . '</p>', $output );
		}

		return $output;

	}
}
