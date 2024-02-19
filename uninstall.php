<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://crea.me
 * @since      1.0.0
 * @package    Joinchat
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$option = get_option( 'joinchat' );

/**
 * Delete all plugin data if 'clear' is true.
 */
if ( isset( $option['clear'] ) && 'yes' === $option['clear'] ) {

	global $wpdb;

	// Delete general option 'joinchat' added by plugin.
	delete_option( 'joinchat' );
	delete_option( 'joinchat_notice_dismiss' );

	// Delete post meta '_joinchat' added by plugin.
	$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_joinchat' ) );

	// Delete term meta '_joinchat' added by plugin.
	$wpdb->delete( $wpdb->prefix . 'termmeta', array( 'meta_key' => '_joinchat' ) );

	// TODO: delete WPML/Polylang translations.

	// Clear any cached data that has been removed.
	wp_cache_flush();

}
