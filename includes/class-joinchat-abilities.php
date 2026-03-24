<?php
/**
 * Abilities API integration
 *
 * Registers Joinchat abilities for WordPress Abilities API.
 * Enables AI agents and automation tools to discover and use Joinchat features.
 *
 * @link       https://join.chat
 * @since      6.1.0
 *
 * @package    Joinchat
 * @subpackage Joinchat/includes
 */

/**
 * Abilities class
 */
class Joinchat_Abilities {

	/**
	 * Ability namespace
	 */
	const NAMESPACE = 'joinchat';

	/**
	 * Ability category for WhatsApp Contact
	 */
	const CATEGORY = 'whatsapp-contact';

	/**
	 * Initialize abilities registration
	 *
	 * Hooks into the correct actions for categories and abilities.
	 *
	 * @since 6.1.0
	 */
	public static function init() {

		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );

	}

	/**
	 * Register ability categories
	 *
	 * Called on wp_abilities_api_categories_init hook.
	 *
	 * @since 6.1.0
	 */
	public static function register_categories() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}

		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'WhatsApp Contact', 'creame-whatsapp-me' ),
				'description' => __( 'WhatsApp contact and communication abilities', 'creame-whatsapp-me' ),
			)
		);
	}

	/**
	 * Register all Joinchat abilities
	 *
	 * Called on wp_abilities_api_init hook.
	 *
	 * @since 6.1.0
	 */
	public static function register_abilities() {
		// Verify API is available.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Register abilities.
		self::register_generate_whatsapp_url();
		self::register_get_phone();
		self::register_set_phone();
		self::register_set_cta();
		self::register_set_message();
	}

	/**
	 * Permission callback for abilities that can be accessed by editors if context is provided
	 *
	 * Allows editors to get context-specific phone numbers, but requires manager capability for global access.
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters to check for context.
	 * @return bool
	 */
	public static function check_permission( $input ) {
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
		$term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;

		$cap = $post_id > 0 || $term_id > 0 ? 'edit_posts' : Joinchat_Util::capability();

		return current_user_can( $cap );
	}

	/**
	 * Register generate-url ability
	 *
	 * Generates WhatsApp click-to-chat URL with phone and message.
	 *
	 * @since 6.1.0
	 */
	private static function register_generate_whatsapp_url() {
		wp_register_ability(
			self::NAMESPACE . '/get-whatsapp-url',
			array(
				'label'               => __( 'Generate WhatsApp URL', 'creame-whatsapp-me' ),
				'description'         => __( 'Generate WhatsApp "click to chat" URL with phone and pre-filled message', 'creame-whatsapp-me' ),
				'category'            => self::CATEGORY,
				'permission_callback' => '__return_true',
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'phone'   => array(
							'type'        => 'string',
							'description' => __( 'WhatsApp phone number (with country code)', 'creame-whatsapp-me' ),
							'required'    => true,
						),
						'message' => array(
							'type'        => 'string',
							'description' => __( 'Optional pre-filled message text', 'creame-whatsapp-me' ),
							'default'     => '',
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'string',
					'description' => __( 'WhatsApp (click to chat) URL', 'creame-whatsapp-me' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_generate_whatsapp_url' ),
				'meta'                => array(
					'annotations'  => array(
						'readonly' => true,
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Execute generate-url ability
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters.
	 * @return string WhatsApp URL.
	 */
	public static function execute_generate_whatsapp_url( $input = array() ) {
		$phone   = isset( $input['phone'] ) ? $input['phone'] : '';
		$message = isset( $input['message'] ) ? $input['message'] : '';

		$phone = Joinchat_Util::clean_whatsapp( $phone );

		if ( empty( $phone ) ) {
			return '';
		}

		$url = "https://wa.me/{$phone}";

		if ( ! empty( $message ) ) {
			$url .= '?text=' . rawurlencode( $message );
		}

		return $url;
	}

	/**
	 * Register get-phone ability
	 *
	 * Gets the configured WhatsApp phone number for current context.
	 *
	 * @since 6.1.0
	 */
	private static function register_get_phone() {
		wp_register_ability(
			self::NAMESPACE . '/get-phone',
			array(
				'label'               => __( 'Get WhatsApp contact Phone', 'creame-whatsapp-me' ),
				'description'         => __( 'Get the configured WhatsApp phone number for current context', 'creame-whatsapp-me' ),
				'category'            => self::CATEGORY,
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
						'term_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional term ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'string',
					'description' => __( 'Phone number', 'creame-whatsapp-me' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_get_phone' ),
				'meta'                => array(
					'annotations' => array(
						'readonly' => true,
					),
				),
			)
		);
	}

	/**
	 * Execute get-phone ability
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters.
	 * @return string Phone number.
	 */
	public static function execute_get_phone( $input = array() ) {
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
		$term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;

		$settings = jc_common()->settings;

		// Check post meta.
		if ( $post_id > 0 ) {
			$meta = get_post_meta( $post_id, '_joinchat', true );
			if ( ! empty( $meta['telephone'] ) ) {
				return Joinchat_Util::clean_whatsapp( $meta['telephone'] );
			}
		}

		// Check term meta.
		if ( $term_id > 0 ) {
			$meta = get_term_meta( $term_id, '_joinchat', true );
			if ( ! empty( $meta['telephone'] ) ) {
				return Joinchat_Util::clean_whatsapp( $meta['telephone'] );
			}
		}

		// Return global setting.
		return Joinchat_Util::clean_whatsapp( $settings['telephone'] ?? '' );
	}

	/**
	 * Register set-phone ability
	 *
	 * Sets the global WhatsApp phone number.
	 *
	 * @since 6.1.0
	 */
	private static function register_set_phone() {
		wp_register_ability(
			self::NAMESPACE . '/set-phone',
			array(
				'label'               => __( 'Set WhatsApp contact Phone', 'creame-whatsapp-me' ),
				'description'         => __( 'Set the WhatsApp phone number globally or for current context', 'creame-whatsapp-me' ),
				'category'            => self::CATEGORY,
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'phone'   => array(
							'type'        => 'string',
							'description' => __( 'WhatsApp phone number', 'creame-whatsapp-me' ),
							'required'    => true,
						),
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
						'term_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional term ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'boolean',
					'description' => __( 'Success status', 'creame-whatsapp-me' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_set_phone' ),
			)
		);
	}

	/**
	 * Execute set-phone ability
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters.
	 * @return bool Success status.
	 */
	public static function execute_set_phone( $input = array() ) {
		$phone   = isset( $input['phone'] ) ? $input['phone'] : '';
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
		$term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;

		$phone = Joinchat_Util::clean_whatsapp( $phone );

		// Handle post context.
		if ( $post_id > 0 ) {
			$meta = get_post_meta( $post_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['telephone'] = $phone;

			return update_post_meta( $post_id, '_joinchat', $meta );
		}

		// Handle term context.
		if ( $term_id > 0 ) {
			$meta = get_term_meta( $term_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['telephone'] = $phone;

			return update_term_meta( $term_id, '_joinchat', $meta );
		}

		// Update global setting.
		$settings              = jc_common()->settings;
		$settings['telephone'] = $phone;

		return update_option( 'joinchat', $settings );
	}

	/**
	 * Register set-cta ability
	 *
	 * Sets the global call-to-action text.
	 *
	 * @since 6.1.0
	 */
	private static function register_set_cta() {
		wp_register_ability(
			self::NAMESPACE . '/set-cta',
			array(
				'label'               => __( 'Set Call to Action', 'creame-whatsapp-me' ),
				'description'         => __( 'Set the call to action text for the Joinchat chat window globally or for current context', 'creame-whatsapp-me' ),
				'category'            => self::CATEGORY,
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'cta'     => array(
							'type'        => 'string',
							'description' => __( 'Call to Action text', 'creame-whatsapp-me' ),
							'required'    => true,
						),
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
						'term_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional term ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'boolean',
					'description' => __( 'Success status', 'creame-whatsapp-me' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_set_cta' ),
			)
		);
	}

	/**
	 * Execute set-cta ability
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters.
	 * @return bool Success status.
	 */
	public static function execute_set_cta( $input = array() ) {
		$cta     = isset( $input['cta'] ) ? sanitize_text_field( $input['cta'] ) : '';
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
		$term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;

		// Handle post context.
		if ( $post_id > 0 ) {
			$meta = get_post_meta( $post_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['message_text'] = $cta;

			return update_post_meta( $post_id, '_joinchat', $meta );
		}

		// Handle term context.
		if ( $term_id > 0 ) {
			$meta = get_term_meta( $term_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['message_text'] = $cta;

			return update_term_meta( $term_id, '_joinchat', $meta );
		}

		// Update global setting.
		$settings                 = jc_common()->settings;
		$settings['message_text'] = $cta;

		return update_option( 'joinchat', $settings );
	}

	/**
	 * Register set-message ability
	 *
	 * Sets the global pre-filled message.
	 *
	 * @since 6.1.0
	 */
	private static function register_set_message() {
		wp_register_ability(
			self::NAMESPACE . '/set-message',
			array(
				'label'               => __( 'Set WhatsApp pre-filled Message', 'creame-whatsapp-me' ),
				'description'         => __( 'Set the pre-filled WhatsApp first message globally or for current context', 'creame-whatsapp-me' ),
				'category'            => self::CATEGORY,
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'message' => array(
							'type'        => 'string',
							'description' => __( 'Pre-filled message text', 'creame-whatsapp-me' ),
							'required'    => true,
						),
						'post_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional post ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
						'term_id' => array(
							'type'        => 'integer',
							'description' => __( 'Optional term ID for context-specific phone', 'creame-whatsapp-me' ),
							'default'     => 0,
						),
					),
				),
				'output_schema'       => array(
					'type'        => 'boolean',
					'description' => __( 'Success status', 'creame-whatsapp-me' ),
				),
				'execute_callback'    => array( __CLASS__, 'execute_set_message' ),
			)
		);
	}

	/**
	 * Execute set-message ability
	 *
	 * @since 6.1.0
	 * @param array $input Input parameters.
	 * @return bool Success status.
	 */
	public static function execute_set_message( $input = array() ) {
		$message = isset( $input['message'] ) ? sanitize_textarea_field( $input['message'] ) : '';
		$post_id = isset( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;
		$term_id = isset( $input['term_id'] ) ? absint( $input['term_id'] ) : 0;

		// Handle post context.
		if ( $post_id > 0 ) {
			$meta = get_post_meta( $post_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['message_send'] = $message;

			return update_post_meta( $post_id, '_joinchat', $meta );
		}

		// Handle term context.
		if ( $term_id > 0 ) {
			$meta = get_term_meta( $term_id, '_joinchat', true );

			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			$meta['message_send'] = $message;

			return update_term_meta( $term_id, '_joinchat', $meta );
		}

		// Update global setting.
		$settings                 = jc_common()->settings;
		$settings['message_send'] = $message;

		return update_option( 'joinchat', $settings );
	}
}
