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

// Delete option added by plugin
delete_option( 'whatsappme' );
