<?php
/**
 * Joinchat admin post meta box
 *
 * @since      4.3.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<div class="joinchat-metabox">
	<?php wp_nonce_field( 'joinchat_data', 'joinchat_nonce' ); ?>
	<p>
		<label for="joinchat_phone"><?php esc_html_e( 'Telephone', 'creame-whatsapp-me' ); ?></label><br>
		<input id="joinchat_phone" <?php echo jc_common()->get_intltel() ? 'data-' : ''; ?>name="joinchat_telephone" value="<?php echo esc_attr( $metadata['telephone'] ); ?>" type="text" placeholder="<?php echo esc_attr( $placeholders['telephone'] ); ?>">
	</p>
	<p>
		<label for="joinchat_message"><?php esc_html_e( 'Call to Action', 'creame-whatsapp-me' ); ?></label><br>
		<textarea id="joinchat_message" name="joinchat_message" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_text'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_text'] ); ?></textarea>
	</p>
	<p>
		<label for="joinchat_message_send"><?php esc_html_e( 'Message', 'creame-whatsapp-me' ); ?></label><br>
		<textarea id="joinchat_message_send" name="joinchat_message_send" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_send'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_send'] ); ?></textarea>
		<?php if ( count( $metabox_vars ) ) : ?>
			<small><?php esc_html_e( 'Can use vars', 'creame-whatsapp-me' ); ?> <code>{<?php echo wp_kses( join( '}</code> <code>{', $metabox_vars ), array( 'code' => array() ) ); ?>}</code></small>
		<?php endif; ?>
		<small><?php esc_html_e( 'to leave it blank use', 'creame-whatsapp-me' ); ?> <code>{}</code></small>
	</p>
	<p>
		<label><input type="radio" name="joinchat_view" value="yes" <?php checked( 'yes', $metadata['view'] ); ?>>
			<span class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Show', 'creame-whatsapp-me' ); ?>"></span></label>
		<label><input type="radio" name="joinchat_view" value="no" <?php checked( 'no', $metadata['view'] ); ?>>
			<span class="dashicons dashicons-hidden" title="<?php esc_attr_e( 'Hide', 'creame-whatsapp-me' ); ?>"></span></label>
		<label><input type="radio" name="joinchat_view" value="" <?php checked( '', $metadata['view'] ); ?>>
			<?php esc_html_e( 'Default visibility', 'creame-whatsapp-me' ); ?></label>
	</p>
</div>
