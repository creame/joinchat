<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since      1.0.0
 * @package    WhatsAppMe
 * @subpackage WhatsAppMe/admin
 * @author     Creame <hola@crea.me>
 */
class WhatsAppMe_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The setings of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $settings    The current settings of this plugin.
	 */
	private $settings;


	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->get_settings();

	}

	/**
	 * Get all settings or set defaults
	 *
	 * @since    1.0.0
	 */
	private function get_settings() {

		$this->settings = array(
			'telephone'     => '',
			'mobile_only'   => 'no',
			'message_text'  => '',
			'message_delay' => 10000,
		);

		$saved_settings = get_option( 'whatsappme' );

		if ( is_array( $saved_settings ) ) {
			// clean unused saved settings
			$saved_settings = array_intersect_key( $saved_settings, $this->settings );
			// merge defaults with saved settings
			$this->settings = array_merge( $this->settings, $saved_settings );
		}

	}

	/**
	 * Initialize the settings for wordpress admin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   void
	 */
	public function settings_init(){

		register_setting( 'whatsappme', 'whatsappme', array( $this, 'settings_validate' ) );
		add_settings_section( 'whatsappme_section', null, array( $this, 'section_text' ), 'whatsappme' );

		$field_names = 	array(
			'telephone'      => __( 'Telephone', 'creame-whatsapp-me' ),
			'mobile_only'    => __( 'Mobile only', 'creame-whatsapp-me' ),
			'message_text'   => __( 'Call to action', 'creame-whatsapp-me' ),
			'message_delay'  => __( 'Delay', 'creame-whatsapp-me' ),
		);

		foreach ( $this->settings as $key => $value ) {
			add_settings_field( 'whatsappme_' . $key, $field_names[ $key ], array( $this, 'field_' . $key ), 'whatsappme', 'whatsappme_section' );
		}
	}

	/**
	 * Validate settings, clean and set defaults before save
	 *
	 * @since    1.0.0
	 * @return   array
	 */
	public function settings_validate($input) {

		if ( !array_key_exists( 'mobile_only', $input) ){
			$input['mobile_only'] = 'no';
		}
		$input['telephone']     = sanitize_text_field($input['telephone']);
		$input['message_text']  = trim($input['message_text']);
		$input['message_delay'] = intval($input['message_delay']);

		add_settings_error( 'whatsappme', 'settings_updated', __( 'Settings saved', 'creame-whatsapp-me' ), 'updated' );

		return $input;
	}

	/**
	 * Section 'whatsappme_section' output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function section_text() {
		echo '<p>' . __( 'From here you can configure the behavior of the WhatsApp button on your site.', 'creame-whatsapp-me' ) . '</p>';
	}

	/**
	 * Field 'telephone' output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function field_telephone() {
		echo '<input name="whatsappme[telephone]" value="' . $this->settings['telephone'] . '" class="regular-text" type="text">' .
			'<p class="description">' . __( "Contact phone number. <strong>The button will not be shown if it's empty.</strong>", 'creame-whatsapp-me' ) . '</p>';
	}

	/**
	 * Field 'message_text' output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function field_message_text() {
		echo '<textarea name="whatsappme[message_text]" rows="3" class="regular-text">' . $this->settings['message_text'] . '</textarea>' .
			'<p class="description">' . __( 'Optional text to invite the user to use the contact via WhatsApp. <strong>Leave empty to disable.</strong>', 'creame-whatsapp-me' ) . '</p>';
	}

	/**
	 * Field 'message_delay' output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function field_message_delay() {
		echo '<input name="whatsappme[message_delay]" value="' . $this->settings['message_delay'] . '" class="small-text" type="number" min="0"> ' . __( 'milliseconds', 'creame-whatsapp-me' ) .
			'<p class="description"> ' . __( 'The <strong>Call to action</strong> will only be displayed once when the user exceeds the estimated delay on a page. It will also be displayed when the user stops the cursor over the WhatsApp button.', 'creame-whatsapp-me' ) . '</p>';
	}

	/**
	 * Field 'mobile_only' output
	 *
	 * @since    1.0.0
	 * @return   void
	 */
	public function field_mobile_only() {
		echo '<fieldset><legend class="screen-reader-text"><span>' . __( 'Mobile only', 'creame-whatsapp-me' ) . '</span></legend>' .
			'<label><input name="whatsappme[mobile_only]" value="yes" type="checkbox"' . checked( 'yes', $this->settings['mobile_only'], false ) . '> ' .
			__('Only display the button on mobile devices', 'creame-whatsapp-me' ) . '</label></fieldset>';
	}

	/**
	 * Add menu to the options page in the wordpress admin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   void
	 */
	public function add_menu() {

		add_options_page('WhatsApp me', 'WhatsApp me', 'manage_options', 'whatsappme', array( $this, 'options_page' ));

	}

	/**
	 * Add link to options page on plugins page
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   void
	 */
	public function settings_link( $links ) {

		$settings_link = '<a href="options-general.php?page=' . $this->plugin_name . '">' . __( 'Settings', 'creame-whatsapp-me' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;

	}

	/**
	 * Generate the options page in the wordpress admin
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   void
	 */
	function options_page() {
		?>
			<div class="wrap">
				<h1>WhatsApp me</h1>

				<form method="post" id="whatsappme_form" action="options.php">
					<?php
					settings_fields('whatsappme');
					do_settings_sections('whatsappme');
					submit_button();
					?>
				</form>
			</div>
		<?php
	}

	/**
	 * Add Meta Box for all the public post types
	 *
	 * @since    1.1.0
	 * @access   public
	 * @return   void
	 */
	public function add_meta_boxes() {
		// Default post types
		$builtin_post_types = array( 'post', 'page' );
		// Custom post types with public url
		$custom_post_types = array_keys( get_post_types( array( 'rewrite' => true ), 'names' ) );

		// Add/remove posts types for "WhatsApp me" meta box
		$post_types = apply_filters( 'whatsappme_post_types_meta_box', array_merge( $builtin_post_types, $custom_post_types ) );

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'whatsappme',
				__( 'WhatsApp me', 'creame-whatsapp-me' ),
				array( $this, 'add_meta_box' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Generate Meta Box html
	 *
	 * @since    1.1.0
	 * @access   public
	 * @return   void
	 */
	public function add_meta_box( $post ) {

		$metadata = get_post_meta( $post->ID, '_whatsappme', true ) ?: array();
		$metadata = array_merge( array(
			'message_text' => '',
			'hide' => false
		), $metadata );

		$post_type = get_post_type_object( get_post_type( $post->ID ) );
		$post_type_name = mb_strtolower( $post_type->labels->singular_name );

		wp_nonce_field( 'whatsappme_data', 'whatsappme_nonce' );
		?>
			<p>
				<label for="whatsappme_message"><?php _e( 'Call to action', 'creame-whatsapp-me' ); ?></label><br>
				<textarea name="whatsappme_message" rows="3" class="large-text"><?php echo $metadata['message_text']; ?></textarea>
			</p>
			<p>
				<input type="checkbox" name="whatsappme_hide" id="whatsappme_hide" value="1" <?php echo $metadata['hide'] ? 'checked' : ''; ?>>
				<label for="whatsappme_hide"><?php printf( __( 'Hide on this %s', 'creame-whatsapp-me' ), $post_type_name ); ?></label>
			</p>
		<?php
	}

	/**
	 * Save meta data from "WhatsApp me" Meta Box on post save
	 *
	 * @since    1.1.0
	 * @access   public
	 * @return   void
	 */
	public function save_post( $post_id ) {
		if ( wp_is_post_autosave( $post_id ) ||
			 ! isset( $_POST['whatsappme_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['whatsappme_nonce'], 'whatsappme_data' ) ) {
			return;
		}

		// Delete empty/false fields
		$metadata = array_filter( array(
			'message_text' => trim( $_POST['whatsappme_message'] ),
			'hide' => isset( $_POST['whatsappme_hide'] ) ? 1 : 0,
		) );

		if ( count( $metadata ) ) {
			update_post_meta( $post_id, '_whatsappme', $metadata );
		} else {
			delete_post_meta( $post_id, '_whatsappme' );
		}
	}
}
