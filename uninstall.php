<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://crea.me
 * @since      1.0.0
 *
 * @package    JoinChat
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * By default delete all plugin data.
 *
 * Use "add_filter( 'joinchat_delete_all', '__return_false' );"
 * before uninstall Joinchat to prevent clear all plugin data.
 */
if ( apply_filters( 'joinchat_delete_all', true ) ) {
	global $wpdb;

	// Delete general option 'joinchat' added by plugin
	delete_option( 'joinchat' );
	delete_option( 'joinchat_notice_dismiss' );
	// Delete post meta '_joinchat' added by plugin
	$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_joinchat' ) );

	// TODO: delete WPML/Polylang translations

	// Clear any cached data that has been removed
	wp_cache_flush();
}
