<?php

/**
 * The Joinchat Premium upsell functionality of the plugin.
 *
 * @since      5.0.0
 * @package    JoinChat
 * @subpackage JoinChat/admin
 * @author     Creame <hola@crea.me>
 */
class JoinChatPremium {

	/**
	 * The ID of this plugin.
	 *
	 * @since    5.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    5.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    5.0.0
	 * @param    string $plugin_name       The name of this plugin.
	 * @param    string $version           The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Add link to options page on plugins page
	 *
	 * @since    5.0.0
	 * @access   public
	 * @param    array $links       current plugin links.
	 * @return   array
	 */
	public function action_link( $links ) {

		$utm  = '?utm_source=action&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
		$lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

		$links['premium'] = sprintf(
			'<a href="%1$s" target="_blank" style="font-weight:bold;color:#f9603a;">%2$s</a>',
			esc_url( "https://join.chat/$lang/premium/$utm" ),
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
				// TODO: add upsell message and links.
				$output = '<h2 class="title">' . __( 'Premium', 'creame-whatsapp-me' ) . '</h2>' .
					'<p>' . __( 'SUPER AWESOME STUFF!!', 'creame-whatsapp-me' ) . '</p>';
				break;

			case 'joinchat_tab_premium__addons':
				$this->premium_addons();
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

		$utm  = '?utm_source=premiumtab&utm_medium=wpadmin&utm_campaign=v' . str_replace( '.', '_', $this->version );
		$lang = false !== strpos( strtolower( get_locale() ), 'es' ) ? 'es' : 'en';

		$addons = array(
			'cta-extras'     => array(
				'name'        => 'CTA Extras',
				'description' => 'Use our embed tool to beautifully design the content that appears in the chat window. Drive your leads from landings to sales and conversion pages. Add links, images, animated GIFs, buttons or even embedded content from other platforms, surveys, chatbots, forms…',
				'ver'         => '2.6',
				'info_en'     => 'https://join.chat/en/addons/cta-extras/',
				'docs_en'     => 'https://join.chat/en/docs/setting-up-cta-extras/',
				'info_es'     => 'https://join.chat/es/addons/cta-extras/',
				'docs_es'     => 'https://join.chat/es/docs/configurando-cta-extras/',
			),
			'support-agents' => array(
				'name'        => 'Support Agents',
				'description' => 'Managing multiple WhatsApp accounts has never been easier. Add multiple phone numbers and monitor their availability, even if your employees are out of the office, your website users will know how long it will be before they are back up and running!',
				'ver'         => '3.7',
				'info_en'     => 'https://join.chat/en/addons/support-agents/',
				'docs_en'     => 'https://join.chat/en/docs/setting-up-support-agents/',
				'info_es'     => 'https://join.chat/es/addons/agentes-de-soporte/',
				'docs_es'     => 'https://join.chat/es/docs/configurando-agentes-de-soporte/',
			),
			'random-phone'   => array(
				'name'        => 'Random Phone',
				'description' => 'Avoid delays in support chats. Joinchat allows you to add as many phone numbers as you want. We distribute customer chats evenly among each of your support agents, so you never have to worry about bottlenecks or dropped calls again.',
				'ver'         => '3.5',
				'info_en'     => 'https://join.chat/en/addons/random-phone/',
				'docs_en'     => 'https://join.chat/en/docs/setting-up-cta-extras/',
				'info_es'     => 'https://join.chat/es/addons/random-phone/',
				'docs_es'     => 'https://join.chat/es/docs/configurando-random-phone/',
			),
			'omnichannel'    => array(
				'name'        => 'OmniChannel',
				'description' => 'This feature will allow you to add more chat apps to the basic plugin, in addition to WhatsApp. You can now add Telegram, Facebook Messenger, SMS, phone call, Skype and FaceTime.',
				'ver'         => '2.0',
				'info_en'     => 'https://join.chat/en/addons/omnichannel/',
				'docs_en'     => 'https://join.chat/en/docs/setting-up-omnichannel/',
				'info_es'     => 'https://join.chat/es/addons/omnichannel/',
				'docs_es'     => 'https://join.chat/es/docs/configurando-omnichannel/',
			),
			'chat-funnels'   => array(
				'name'        => 'Chat Funnels',
				'description' => 'Nullam sagittis. Quisque libero metus, condimentum nec, tempor a, commodo mollis, magna. In ac felis quis tortor malesuada pretium.', // TODO: Chat Funnels description.
				'ver'         => '1.0',
				'info_en'     => 'https://join.chat/en/addons/chat-funnels/',
				'docs_en'     => 'https://join.chat/en/docs/setting-up-chat-funnels/',
				'info_es'     => 'https://join.chat/es/addons/chat-funnels/',
				'docs_es'     => 'https://join.chat/es/docs/setting-up-chat-funnels/',
			),
		);

		include __DIR__ . '/partials/premium.php';

	}

}
