<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Controllers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Controllers\Users' ) ) {
	/**
	 * Holds information about the users
	 *
	 * @since 1.0.0
	 */
	class Users {

		/**
		 * Holds the current user
		 *
		 * @since 1.0.0
		 *
		 * @var mixed
		 */
		private static $logged_users = null;

		/**
		 * Returns the currently logged users
		 *
		 * @since 1.0.0
		 *
		 * @return array \WP_User
		 */
		public static function get_logged_users() {
			if ( null === self::$logged_users ) {
				self::$logged_users = get_users(
					array(
						'meta_key'     => 'session_tokens',
						'meta_compare' => 'EXISTS',
					)
				);
			}

			return self::$logged_users;
		}

		/**
		 * Wipes off all the user meta
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function delete_all_user_data() {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->usermeta
					WHERE 1
					AND meta_key LIKE %s",
					array(
						'_wpsec%',
					)
				)
			);
		}
	}
}
