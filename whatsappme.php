<?php

/**
 * @link              https://crea.me
 * @since             1.0.0
 * @package           WhatsAppMe
 *
 * @wordpress-plugin
 * Plugin Name:       WhatsApp me
 * Plugin URI:        https://wame.chat
 * Description:       Connect a WordPress chat with WhatsApp. The best solution for marketing and support. Stop losing customers and increase your sales.
 * Version:           2.3.3
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
define( 'WHATSAPPME_VERSION', '2.3.3' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-whatsappme.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_whatsappme() {

	$plugin = new WhatsAppMe();
	$plugin->run();

}
run_whatsappme();
