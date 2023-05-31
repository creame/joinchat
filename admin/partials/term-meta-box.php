<?php
/**
 * Joinchat admin term edit from fields
 *
 * @since      4.3.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<tr class="form-field">
	<th><h2 style="margin:0"><?php esc_html_e( 'Joinchat', 'creame-whatsapp-me' ); ?></h2></th>
	<td><?php wp_nonce_field( 'joinchat_data', 'joinchat_nonce' ); ?></td>
</tr>
<tr class="form-field joinchat-metabox">
	<th scope="row"><label for="joinchat_phone"><?php esc_html_e( 'Telephone', 'creame-whatsapp-me' ); ?></label></th>
	<td><input id="joinchat_phone" <?php echo jc_common()->get_intltel() ? 'data-' : ''; ?>name="joinchat_telephone" value="<?php echo esc_attr( $metadata['telephone'] ); ?>" type="text" placeholder="<?php echo esc_attr( $placeholders['telephone'] ); ?>"></td>
</tr>
<tr class="form-field joinchat-metabox">
	<th scope="row"><label for="joinchat_message"><?php esc_html_e( 'Call to Action', 'creame-whatsapp-me' ); ?></label></th>
	<td><textarea id="joinchat_message" name="joinchat_message" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_text'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_text'] ); ?></textarea></td>
</tr>
<tr class="form-field joinchat-metabox">
	<th scope="row"><label for="joinchat_message_send"><?php esc_html_e( 'Message', 'creame-whatsapp-me' ); ?></label></th>
	<td>
		<textarea id="joinchat_message_send" name="joinchat_message_send" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_send'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_send'] ); ?></textarea>
		<p class="description">
			<?php if ( count( $metabox_vars ) ) : ?>
				<?php esc_html_e( 'Can use vars', 'creame-whatsapp-me' ); ?> <code>{<?php echo join( '}</code> <code>{', $metabox_vars ); ?>}</code>
			<?php endif; ?>
			<?php esc_html_e( 'to leave it blank use', 'creame-whatsapp-me' ); ?> <code>{}</code>
		</p>
	</td>
</tr>
<tr class="form-field joinchat-metabox">
	<th scope="row"><label for="joinchat_message"><?php esc_html_e( 'Visibility', 'creame-whatsapp-me' ); ?></label></th>
	<td>
		<label><input type="radio" name="joinchat_view" value="yes" <?php checked( 'yes', $metadata['view'] ); ?>>
			<span class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Show', 'creame-whatsapp-me' ); ?>"></span></label>
		<label><input type="radio" name="joinchat_view" value="no" <?php checked( 'no', $metadata['view'] ); ?>>
			<span class="dashicons dashicons-hidden" title="<?php esc_attr_e( 'Hide', 'creame-whatsapp-me' ); ?>"></span></label>
		<label><input type="radio" name="joinchat_view" value="" <?php checked( '', $metadata['view'] ); ?>>
			<?php esc_html_e( 'Default visibility', 'creame-whatsapp-me' ); ?></label>
	</td>
</tr>
