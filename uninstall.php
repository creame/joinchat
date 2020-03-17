<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://crea.me
 * @since      1.0.0
 *
 * @package    WhatsAppMe
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * By default don't delete plugin data.
 *
 * Use "add_filter( 'whatsappme_delete_all', '__return_true' );"
 * before uninstall WAme to completely clear all plugin data.
 */
if ( apply_filters( 'whatsappme_delete_all', false ) ) {
	global $wpdb;

	// Delete general option 'whatsappme' added by plugin
	delete_option( 'whatsappme' );
	// Delete post meta '_whatsappme' added by plugin
	$wpdb->delete( $wpdb->prefix . 'postmeta', array( 'meta_key' => '_whatsappme' ) );

}
