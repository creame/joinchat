<?php
/**
 * Joinchat admin user edit form fields
 *
 * @since      6.4.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;

$metadata     = isset( $metadata ) && is_array( $metadata ) ? $metadata : array();
$placeholders = isset( $placeholders ) && is_array( $placeholders ) ? $placeholders : array();
$metabox_vars = isset( $metabox_vars ) && is_array( $metabox_vars ) ? $metabox_vars : array();
?>

<h2><?php esc_html_e( 'Joinchat', 'creame-whatsapp-me' ); ?></h2>
<p><?php esc_html_e( 'Contact settings for this user archive page.', 'creame-whatsapp-me' ); ?></p>
<table class="form-table" role="presentation">
	<tbody>
		<tr class="joinchat-metabox">
			<th scope="row"><label for="joinchat_phone"><?php esc_html_e( 'Telephone', 'creame-whatsapp-me' ); ?></label></th>
			<td>
				<?php wp_nonce_field( 'joinchat_data', 'joinchat_nonce' ); ?>
				<input id="joinchat_phone" <?php echo jc_common()->get_iti_version() ? 'data-' : ''; ?>name="joinchat_telephone" value="<?php echo esc_attr( $metadata['telephone'] ); ?>" type="text" placeholder="<?php echo esc_attr( $placeholders['telephone'] ); ?>">
			</td>
		</tr>
		<tr class="joinchat-metabox">
			<th scope="row"><label for="joinchat_message"><?php esc_html_e( 'Call to Action', 'creame-whatsapp-me' ); ?></label></th>
			<td><textarea id="joinchat_message" name="joinchat_message" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_text'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_text'] ); ?></textarea></td>
		</tr>
		<tr class="joinchat-metabox">
			<th scope="row"><label for="joinchat_message_send"><?php esc_html_e( 'Message', 'creame-whatsapp-me' ); ?></label></th>
			<td>
				<textarea id="joinchat_message_send" name="joinchat_message_send" rows="2" placeholder="<?php echo esc_attr( $placeholders['message_send'] ); ?>" class="large-text"><?php echo esc_textarea( $metadata['message_send'] ); ?></textarea>
				<p class="description">
					<?php if ( count( $metabox_vars ) ) : ?>
						<?php esc_html_e( 'Can use vars', 'creame-whatsapp-me' ); ?> <code>{<?php echo wp_kses( join( '}</code> <code>{', $metabox_vars ), array( 'code' => array() ) ); ?>}</code>
					<?php endif; ?>
					<?php esc_html_e( 'to leave it blank use', 'creame-whatsapp-me' ); ?> <code>{}</code>
				</p>
			</td>
		</tr>
		<tr class="joinchat-metabox">
			<th scope="row"><label for="joinchat_view"><?php esc_html_e( 'Visibility', 'creame-whatsapp-me' ); ?></label></th>
			<td>
				<label><input id="joinchat_view" type="radio" name="joinchat_view" value="yes" <?php checked( 'yes', $metadata['view'] ); ?>>
					<span class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Show', 'creame-whatsapp-me' ); ?>"></span></label>
				<label><input type="radio" name="joinchat_view" value="no" <?php checked( 'no', $metadata['view'] ); ?>>
					<span class="dashicons dashicons-hidden" title="<?php esc_attr_e( 'Hide', 'creame-whatsapp-me' ); ?>"></span></label>
				<label><input type="radio" name="joinchat_view" value="" <?php checked( '', $metadata['view'] ); ?>>
					<?php esc_html_e( 'Default visibility', 'creame-whatsapp-me' ); ?></label>
			</td>
		</tr>
	</tbody>
</table>
