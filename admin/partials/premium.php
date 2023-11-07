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
			<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php esc_html_e( 'Select All' ); ?></label><input id="cb-select-all-1" type="checkbox" disabled></td>
			<th scope="col" id="name" class="manage-column column-name column-primary"><?php esc_html_e( 'Add-on', 'creame-whatsapp-me' ); ?></th>
			<th scope="col" id="description" class="manage-column column-description"><?php esc_html_e( 'Description' ); ?></th>
		</tr>
	</thead>

	<tbody id="the-list" data-wp-lists="list:addon">
	<?php foreach ( $addons as $addon ) : ?>
		<tr class="inactive">
			<th scope="row" class="check-column"><input type="checkbox" disabled></th>
			<td class="plugin-title column-primary"><strong><?php echo esc_html( $addon['name'] ); ?></strong>
				<div class="row-actions visible"></div>
			</td>
			<td class="column-description desc">
				<div class="plugin-description"><?php echo wp_kses( $addon['description'], array( 'strong' => array() ) ); ?></div>
				<div class="inactive second plugin-version-author-uri">
					<a href="<?php echo esc_url( $addon['info'] ); ?>" target="_blank"><?php esc_html_e( 'View details' ); ?></a> |
					<a href="<?php echo esc_url( $addon['docs'] ); ?>" target="_blank"><?php esc_html_e( 'Documentation' ); ?></a>
				</div>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>

	<tfoot>
		<tr>
			<td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php esc_html_e( 'Select All' ); ?></label><input id="cb-select-all-2" type="checkbox" disabled></td>
			<th scope="col" class="manage-column column-name column-primary"><?php esc_html_e( 'Add-on', 'creame-whatsapp-me' ); ?></th>
			<th scope="col" class="manage-column column-description"><?php esc_html_e( 'Description' ); ?></th>
		</tr>
	</tfoot>

</table>
