<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Controllers\Modules;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use WPSEC\Controllers\User;
use WPSEC\Controllers\Settings;

if ( ! class_exists( '\WPSEC\Controllers\Modules\Login_Attempts' ) ) {
	/**
	 * Responsible for the login attempts count, store and delete
	 *
	 * @since 1.0.0
	 */
	class Login_Attempts extends Base_Module {

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = 'login_attempts_menu';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const LOGIN_ATTEMPTS_SETTINGS_NAME = 'login_attempts';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const LOGIN_LOCK_SETTINGS_NAME = 'login_lock';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 */
		protected static $global_setting_name = null;

		/**
		 * Is the module enabled or not
		 *
		 * @var bool
		 *
		 * @since 1.0.0
		 */
		protected static $module_enabled;

		/**
		 * Holds the name of the give module
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected static $module_name;

		/**
		 * User meta key name
		 *
		 * @since 1.0.0
		 */
		const META_KEY = '_wps_login_attempts';

		/**
		 * How many times the user is allowed to try to login before sending email to admin
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $allowed_attempts = null;

		/**
		 * In how many minutes the user is allowed to try to login again
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $allowed_mins = null;

		/**
		 * Inits the class and sets the hooks
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function settings_init() {

			if ( \current_user_can( 'manage_options' ) ) {
				\add_filter( 'wpsec_store_settings', array( __CLASS__, 'store_settings' ), 2, 2 );
			}
		}

		/**
		 * Stores the module settings.
		 *
		 * @param array $settings - Array with the currently collected settings.
		 * @param array $post_array - The array with settings to check against.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function store_settings( array $settings, array $post_array ): array {

			$settings[ self::GLOBAL_SETTINGS_NAME ] = ( array_key_exists( self::GLOBAL_SETTINGS_NAME, $post_array ) ) ? true : false;
			if ( $settings[ self::GLOBAL_SETTINGS_NAME ] && array_key_exists( 'login_attempts', $post_array ) ) {
				$settings['login_attempts'] = filter_var(
					$post_array['login_attempts'],
					FILTER_VALIDATE_INT,
					array(
						'options' => array(
							'min_range' => 1,
							'max_range' => 15,
						),
					)
				);
				if ( false === $settings['login_attempts'] ) {
					unset( $settings['login_attempts'] );
				}
			}
			if ( $settings[ self::GLOBAL_SETTINGS_NAME ] && array_key_exists( self::LOGIN_LOCK_SETTINGS_NAME, $post_array ) ) {
				$settings[ self::LOGIN_LOCK_SETTINGS_NAME ] = filter_var(
					$post_array[ self::LOGIN_LOCK_SETTINGS_NAME ],
					FILTER_VALIDATE_INT,
					array(
						'options' => array(
							'min_range' => 1,
							'max_range' => 180,
						),
					)
				);
				if ( false === $settings[ self::LOGIN_LOCK_SETTINGS_NAME ] ) {
					unset( $settings[ self::LOGIN_LOCK_SETTINGS_NAME ] );
				}
			}

			return $settings;
		}

		/**
		 * Increasing login attempts for User
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_User $user - the WP User.
		 *
		 * @return void
		 */
		public static function increase_login_attempts( \WP_User $user ): void {
			$attempts = User::get_meta( self::META_KEY, $user, true );
			if ( '' === $attempts ) {
				$attempts = 0;
			}
			User::update_meta( self::META_KEY, ++$attempts, $user );
		}

		/**
		 * Returns the number of unsuccessful attempts for the User
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_User $user - the WP User.
		 *
		 * @return integer
		 */
		public static function get_login_attempts( \WP_User $user ): int {
			return (int) User::get_meta( self::META_KEY, $user, true );
		}

		/**
		 * Clearing login attempts for User
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_User $user - the WP User.
		 *
		 * @return void
		 */
		public static function clear_login_attempts( \WP_User $user ): void {
			User::delete_meta( self::META_KEY, $user );
		}

		/**
		 * Returns the number of the allowed attempts
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - the WP blog id.
		 *
		 * @return mixed
		 */
		public static function get_allowed_attempts( $blog_id = '' ) {
			if ( null === self::$allowed_attempts ) {
				self::$allowed_attempts = Settings::get_current_options()[ self::LOGIN_ATTEMPTS_SETTINGS_NAME ];
			}

			return self::$allowed_attempts;
		}

		/**
		 * Returns the number of lock minutes
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - the WP blog id.
		 *
		 * @return int
		 */
		public static function get_lock_time_mins( $blog_id = '' ): int {
			if ( null === self::$allowed_mins ) {
				self::$allowed_mins = Settings::get_current_options()[ self::LOGIN_LOCK_SETTINGS_NAME ];
			}

			return (int) self::$allowed_mins;
		}
	}
}
