<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://wp-secured.com
 * @since             1.0.0
 * @package           Secured
 *
 * @wordpress-plugin
 * Plugin Name:       Secured WP
 * Plugin URI:        https://wp-secured.com
 * Description:       Provides Security for WP sites. 2FA, login attempts, hardens WP login process
 * Version:           2.1.3
 * Author:            wp-secured
 * Author URI:        https://wp-secured.com
 * Author email:      wp.secured.com@gmail.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       secured-wp
 * Domain Path:       /languages
 * License:           GPL2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      8.0
 */

use WPSEC\Controllers\Modules\Login;
use WPSEC\Controllers\Modules\Remember_Me;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . '/third-party/vendor/scoper-autoload.php';
require __DIR__ . '/constants.php';

\add_action( 'init', array( 'WPSEC\\Secured', 'init' ) );
\add_action( 'init', array( 'WPSEC\\Controllers\\Login_Check', 'check_oob_and_login' ) );

\add_filter( 'plugin_action_links_' . \plugin_basename( __FILE__ ), array( 'WPSEC\\Secured', 'add_action_links' ) );

if ( (bool) Remember_Me::get_global_settings_value() ) {
	\add_filter( 'determine_current_user', array( 'WPSEC\\Controllers\\Modules\\Remember_Me', 'check_remember_me' ), 99 );
}

// Fires wp-login redirection.
if ( (bool) Login::get_global_settings_value() ) {
	\add_action(
		'plugins_loaded',
		function () {
			Login::init();
		}
	);
}

register_activation_hook( __FILE__, array( 'WPSEC\\Secured', 'plugin_activation' ) );
