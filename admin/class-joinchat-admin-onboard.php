<?php
/**
 * Onboard page of the plugin.
 *
 * @package    Joinchat
 */

/**
 * Onboard page of the plugin.
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Admin_Onboard {

	/**
	 * Add onboard submenu in the WordPress admin
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function add_menu() {

		$title = esc_html__( 'Welcome to Joinchat', 'creame-whatsapp-me' );

		if ( Joinchat_Util::options_submenu() ) {
			add_options_page( $title, $title, Joinchat_Util::capability(), 'joinchat-onboard', array( $this, 'options_page' ) );
		} else {
			add_submenu_page( JOINCHAT_SLUG, $title, $title, Joinchat_Util::capability(), 'joinchat-onboard', array( $this, 'options_page' ) );
		}

	}

	/**
	 * Remove onboard submenu in the WordPress admin
	 *
	 * We need register the page but don't want it on wp-admin menu.
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function remove_menu() {

		global $submenu;

		if ( Joinchat_Util::options_submenu() ) {
			remove_submenu_page( 'options-general.php', 'joinchat-onboard' );
		} else {
			remove_submenu_page( JOINCHAT_SLUG, 'joinchat-onboard' );

			if ( isset( $submenu[ JOINCHAT_SLUG ] ) && 1 === count( $submenu[ JOINCHAT_SLUG ] ) ) {
				remove_submenu_page( JOINCHAT_SLUG, JOINCHAT_SLUG );
			}
		}

	}

	/**
	 * Add settings page hooks
	 *
	 * @since    5.0.0
	 * @return void
	 */
	public function page_hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'in_admin_header', array( $this, 'admin_header' ) );

		add_filter( 'admin_title', array( $this, 'admin_title' ) );
		add_filter( 'submenu_file', array( $this, 'submenu_file' ) );

	}


	/**
	 * Generate the options page in the WordPress admin
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function options_page() {

		?>
			<div class="wrap">
				<div class="wp-header-end"></div>

				<div>
					<?php esc_html_e( 'Exit the wizard and', 'creame-whatsapp-me' ); ?>
					<a href="<?php echo esc_url( add_query_arg( 'onboard', 'no', Joinchat_Util::admin_url() ) ); ?>"><?php esc_html_e( 'go to Joinchat Settings', 'creame-whatsapp-me' ); ?></a>
				</div>

				<div id="joinchat_onboard">
					<div class="joinchat__dialog"></div>
				</div>
				<svg style="width:0;height:0;position:absolute"><defs><clipPath id="joinchat__peak_l"><path d="M17 25V0C17 12.877 6.082 14.9 1.031 15.91c-1.559.31-1.179 2.272.004 2.272C9.609 18.182 17 18.088 17 25z"/></clipPath><clipPath id="joinchat__peak_r"><path d="M0 25.68V0c0 13.23 10.92 15.3 15.97 16.34 1.56.32 1.18 2.34 0 2.34-8.58 0-15.97-.1-15.97 7Z"/></clipPath></defs></svg>

			</div>
		<?php
	}

	/**
	 * Update admin title
	 *
	 * @since    5.0.0
	 * @param    string $admin_title  current admin title.
	 * @return   string
	 */
	public static function admin_title( $admin_title ) {

		return sprintf( '%s &lsaquo; %s', esc_html__( 'Welcome to Joinchat', 'creame-whatsapp-me' ), esc_html( get_bloginfo( 'name' ) ) );

	}

	/**
	 * Set Joinchat submenu selected on onboard
	 *
	 * @since    5.0.0
	 * @param    string $submenu_file Submenu item.
	 * @return   string
	 */
	public function submenu_file( $submenu_file ) {

		return JOINCHAT_SLUG;

	}

	/**
	 * Custom admin header with Joinchat logo
	 *
	 * @since    5.0.0
	 * @return   void
	 */
	public function admin_header() {
		?>
		<div id="jcadminbar">
			<div class="joinchat-header">
				<h1><img src="<?php echo esc_url( plugin_dir_url( JOINCHAT_FILE ) . '/admin/img/joinchat.svg' ); ?>" width="159" height="40" alt="Joinchat"></h1>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue styles & scripts for onboard page
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function enqueue_assets() {

		$handle = 'joinchat-onboard';
		$min    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$deps   = array( 'jquery' );

		// Enqueue styles.
		wp_enqueue_style( $handle, plugins_url( "css/joinchat-onboard{$min}.css", __FILE__ ), array(), JOINCHAT_VERSION, 'all' );

		// Enqueue IntlTelInput assets.
		if ( jc_common()->get_intltel() ) {
			$deps[] = 'intl-tel-input';
			wp_enqueue_style( 'intl-tel-input' );
		}

		$user = wp_get_current_user();

		$config = array(
			'settings_url' => add_query_arg( 'onboard', 'no', Joinchat_Util::admin_url() ),
			'img_base'     => plugins_url( 'img/', __FILE__ ),
			'user_email'   => $user->user_email,
			'nonce'        => wp_create_nonce( 'joinchat_onboard' ),
		);

		$l10n = array(
			'step_hi'         => sprintf(
				wp_kses( /* translators: %s: User display name. */
					_x( "Hey, <em>%s</em>. Let's set up <strong>Joinchat</strong> in less than 2 minutes.", 'onboard', 'creame-whatsapp-me' ),
					array(
						'em'     => array(),
						'strong' => array(),
					)
				),
				esc_html( $user->display_name )
			),
			'step_hi_next'    => _x( "👌 OK, let's start.", 'onboard', 'creame-whatsapp-me' ),
			'step_phone'      => _x( 'Please tell me your WhatsApp number', 'onboard', 'creame-whatsapp-me' ),
			'step_phone_next' => _x( "Done, let's continue", 'onboard', 'creame-whatsapp-me' ),
			'step_msg'        => _x( 'Add the text for the first message that users will send you via WhatsApp.', 'onboard', 'creame-whatsapp-me' ),
			'step_msg_field'  => __( 'Message', 'creame-whatsapp-me' ),
			'step_msg_value'  => esc_textarea( __( 'Hi *{SITE}*! I need more info about {TITLE} {URL}', 'creame-whatsapp-me' ) ),
			'step_msg_yes'    => _x( 'Continue with this text', 'onboard', 'creame-whatsapp-me' ),
			'step_msg_no'     => _x( "I don't want a message", 'onboard', 'creame-whatsapp-me' ),
			'step_cta'        => _x( 'Define a Call to Action message to prompt users to interact.', 'onboard', 'creame-whatsapp-me' ),
			'step_cta_field'  => __( 'Call to Action', 'creame-whatsapp-me' ),
			'step_cta_value'  => esc_textarea( __( "Hello 👋\nCan we help you?", 'creame-whatsapp-me' ) ),
			'step_cta_yes'    => _x( 'Continue with this text', 'onboard', 'creame-whatsapp-me' ),
			'step_cta_no'     => _x( "I don't want a CTA", 'onboard', 'creame-whatsapp-me' ),
			'step_news'       => _x( 'Finally, do you want us to send you tips to improve conversion with <strong>Joinchat</strong>?', 'onboard', 'creame-whatsapp-me' ),
			'step_news_terms' => sprintf( /* translators: %s: Terms of Use link. */
				wp_kses( _x( 'I accept the <a href="%s" target="_blank">terms of use and privacy policy</a>', 'onboard', 'creame-whatsapp-me' ), Joinchat_Admin::KSES_LINK ),
				esc_url( Joinchat_Util::link( 'terms', 'onboard' ) )
			),
			'step_news_yes'   => _x( 'OK, keep me posted', 'onboard', 'creame-whatsapp-me' ),
			'step_news_no'    => _x( 'No, thanks', 'onboard', 'creame-whatsapp-me' ),
			'step_inbox'      => _x( '👍 Perfect, we have just sent you an email to your account, visit your mailbox to confirm your subscription.', 'onboard', 'creame-whatsapp-me' ),
			'step_inbox_next' => _x( 'Done!', 'onboard', 'creame-whatsapp-me' ),
			'step_success'    => _x( '🥳 Great, <strong>Joinchat</strong> is up and running.', 'onboard', 'creame-whatsapp-me' ),
			'step_fail'       => _x( '😖 Sorry, something went wrong.', 'onboard', 'creame-whatsapp-me' ),
			'step_settings'   => ucfirst( __( 'go to Joinchat Settings', 'creame-whatsapp-me' ) ),
		);

		// Enqueue scripts.
		wp_enqueue_script( $handle, plugins_url( "js/joinchat-onboard{$min}.js", __FILE__ ), $deps, JOINCHAT_VERSION, true );
		wp_add_inline_script( $handle, 'var joinchat_settings = ' . wp_json_encode( $config ) . ';', 'before' );
		wp_localize_script( $handle, 'joinchat_l10n', $l10n );

	}

	/**
	 * Save onboard settings
	 *
	 * @since    5.0.0
	 * @access   public
	 * @return   void
	 */
	public function save() {

		check_ajax_referer( 'joinchat_onboard', 'nonce', true );

		$data = $_POST['data'];

		// Save settings.
		$settings = array_merge( jc_common()->settings, $data );
		$updated  = update_option( JOINCHAT_SLUG, $settings, true );

		// Newsletter subscription.
		$newsletter = true;

		if ( ! empty( $data['newsletter'] ) ) {
			$body = array(
				'email' => $data['newsletter'],
				'site'  => get_site_url(),
			);

			$response = wp_remote_post(
				'https://eu5-api.connectif.cloud:443/integration-type/system/scrippet-notification/03362af2-f194-457a-a5c7-5b7d94f29cb6?eventId=64903bd547fb425e8608f3b3',
				array(
					'headers' => array( 'Content-Type' => 'application/json' ),
					'body'    => wp_json_encode( $body ),
					'timeout' => 15,
				)
			);

			$newsletter = ! is_wp_error( $response );
		}

		if ( ! $updated || ! $newsletter ) {
			wp_send_json_error();
		} else {
			wp_send_json_success();
		}

	}
}
