<?php

/**
 * The Joinchat Premium upsell functionality of the plugin.
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinchatPremium {

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
			esc_url( JoinchatUtil::link( 'premium', 'action' ) ),
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

		$tabs['premium'] = __( 'Premium', 'creame-whatsapp-me' );

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
				$output = '<h2 class="title">' . __( 'Premium', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' .
						__( 'With <b>Joinchat Premium</b> you can enjoy exclusive features such as advanced Call to Action customization, multiple agents with scheduling of service hours, add other contact channels and much more.', 'creame-whatsapp-me' ) . ' ' .
						__( 'In addition, you will receive specialized technical support to solve any questions or issues you may have.', 'creame-whatsapp-me' ) .
					'</p>' .
					'<p>' . __( 'Take your customer service to the next level with <b>Joinchat Premium</b>!', 'creame-whatsapp-me' ) . '</p>' .
					'<p><a class="button" href="' . esc_url( JoinchatUtil::link( 'pricing', 'cta' ) ) . '" target="_blank">' . __( 'Go Premium', 'creame-whatsapp-me' ) . '</a></p>';
				break;

			case 'joinchat_tab_premium__addons':
				$output  = '<hr><h2 class="title">' . __( 'Add-ons', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'Enable only the features you need to optimize resource load and improve your user experience.', 'creame-whatsapp-me' ) . '</p>';
				$output .= $this->premium_addons();
				break;
		}

		return $output;
	}

	/**
	 * Premium add-ons table list
	 *
	 * @since    5.0.0
	 * @return   void
	 */
	private function premium_addons() {

		$addons = array(
			'cta-extras'     => array(
				'name'        => _x( 'CTA Extras', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( 'Use our embed tool to beautifully design the content that appears in the chat window. Drive your leads from landings to sales and conversion pages. Add links, images, animated GIFs, buttons or even embedded content from other platforms, surveys, chatbots, forms…', 'Add-on description', 'creame-whatsapp-me' ),
				'ver'         => '3.0',
			),
			'support-agents' => array(
				'name'        => _x( 'Support Agents', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( 'Managing multiple WhatsApp accounts has never been easier. Add multiple phone numbers and monitor their availability, even if your employees are out of the office, your website users will know how long it will be before they are back up and running!', 'Add-on description', 'creame-whatsapp-me' ),
				'ver'         => '4.0',
			),
			'random-phone'   => array(
				'name'        => _x( 'Random Phone', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( 'Avoid delays in support chats. Joinchat allows you to add as many phone numbers as you want. We distribute customer chats evenly among each of your support agents, so you never have to worry about bottlenecks or dropped calls again.', 'Add-on description', 'creame-whatsapp-me' ),
				'ver'         => '4.0',
			),
			'omnichannel'    => array(
				'name'        => _x( 'OmniChannel', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( 'This feature will allow you to add more chat apps to the basic plugin, in addition to WhatsApp. You can now add Telegram, Facebook Messenger, SMS, phone call, Skype and FaceTime.', 'Add-on description', 'creame-whatsapp-me' ),
				'ver'         => '3.0',
			),
			'chat-funnels'   => array(
				'name'        => _x( 'Chat Funnels', 'Add-on name', 'creame-whatsapp-me' ),
				'description' => _x( 'Nullam sagittis. Quisque libero metus, condimentum nec, tempor a, commodo mollis, magna. In ac felis quis tortor malesuada pretium.', 'Add-on description', 'creame-whatsapp-me' ), // TODO: Chat Funnels description.
				'ver'         => '1.0',
			),
		);

		foreach ( $addons as $slug => $addon ) {
			$addon['info']   = JoinchatUtil::link( "addons/$slug", 'upselltab' );
			$addon['docs']   = JoinchatUtil::link( "docs/setting-up-$slug", 'upselltab' );
			$addons[ $slug ] = $addon;
		}

		ob_start();
		include __DIR__ . '/partials/premium.php';
		$output = ob_get_clean();

		return $output;

	}

}
