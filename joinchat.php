<?php

/**
 * @link              https://crea.me
 * @since             1.0.0
 * @package           JoinChat
 *
 * @wordpress-plugin
 * Plugin Name:       Joinchat
 * Plugin URI:        https://join.chat
 * Description:       Connects a WordPress chat with WhatsApp. The best solution for marketing and support. Stop losing customers and increase your sales.
 * Version:           4.5.20
 * Author:            Creame
 * Author URI:        https://crea.me
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       creame-whatsapp-me
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define constants.
 */
define( 'JOINCHAT_VERSION', '4.5.20' );
define( 'JOINCHAT_FILE', __FILE__ );
define( 'JOINCHAT_DIR', plugin_dir_path( JOINCHAT_FILE ) );
define( 'JOINCHAT_BASENAME', plugin_basename( JOINCHAT_FILE ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require JOINCHAT_DIR . 'includes/class-joinchat.php';

/**
 * Begins execution of the plugin.
 *
 * Everything within the plugin is registered via hooks,
 * but initiation is delayed to 'init' hook to allow extensions
 * or third party plugins to change Joinchat behavior.
 *
 * @since    1.0.0
 * @since    3.0.0     Replaced direct run() to launch via 'init' hook
 */
function run_joinchat() {

	$plugin = new JoinChat();

	add_action( 'init', array( $plugin, 'run' ) );

}

run_joinchat();
