<?php
/**
 * The Joinchat Premium upsell functionality of the plugin.
 *
 * @package    Joinchat
 */

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

		$tabs['premium'] = esc_html__( 'Premium', 'creame-whatsapp-me' );

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

		return array(
			'info'   => array(),
			'addons' => array(),
		);

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

		switch ( $section_id ) {
			case 'joinchat_tab_premium__info':
				$output = '<h2 class="title">' . esc_html__( 'Premium', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						wp_kses( __( 'With <b>Joinchat Premium</b> you can enjoy exclusive features such as advanced Call to Action customization, multiple agents with scheduling of service hours, add other contact channels and much more.', 'creame-whatsapp-me' ), array( 'b' => array() ) ) . ' ' .
						esc_html__( 'In addition, you will receive specialized technical support to solve any questions or issues you may have.', 'creame-whatsapp-me' ) .
					'</p>' .
					'<p>' . esc_html__( 'Take your customer service to the next level with <b>Joinchat Premium</b>!', 'creame-whatsapp-me' ) . '</p>' .
					'<p><a class="button" href="' . esc_url( Joinchat_Util::link( 'pricing', 'cta' ) ) . '" target="_blank">' . esc_html__( 'Go Premium', 'creame-whatsapp-me' ) . '</a></p>';
				break;

			case 'joinchat_tab_premium__addons':
				$output  = '<hr><h2 class="title">' . esc_html__( 'Add-ons', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . esc_html__( 'Enable only the features you need to optimize resource load and improve your user experience.', 'creame-whatsapp-me' ) . '</p>';
				$output .= $this->premium_addons();
				break;
		}

		return $output;
	}

	/**
	 * Premium add-ons table list
	 *
	 * @since    5.0.0
	 * @return   string
	 */
	private function premium_addons() {

		$addons = array(
			'cta-extras'     => array(
				'name'        => _x( 'CTA Extras', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( '<strong>Enhance your Calls to Action.</strong> Create more engaging content in the chat window for better conversion. Add links, videos, images, animated GIFs, buttons or even embedded content from other platforms such as Calendly, surveys, forms…', 'Add-on description', 'creame-whatsapp-me' ),
			),
			'support-agents' => array(
				'name'        => _x( 'Support Agents', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( '<strong>A contact button with multiple WhatsApp numbers.</strong> Avoid collapsing your support, pre-sales or orders chat. Add as many WhatsApp numbers as you have support or sales staff. Your customers will randomly access each of them distributing the workload evenly.', 'Add-on description', 'creame-whatsapp-me' ),
			),
			'random-phone'   => array(
				'name'        => _x( 'Random Phone', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( '<strong>Contact buttons for each agent with availability times.</strong> Manage multiple WhatsApp accounts with their name, department and working hours. Your visitors will be able to contact the agent of their choice and know how long it will be until the agents are available.', 'Add-on description', 'creame-whatsapp-me' ),
			),
			'omnichannel'    => array(
				'name'        => _x( 'OmniChannel', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( '<strong>Add more contact channels.</strong> Allows you to add more contact channels (from more than 10 apps) in addition to WhatsApp. Now you can add Telegram, Facebook Messenger, Tiktok, Snapchat, SMS, phone calls, Skype, FaceTime and more.', 'Add-on description', 'creame-whatsapp-me' ),
			),
			'chat-funnels'   => array(
				'name'        => _x( 'Chat Funnels', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( '<strong>Simple funnels like a messaging chatbot.</strong> Create lead capture, qualification or support funnels by simulating conversations with a chatbot.', 'Add-on description', 'creame-whatsapp-me' ),
			),
		);

		foreach ( $addons as $slug => $addon ) {
			$addon['info']   = Joinchat_Util::link( "addons/$slug", 'upselltab' );
			$addon['docs']   = Joinchat_Util::link( "docs/setting-up-$slug", 'upselltab' );
			$addons[ $slug ] = $addon;
		}

		ob_start();
		include __DIR__ . '/partials/premium.php';
		$output = ob_get_clean();

		return $output;

	}

	/**
	 * Show header coupon
	 *
	 * @since  5.0.12
	 * @return void
	 */
	public function header_coupon() {

		$discount = '33%';
		$coupon   = 'WPJOINCHAT';

		printf(
			'<div class="joinchat-coupon">%s <span>%s</span> %s</div>',
			/* translators: %s: coupon discount. */
			esc_html( sprintf( __( '🔥 %s discount on Premium!', 'creame-whatsapp-me' ), $discount ) ),
			esc_html( $coupon ),
			sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( Joinchat_Util::link( 'wp-coupon', 'coupon' ) ), esc_html__( 'Claim Coupon', 'creame-whatsapp-me' ) )
		);
	}

}
