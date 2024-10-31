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

use WPSEC\Helpers\WP_Helper;
use WPSEC_Vendor\OTPHP\TOTP;
use WPSEC\Validators\Validator;
use WPSEC\Helpers\Secrets_Generator;
use WPSEC\Controllers\Modules\Remember_Me;
use WPSEC\Controllers\Modules\Login_Attempts;

if ( ! class_exists( 'WPSEC\Controllers\User' ) ) {
	/**
	 * Holds information about the user
	 *
	 * @since 1.0.0
	 */
	class User {

		/**
		 * Holds the current user
		 *
		 * @since 1.0.0
		 *
		 * @var mixed
		 */
		private static $user = null;

		/**
		 * Prefix of the used transient for the user locks
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $transient_prefix = 'attempted_login_';

		/**
		 * Prefix of the used transient for the user logged in devices
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $transient_prefix_logged = '_logged_in_devices';

		/**
		 * Totpkey of the user
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $totp_key = null;

		/**
		 * Holds the name of the metakey for the user totp
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		private static $totp_key_meta_key_name = '_wpsec_totp_key';

		/**
		 * Holds the status name for the TOTP of the user
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $tot_enabled_key = '_wpsec-totp-enabled';

		/**
		 * Holds the status name for the TOTP of the user
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $user_excluded_two_fa_key = '_wpsec-two-fa-excluded';

		/**
		 * Sets the time period for how long the TOTP is valid
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $period = 30;

		/**
		 * How many digits the password must be
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $digits = 6;

		/**
		 * Which algorithm is used for generating the proper TOTP
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $algorithm = 'sha1';

		/**
		 * Holds the current status of the user - logged or not
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $logged_in = null;

		/**
		 * Returns the current user
		 * TODO: re-check that logic - there is maybe room for some improvements
		 *
		 * @param mixed $user - User - that could be int \WP_User or null, if nothing is provided the logic will try to extract user from the WP - currently logged in.
		 *
		 * @since 1.0.0
		 *
		 * @return \WP_User
		 */
		public static function get_user( $user = null ): \WP_User {
			if ( null === self::$user || ! is_null( $user ) ) {
				self::set_user( $user );
			}

			return self::$user;
		}

		/**
		 * Sets the current user.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user user object @see \WP_user or integer representing the ID of the user. If null / not provided, tries to extract the current user.
		 *
		 * @return WPSEC\Controllers\User
		 *
		 * @throws \Exception - throws exception if can not extract user.
		 */
		public static function set_user( $user = null ) {
			self::$user = null;

			if ( empty( $user ) ) {
				self::$user = \wp_get_current_user();
			}

			if ( is_null( self::$user ) && Validator::filter_validate( $user, 'int' ) ) {
				self::$user = \get_user_by( 'id', $user );
			}

			if ( is_null( self::$user ) && $user instanceof \WP_User ) {
				self::$user = $user;
			}

			if ( is_null( self::$user ) ) {
				self::$user = \get_user_by( 'login', $user );
				if ( false === self::$user ) {
					self::$user = \get_user_by( 'email', $user );
				}
			}

			if ( false === self::$user && Validator::filter_validate( $user, 'int' ) ) {
				self::$user = new \WP_User( $user );
			}

			if ( is_null( self::$user ) ) {
				throw new \Exception( 'Can not extract user', 1 );
			}

			return __CLASS__;
		}

		/**
		 * Gets currently stored meta for given user
		 *
		 * @since 1.0.0
		 *
		 * @param string  $meta_key - the meta key which has to be checked for value.
		 * @param mixed   $user - could be User instance, user Id or null - if null currently logged user is used.
		 * @param boolean $single - return single or array.
		 *
		 * @return mixed
		 */
		public static function get_meta( string $meta_key, $user = null, $single = false ) {
			return \get_user_meta( self::get_user( $user )->ID, $meta_key, $single );
		}

		/**
		 * Updates given user meta with given value
		 *
		 * @since 1.0.0
		 *
		 * @param string $meta_key - the meta key which has to be checked for value.
		 * @param mixed  $meta_value - value for the meta which needs to be updated.
		 * @param mixed  $user - could be User instance, user Id or null - if null currently logged user is used.
		 *
		 * @return void
		 */
		public static function update_meta( string $meta_key, $meta_value, $user = null ) {
			\update_user_meta( self::get_user( $user )->ID, $meta_key, $meta_value );
		}

		/**
		 * That method is just an alias for @see updateMeta
		 *
		 * @since 1.0.0
		 *
		 * @param string $meta_key - key which identifies the meta.
		 * @param mixed  $meta_value - value which must be stored.
		 * @param mixed  $user - the user which that meta applies to.
		 * @return void
		 */
		public static function set_meta( string $meta_key, $meta_value, $user = null ) {
			self::update_meta( $meta_key, $meta_value, $user );
		}

		/**
		 * Deletes meta for the user
		 *
		 * @since 1.0.0
		 *
		 * @param string $meta_key - the meta key which has to be checked for value.
		 * @param mixed  $user - could be User instance, user Id or null - if null currently logged user is used.
		 *
		 * @return void
		 */
		public static function delete_meta( string $meta_key, $user = null ) {
			\delete_user_meta( self::get_user( $user )->ID, $meta_key );
		}

		/**
		 * Checks if the given user is locked
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - @see setUser method of this class.
		 *
		 * @return boolean
		 */
		public static function is_locked( $user = null ): bool {
			self::set_user( $user );

			return false !== \get_transient( self::$transient_prefix . self::$user->ID ) ? true : false;
		}

		/**
		 * Gets the locked user transient name
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_locked_transient_prefix(): string {
			return self::$transient_prefix;
		}

		/**
		 * Lock user and destroys its session
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - @see set_user method of this class.
		 * @param bool  $destroy_sessions - should we also destroy all the sessions for the user?.
		 *
		 * @return void
		 */
		public static function lock_user( $user = null, $destroy_sessions = false ): void {
			self::set_user( $user );

			\set_transient(
				self::get_locked_transient_prefix() . self::$user->ID,
				self::$user->user_login,
				60 * Login_Attempts::get_lock_time_mins()
			);

			if ( $destroy_sessions ) {
				$manager = \WP_Session_Tokens::get_instance( self::$user->ID );
				$manager->destroy_all();
			}
		}

		/**
		 * Unlocks the user and clears the login attempts
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - @see setUser method of this class.
		 *
		 * @return void
		 */
		public static function unlock_user( $user = null ): void {
			self::set_user( $user );

			\delete_transient(
				self::get_locked_transient_prefix() . self::$user->ID
			);

			Login_Attempts::clear_login_attempts( self::$user );
		}

		/**
		 * Returns the user secret key, if one is not exist - generates it
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - user which TOTP key is needed.
		 *
		 * @return string
		 */
		public static function get_user_totp( $user = null ) {
			if ( null === self::$totp_key ) {
				if ( '' === self::$totp_key = self::get_meta( self::$totp_key_meta_key_name, $user, true ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure

					self::$totp_key = TOTP::create(
						Secrets_Generator::totp_generate_key(),
						self::$period,
						self::$algorithm,
						self::$digits
					)->getSecret();
					self::update_meta( self::$totp_key_meta_key_name, self::$totp_key, $user );
				}
			}

			return self::$totp_key;
		}

		/**
		 * Deletes user meta
		 *
		 * @param mixed $user - The user for which the TOTP key must be deleted.
		 *
		 * @return void
		 *
		 * @since 1.3
		 */
		public static function delete_user_totp( $user = null ) {
			self::delete_meta( self::$totp_key_meta_key_name, $user );
			self::remove_user_totp_enabled_meta( $user );
		}

		/**
		 * Validates authentication.
		 *
		 * @since 1.0.0
		 *
		 * @param string           $auth_code - Authentication code.
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return bool Whether the user gave a valid code
		 */
		public static function validate_totp_authentication( string $auth_code, $user = null ) {

			$auth_code = str_replace( array( ' ' ), '', $auth_code );

			$totp = TOTP::create(
				self::get_user_totp( $user ),
				self::$period,
				self::$algorithm,
				self::$digits
			);

			return $totp->verify( \sanitize_text_field( $auth_code ) );
		}

		/**
		 * Returns the totp status of the current user
		 *
		 * @since 1.0.0
		 *
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return boolean
		 */
		public static function is_totp_user_enabled( $user = null ): bool {
			return \filter_var( self::get_meta( self::$tot_enabled_key, $user, true ), FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * Sets the TOTP as enabled for the user
		 *
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function enable_totp( $user = null ) {
			self::set_meta( self::$tot_enabled_key, true, $user );
		}

		/**
		 * Remove the TOTP meta key completely
		 *
		 * @param mixed $user - The user for which the meta must be removed.
		 *
		 * @return void
		 *
		 * @since 1.3
		 */
		public static function remove_user_totp_enabled_meta( $user = null ) {
			self::delete_meta( self::$tot_enabled_key, $user );
		}

		/**
		 * Returns the 2FA status of the current user
		 *
		 * @since 1.0.0
		 *
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return boolean
		 */
		public static function is_two_fa_user_excluded( $user = null ): bool {
			return filter_var( self::get_meta( self::$user_excluded_two_fa_key, $user, true ), FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * Sets the 2FA status as excluded for the user
		 *
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function exclude_two_fa( $user = null ) {
			self::set_meta( self::$user_excluded_two_fa_key, true, $user );
		}

		/**
		 * Sets the 2FA status as excluded for the user
		 *
		 * @param null|int|WP_User $user WP_User object of the logged-in user.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function include_two_fa( $user = null ) {
			self::delete_meta( self::$user_excluded_two_fa_key, $user );
		}

		/**
		 * Sets the logged in devices in the user's array
		 *
		 * @since 1.0.0
		 *
		 * @param string $device - the device name - check @Remember_Me class for more information.
		 * @param mixed  $user - The user for which device is se.
		 *
		 * @return void
		 */
		public static function set_logged_in_device( string $device, $user = null ) {
			$devices = self::get_logged_in_devices( $user );

			if ( ! empty( $devices ) && is_array( $devices ) ) {
				if ( ! in_array( $device, $devices, true ) ) {
					$devices[] = $device;
				}
			} else {
				$devices = array( $device );
			}

			self::set_meta( self::$transient_prefix_logged, $devices, $user, true );
		}

		/**
		 * Deletes given device from the remember me array of the user,
		 *
		 * @since 1.0.0
		 *
		 * @param string $device - the device name - check @Remember_Me class for more information.
		 * @param mixed  $user - could the int \WP_User or null - class will try to extract the proper user.
		 *
		 * @return bool
		 */
		public static function delete_logged_in_device( string $device, $user = null ): bool {
			$devices = self::get_logged_in_devices( $user );
			if ( ! empty( $devices ) && is_array( $devices ) ) {
				if ( ! in_array( $device, $devices, true ) ) {
					return false;
				}
				$key = array_search( $device, $devices, true );
				unset( $devices[ $key ] );
				self::set_meta( self::$transient_prefix_logged, $devices, $user, true );

				Remember_Me::remove_transient( (int) self::$user->ID, $device );

				return true;
			}

			return false;
		}

		/**
		 * Removes all the logged in devices for the given user, and removes the transients
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - could the int \WP_User or null - class will try to extract the proper user.
		 *
		 * @return boolean
		 */
		public static function delete_all_logged_in_devices( $user = null ): bool {
			$devices = self::get_logged_in_devices( $user );
			if ( ! empty( $devices ) && is_array( $devices ) ) {
				foreach ( $devices as $device ) {
					Remember_Me::remove_transient( (int) self::$user->ID, $device );
				}
			}

			self::set_meta( self::$transient_prefix_logged, array(), $user, true );

			return true;
		}

		/**
		 * Cleans up the data of the logged in devices for the user based on the expired transients for the every device
		 * TODO: maybe it is better that to be part of the Remember_Me class
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - could the int \WP_User or null - class will try to extract the proper user.
		 *
		 * @return void
		 */
		public static function clean_logged_in_devices( $user = null ) {
			$devices          = self::get_logged_in_devices( $user );
			$original_devices = $devices;
			$remember_prefix  = Remember_Me::get_transient_prefix();

			foreach ( $devices as $device_id => &$logged_device ) {

				$data_timeout = WP_Helper::get_option(
					'_transient_timeout_' . $remember_prefix . md5( $logged_device ) . '_' . self::get_user( $user )->ID
				);

				if ( ! $data_timeout || ! ( $data_timeout > time() ) ) {
					unset( $devices[ $device_id ] );
				}
			}
			unset( $logged_device );

			if ( $devices !== $original_devices ) {
				self::set_meta( self::$transient_prefix_logged, $devices, $user, true );
			}
		}

		/**
		 * Returns the stored logged in devices for the given user
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - could the int \WP_User or null - class will try to extract the proper user.
		 *
		 * @return array - all the stored devices or false if there aren't any
		 */
		public static function get_logged_in_devices( $user = null ): array {
			$devices = self::get_meta( self::$transient_prefix_logged, $user, true );

			if ( ! is_array( $devices ) ) {
				$devices = array();
			}
			return $devices;
		}

		/**
		 * Checks if any given user is logged in
		 *
		 * That method is used when batch jobs is in place - like the user list in the user menu.
		 * That method must not be used if you want to determine the status of the currently logged user,
		 * but just to check the status of the any given user, as it will search the entire user meta table
		 * in order to return correct results.
		 *
		 * If you want to get the status of the current user - @see isCurrentlyLogged method of this class
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - could the int \WP_User or null - class will try to extract the proper user.
		 *
		 * @return boolean
		 */
		public static function is_logged( $user = null ): bool {
			self::set_user( $user );

			$logged_users = Users::get_logged_users();

			$ids = \wp_list_pluck( $logged_users, 'ID' );

			return in_array( self::$user->ID, $ids, true );
		}

		/**
		 * Returns HTML statistic for the current user
		 *
		 * @param mixed $user - the user.
		 *
		 * @return string
		 *
		 * @since 1.7
		 */
		public static function get_status( $user = null ): string {
			$status = array(
				'2FAEnabled'  => __( '2FA enabled: YES', 'secured-wp' ),
				'TotpEnabled' => __( 'TOTP enabled: NO', 'secured-wp' ),
			);

			if ( self::is_two_fa_user_excluded( $user ) ) {
				$status['2FAEnabled'] = __( '2FA enabled: EXCLUDED', 'secured-wp' );
			}

			if ( self::is_totp_user_enabled( $user ) ) {
				$status['TotpEnabled'] = __( 'TOTP enabled: YES', 'secured-wp' );
			}

			return implode( '<br/>', $status );
		}

		/**
		 * Returns the status of the current user - is it logged or not
		 *
		 * Unlike @see isLogged method of this class - it is checking only the current user
		 *
		 * If the user is logged in, this method also set the $user property of this class for caching purposes
		 *
		 * @return boolean
		 *
		 * @since 1.0.0
		 */
		public static function is_currently_logged() {
			if ( null === self::$logged_in ) {
				self::$logged_in = false;
				if ( \is_user_logged_in() ) {
					self::$logged_in = true;
					self::set_user();
				}
			}

			return self::$logged_in;
		}
	}
}
