<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

use WPSEC\Secured;
use WPSEC\Helpers\WP_Helper;
use WPSEC\Controllers\Users;
use WPSEC\Helpers\Information\Module_Information;

/**
 * Fired when the plugin is uninstalled.
 */
require __DIR__ . '/third-party/vendor/scoper-autoload.php';
require __DIR__ . '/constants.php';

/**
 * Removes the plugin option
 * Removes meta from all the users
 *
 * @return void
 */
function uninstall_wps_secured() {

	if ( Secured::is_delete_data_enabled() ) {
		Users::delete_all_user_data();

		if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
			$sites   = WP_Helper::get_sites();
			$modules = Module_Information::get_module_classes();

			foreach ( $modules as $key => $module ) {
				if ( false !== strpos( $module, 'Base' ) ) {
					unset( $modules[ $key ] );
				}
			}

			foreach ( $sites as $site ) {
				\switch_to_blog( $site->blog_id );

				foreach ( $modules as $module ) {
					$module::deleteModuleData();
				}

				\restore_current_blog();
			}
		} else {
			$modules = Module_Information::get_module_classes();

			foreach ( $modules as $key => $module ) {
				if ( false !== strpos( $module, 'Base' ) ) {
					unset( $modules[ $key ] );
				}
			}

			foreach ( $modules as $module ) {
				$module::deleteModuleData();
			}
		}
		Secured::delete_data();
	}
}

uninstall_wps_secured();
