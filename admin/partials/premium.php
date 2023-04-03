<?php
/**
 * Joinchat premium addons
 *
 * @since      5.0.0
 * @package    Joinchat
 * @subpackage Joinchat/admin
 * @author     Creame <hola@crea.me>
 */

defined( 'WPINC' ) || exit;
?>

<table class="wp-list-table widefat plugins">
	<thead>
		<tr>
			<th scope="col" id="name" class="manage-column column-name column-primary"><?php _e( 'Add-on', 'creame-whatsapp-me' ); ?></th>
			<th scope="col" id="description" class="manage-column column-description"><?php _e( 'Description' ); ?></th>
		</tr>
	</thead>

	<tbody id="the-list" data-wp-lists="list:addon">
	<?php foreach ( $addons as $key => $addon ) : ?>
		<tr class="inactive">
			<td class="plugin-title column-primary"><strong><?php echo esc_html( $addon['name'] ); ?></strong>
				<div class="row-actions visible"><span class="activate"><a href="#" id="activate-joinchat-agentes-de-soporte" class="edit"><?php _e( 'Activate' ); ?></a></span></div>
			</td>
			<td class="column-description desc">
				<div class="plugin-description"><?php echo esc_html( $addon['description'] ); ?></div>
				<div class="inactive second plugin-version-author-uri">
					<?php printf( __( 'Version: %s' ), $addon['ver'] ); ?> |
					<a href="<?php echo esc_url( $addon[ "info_$lang" ] . $utm ); ?>"><?php _e( 'View details' ); ?></a> |
					<a href="<?php echo esc_url( $addon[ "docs_$lang" ] . $utm ); ?>"><?php _e( 'Documentation' ); ?></a>
				</div>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>

	<tfoot>
		<tr>
			<th scope="col" class="manage-column column-name column-primary"><?php _e( 'Add-on', 'creame-whatsapp-me' ); ?></th>
			<th scope="col" class="manage-column column-description"><?php _e( 'Description' ); ?></th>
		</tr>
	</tfoot>

</table>
