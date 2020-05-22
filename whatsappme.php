<?php

/**
 * WhatsApp me to Join.chat migration helper
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Deactivate old 'creame-whatsapp-me/whatsappme.php'
deactivate_plugins( plugin_basename( __FILE__ ) );

// Activate new 'creame-whatsapp-me/joinchat.php'
activate_plugins( plugin_dir_path( __FILE__ ) . 'joinchat.php' );
