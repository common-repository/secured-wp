<?php
/**
 * Secured WP constants
 *
 * Registered constants of the class
 *
 * @package WPSecure
 * @subpackage Constants
 */

/** Prevent default call */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPSEC_REQUIRED_PHP_VERSION', '8.0' );
define( 'WPSEC_REQUIRED_WP_VERSION', '6.0' );
define( 'WPSEC_PLUGIN_SECURED_VERSION', '2.1.3' );
define( 'WPSEC_PLUGIN_SECURED_NAME', 'Secured WP' );
define( 'WPSEC_PLUGIN_SECURED_SLUG', 'secured-wp' );
define( 'WPSEC_PLUGIN_SECURED_JS_VAR', 'securedWp' );
define( 'WPSEC_PLUGIN_SECURED_FILENAME', __FILE__ );
define( 'WPSEC_PLUGIN_SECURED_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPSEC_PLUGIN_SECURED_CSS_PREFIX', 'snd' );
define( 'WPSEC_PLUGIN_SECURED_ASSETS_DIR', '/WPSEC-secured' );
define( 'WPSEC_PLUGIN_SECURED_URL', plugin_dir_url( __FILE__ ) );
define( 'WPSEC_PLUGIN_SECURED_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSEC_PLUGIN_SECURED_SETTINGS_NAME', '_wpsec_plugin_options' );

define( 'WPSEC_PLUGIN_SECURED_DELETE_DATA_VAR_NAME', '_wpsec_delete_data' );
define( 'WPSEC_PLUGIN_SECURED_DELETE_DATA_VAR_DEFAULT', false );
