<?php

/**
 * @link              https://crea.me
 * @since             1.0.0
 * @package           WhatsAppMe
 *
 * @wordpress-plugin
 * Plugin Name:       WAme chat
 * Plugin URI:        https://wame.chat
 * Description:       Connect a WordPress chat with WhatsApp. The best solution for marketing and support. Stop losing customers and increase your sales.
 * Version:           3.1.2
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
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'WHATSAPPME_VERSION', '3.1.2' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-whatsappme.php';

/**
 * Begins execution of the plugin.
 *
 * Everything within the plugin is registered via hooks,
 * but initiation is delayed to 'init' hook to allow extensions
 * or third party plugins to change WAme behavior.
 *
 * @since    1.0.0
 * @since    3.0.0     Replaced direct run() to launch via 'init' hook
 */
function run_whatsappme() {

	$plugin = new WhatsAppMe();

	add_action( 'init', array( $plugin, 'run' ) );

}
run_whatsappme();
