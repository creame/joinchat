<?php
/**
 * Gutenberg functionality of the plugin.
 *
 * @package    Joinchat
 */

/**
 * Register Gutenberg block editor plugin logic.
 * Add native sidebar for postmeta and register blocks and patterns.
 *
 * @since      1.0.0
 * @package    Joinchat_Gutenberg
 * @subpackage Joinchat/gutenberg
 * @author     Creame <hola@crea.me>
 */
class Joinchat_Gutenberg {

	/**
	 * Register the stylesheets for the gutenberg editor
	 *
	 * @since    4.5.0
	 * @return   void
	 */
	public function enqueue_editor_assets() {

		$asset_file = include JOINCHAT_DIR . '/gutenberg/build/index.asset.php';

		$joinchat_data = array(
			'image_qr'     => plugins_url( 'admin/img/qr.png', JOINCHAT_FILE ),
			'defaults'     => jc_common()->get_obj_placeholders( get_post() ),
			'message_vars' => jc_common()->get_obj_vars( get_post() ),
		);

		wp_enqueue_script( 'joinchat-gutenberg', plugins_url( 'gutenberg/build/index.js', JOINCHAT_FILE ), $asset_file['dependencies'], $asset_file['version'], true );
		wp_localize_script( 'joinchat-gutenberg', 'joinchatData', $joinchat_data );
		wp_set_script_translations( 'joinchat-gutenberg', 'creame-whatsapp-me', JOINCHAT_DIR . 'languages' );

		/**
		 * Disable sidebar?
		 */

		$conditions = array(
			$this->show_sidebar(),                                                   // Is enabled sidebar?
			in_array( get_post_type(), jc_common()->get_public_post_types(), true ), // CPT is plubic (with '_joinchat' meta registered)?
			post_type_supports( get_post_type(), 'custom-fields' ),                  // CPT supports 'custom-fields' for Gutenberg access to postmeta?
		);

		if ( count( array_filter( $conditions ) ) < count( $conditions ) ) {
			wp_add_inline_script( 'joinchat-gutenberg', 'wp.hooks.addFilter( "joinchat_gutenberg_sidebar", "joinchat", () => { return false; } );', 'before' );
		}

	}

	/**
	 * Initiates blocks on PHP side.
	 *
	 * @since    4.5.0
	 * @return void
	 */
	public function register_blocks() {

		register_block_type(
			JOINCHAT_DIR . '/gutenberg/build/block_btn/',
			array(
				'render_callback' => array( $this, 'render_button' ),
			)
		);

	}

	/**
	 * Render the button.
	 *
	 * @since    4.5.0
	 * @param  array  $attributes The block attributes.
	 * @param  string $content    The block html.
	 * @return string The block html.
	 */
	public function render_button( $attributes, $content ) {

		// Don't do nothing for admin and API.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $content;
		}

		// Need render QR code.
		if ( isset( $attributes['qr_code'] ) && 'no' !== $attributes['qr_code'] ) {
			jc_common()->qr = true;
		}

		// Replace dynamic vars.
		if ( ! empty( $attributes['message'] ) ) {
			$escaped = str_replace( array( '&', '"', '>' ), array( '&amp;', '&quot;', '&gt;' ), $attributes['message'] );
			$content = str_replace( $escaped, esc_attr( Joinchat_Util::replace_variables( $attributes['message'] ) ), $content );
		}

		// Render an empty Button Block to ensure enqueue button styles.
		$button = parse_blocks( '<!-- wp:button /-->' );
		render_block( $button[0] );

		return $content;

	}

	/**
	 * Fallback styles
	 *
	 * @return void
	 */
	public function root_styles() {

		if ( has_block( 'joinchat/button' ) && ! wp_script_is( 'joinchat', 'done' ) && ! wp_script_is( 'joinchat-woo', 'done' ) ) {

			ob_start();
			?>
<style>
:root {
  --joinchat-ico: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23fff' d='M3.516 3.516c4.686-4.686 12.284-4.686 16.97 0 4.686 4.686 4.686 12.283 0 16.97a12.004 12.004 0 0 1-13.754 2.299l-5.814.735a.392.392 0 0 1-.438-.44l.748-5.788A12.002 12.002 0 0 1 3.517 3.517zm3.61 17.043.3.158a9.846 9.846 0 0 0 11.534-1.758c3.843-3.843 3.843-10.074 0-13.918-3.843-3.843-10.075-3.843-13.918 0a9.846 9.846 0 0 0-1.747 11.554l.16.303-.51 3.942a.196.196 0 0 0 .219.22l3.961-.501zm6.534-7.003-.933 1.164a9.843 9.843 0 0 1-3.497-3.495l1.166-.933a.792.792 0 0 0 .23-.94L9.561 6.96a.793.793 0 0 0-.924-.445 1291.6 1291.6 0 0 0-2.023.524.797.797 0 0 0-.588.88 11.754 11.754 0 0 0 10.005 10.005.797.797 0 0 0 .88-.587l.525-2.023a.793.793 0 0 0-.445-.923L14.6 13.327a.792.792 0 0 0-.94.23z'/%3E%3C/svg%3E");
  --joinchat-font: -apple-system, blinkmacsystemfont, "Segoe UI", roboto, oxygen-sans, ubuntu, cantarell, "Helvetica Neue", sans-serif;
}
</style>
			<?php
			echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

	}

	/**
	 * Undocumented function
	 *
	 * @since    4.5.0
	 * @return void
	 */
	public function register_patterns() {

		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		// Comming soon.
	}

	/**
	 * Allow Gutenberg sidebar
	 *
	 * @since    4.5.0
	 * @return bool
	 */
	public function show_sidebar() {

		return apply_filters( 'joinchat_gutenberg_sidebar', true );

	}

	/**
	 * Register post meta
	 *
	 * @since    4.5.0
	 * @return void
	 */
	public function register_meta() {

		if ( ! $this->show_sidebar() ) {
			return;
		}

		$post_types = jc_common()->get_public_post_types();

		foreach ( $post_types as $post_type ) {
			register_meta(
				'post',
				'_joinchat',
				array(
					'object_subtype' => $post_type,
					'type'           => 'object',
					'show_in_rest'   => array(
						'schema' => array(
							'type'       => 'object',
							'properties' => array(
								'telephone'    => array( 'type' => 'string' ),
								'message_text' => array( 'type' => 'string' ),
								'message_send' => array( 'type' => 'string' ),
								'view'         => array( 'type' => 'string' ),
							),
						),
					),
					'auth_callback'  => function () {
						return current_user_can( 'edit_posts' );
					},
					'single'         => true,
				)
			);
		}

		// Sanitize meta on save.
		add_filter( 'sanitize_post_meta__joinchat', array( $this, 'sanitize_meta' ) );
		add_action( 'updated_postmeta', array( $this, 'delete_empty_meta' ), 10, 4 );

	}

	/**
	 * Sanitize post meta
	 *
	 * @since    4.5.0
	 * @param  array $meta_value Current meta value.
	 * @return array
	 */
	public function sanitize_meta( $meta_value ) {

		Joinchat_Util::maybe_encode_emoji();

		return array_filter( Joinchat_Util::clean_input( $meta_value ) );

	}

	/**
	 * Delete empty post meta
	 *
	 * @since    4.5.0
	 * @param  int    $meta_id    Current meta ID.
	 * @param  int    $object_id  Current object ID.
	 * @param  string $meta_key   Current meta key.
	 * @param  mixed  $meta_value Current meta value.
	 * @return void
	 */
	public function delete_empty_meta( $meta_id, $object_id, $meta_key, $meta_value ) {

		if ( '_joinchat' === $meta_key && empty( maybe_unserialize( $meta_value ) ) ) {
			delete_metadata_by_mid( 'post', $meta_id );
		}

	}
}
