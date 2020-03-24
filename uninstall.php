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
 * By default don't delete plugin data.
 *
 * Use "add_filter( 'joinchat_delete_all', '__return_true' );"
 * before uninstall Join.chat to completely clear all plugin data.
 */
if ( apply_filters( 'joinchat_delete_all', false ) ) {
	global $wpdb;

	// Delete general option 'joinchat' added by plugin
	delete_option( 'joinchat' );
	// Delete post meta '_joinchat' added by plugin
	$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_joinchat' ) );

}
