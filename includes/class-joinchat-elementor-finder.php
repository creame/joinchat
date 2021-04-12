<?php

/**
 * Join.chat category for Elementor Finder.
 *
 * @since      4.1.12
 * @package    JoinChat
 * @subpackage JoinChat/includes
 * @author     Creame <hola@crea.me>
 */
class JoinChatElementorFinder extends \Elementor\Core\Common\Modules\Finder\Base_Category {

	/**
	 * Get category title.
	 *
	 * @since 4.1.12
	 * @return string
	 */
	public function get_title() {

		return 'Join.chat';

	}

	/**
	 * Get category items.
	 *
	 * @since 4.1.12
	 * @param array $options
	 * @return array $items array of Finder items.
	 */
	public function get_category_items( array $options = array() ) {

		$settings_url = admin_url( 'options-general.php' ) . '?page=joinchat';

		$items = array(
			'general'    => array(
				'title'       => _x( 'General Settings', 'Title in Elementor Finder', 'creame-whatsapp-me' ),
				'url'         => $settings_url,
				'icon'        => 'settings',
				'keywords'    => explode( ',', 'joinchat,whatsapp,' . _x( 'settings,phone', 'Keywords in Elementor Finder', 'creame-whatsapp-me' ) ),
				'description' => __( 'Join.chat settings page', 'creame-whatsapp-me' ),
			),
			'visibility' => array(
				'title'       => _x( 'Visibility Settings', 'Title in Elementor Finder', 'creame-whatsapp-me' ),
				'url'         => $settings_url . '&tab=visibility',
				'icon'        => 'eye',
				'keywords'    => explode( ',', 'joinchat,whatsapp,' . _x( 'visibility,show,hide', 'Keywords in Elementor Finder', 'creame-whatsapp-me' ) ),
				'description' => __( 'Join.chat settings page', 'creame-whatsapp-me' ),
			),
		);

		return apply_filters( 'joinchat_elementor_finder_items', $items, $settings_url );

	}

}
