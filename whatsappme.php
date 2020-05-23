<?php

/**
 * WhatsApp me to Join.chat migration helper
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function deactivate_wame_activate_joinchat() {

	// Deactivate old 'creame-whatsapp-me/whatsappme.php'
	deactivate_plugins( plugin_basename( __FILE__ ) );

	// Activate new 'creame-whatsapp-me/joinchat.php'
	activate_plugins( plugin_dir_path( __FILE__ ) . 'joinchat.php' );
}

add_action( 'plugins_loaded', 'deactivate_wame_activate_joinchat', 1 );
