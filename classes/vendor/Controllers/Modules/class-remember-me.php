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

use WPSEC\Helpers\WP_Helper;
use WPSEC_Vendor\OTPHP\TOTP;
use WPSEC\Controllers\Settings;
use WPSEC_Vendor\Mobile_Detect;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Controllers\Modules\Remember_Me' ) ) {

	/**
	 * Sets remember me cookie and sets the authentication in the system
	 *
	 * @since 1.0.0
	 */
	class Remember_Me extends Base_Module {

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = 'remember_devices_settings_menu';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const TIME_REMEMBER_NAME = 'time_remember';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 */
		protected static $global_setting_name = null;

		/**
		 * Keeps the name of the transient for the remember me, and also the name used for the cookie name
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $transient_prefix = '_remember_me_';

		/**
		 * Sets the time period for how long the TOTP is valid
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $period = null;

		/**
		 * How many digits the password must be
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $digits = 12;

		/**
		 * Which algorithm is used for generating the proper TOTP
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $algorithm = 'sha1';

		/**
		 * In how many days the user is allowed to use remember me
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $allowed_days = null;

		/**
		 * Holds the name of the give module
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected static $module_name;

		/**
		 * Sets the remember me cookie and sets the remember me transient
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user_id - WP user ID.
		 *
		 * @return void
		 */
		public static function set_remember_me( $user_id ) {
			$secret = self::get_secret();
			self::set_cookie( $user_id, $secret['pass'] );
			self::set_transient( $user_id, $secret['secret'] );
		}

		/**
		 * Removes the cookie and transient if they are present
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user_id - WP user ID.
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function clear_remember_me( $user_id ) {
			if ( isset( $_COOKIE[ self::$transient_prefix ] ) ) {
				self::remove_transient( (int) $user_id );
				self::delete_remember_me_cookie();
			}
		}

		/**
		 * Checks the cookie value against the stored value in the transient
		 * Device is also checked
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user_id - WP user ID.
		 *
		 * @return bool|int - false if all the checks fail, user id otherwise
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 * @SuppressWarnings(PHPMD.ExitExpression)
		 */
		public static function check_remember_me( $user_id ) {
			if ( $user_id ) {
				return $user_id;
			}

			$remember_me = isset( $_COOKIE[ self::$transient_prefix ] ) ? \sanitize_text_field( \wp_unslash( $_COOKIE[ self::$transient_prefix ] ) ) : false;

			try {
				if ( $remember_me ) {

					$vals = \json_decode( \stripslashes_deep( $remember_me ), true, JSON_THROW_ON_ERROR );

					$device = self::get_device();

					/**
					 * Collect data from the transient
					 */
					$remember_transient = \get_transient( self::$transient_prefix . md5( $device ) . '_' . $vals['uid'] );

					if ( $remember_transient ) {
						/**
						 * Builds the TOTP class
						 */
						$otp = TOTP::create(
							$remember_transient['secret'],
							self::get_period(),
							self::$algorithm,
							self::$digits
						);

						/**
						 * Check password against the TOTP object
						 */
						if ( $vals['pass'] === $otp->at( time() ) ) {
							\wp_set_current_user( $vals['uid'] );
							\wp_set_auth_cookie( $vals['uid'] );

							\wp_safe_redirect( ( ( isset( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] ) ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" );

							exit();
						}
					} else {
						return false;
					}
				}
			} catch ( \Exception $exc ) {
				return false;
			}

			return false;
		}

		/**
		 * Returns the name of the transient prefix
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_transient_prefix(): string {
			return self::$transient_prefix;
		}

		/**
		 * Returns the device of the current user based on the browser headers
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_device(): string {
			$device_detect = new Mobile_Detect();
			$device        = $device_detect->getUserAgent();
			if ( null === $device ) {
				$device = '';
			}

			return $device;
		}

		/**
		 * Returns the expiry time of the remember me transient, based on the given device
		 *
		 * @since 1.0.0
		 *
		 * @param string  $device - device (string).
		 * @param integer $user_id - the WP user.
		 *
		 * @return integer
		 */
		public static function get_expire_time( string $device, int $user_id ): int {
			$expires = (int) WP_Helper::get_option( '_transient_timeout_' . self::$transient_prefix . md5( $device ) . '_' . $user_id, 0 );

			return $expires;
		}

		/**
		 * Removes remember me transient
		 *
		 * @since 1.0.0
		 *
		 * @param int   $user_id - the WP user.
		 * @param mixed $device - device (string).
		 *
		 * @return void
		 */
		public static function remove_transient( int $user_id, $device = null ) {
			if ( null === $device ) {
				$device = self::get_device();
			}

			\delete_transient(
				self::$transient_prefix . md5( $device ) . '_' . (string) $user_id
			);
		}

		/**
		 * Returns the number of the allowed attempts
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - the WP blog id.
		 *
		 * @return int
		 */
		public static function get_lock_time_days( $blog_id = '' ): int {
			if ( null === self::$allowed_days ) {

				self::$allowed_days = Settings::get_current_options()[ self::TIME_REMEMBER_NAME ];
			}

			return (int) self::$allowed_days;
		}

		/**
		 * Returns the current period
		 * This is based on current settings - that means that this will return different values based on global settings changes
		 * That is related to the expiration time as well
		 *
		 * TODO: Make sure that this acts properly when there are already created transients
		 *
		 * @return int
		 *
		 * @since 1.0.0
		 */
		public static function get_period(): int {
			if ( null === self::$period ) {
				self::$period = DAY_IN_SECONDS * self::get_lock_time_days();
			}

			return (int) self::$period;
		}

		/**
		 * Resets the remember me cookie
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function delete_remember_me_cookie() {
			$domain = ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) ? COOKIE_DOMAIN : '/';
			setcookie(
				self::$transient_prefix,
				'',
				array(
					'expires'  => time() + self::get_period(),
					'path'     => $domain,
					'domain'   => '',
					'samesite' => 'Strict',
					'secure'   => false,
					'httponly' => true,
				)
			);
		}

		/**
		 * Sets the remember me cookie
		 *
		 * @since 1.0.0
		 *
		 * @param int    $user_id - the WP user.
		 * @param string $pass - pass for the given user.
		 *
		 * @return void
		 */
		private static function set_cookie( $user_id, $pass ) {
			$domain = ( defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ) ? COOKIE_DOMAIN : '/';
			$device = self::get_device();
			setcookie(
				self::$transient_prefix,
				\json_encode(
					array(
						'uid'    => $user_id,
						'pass'   => $pass,
						'device' => $device,
					)
				),
				array(
					'expires'  => time() + self::get_period(),
					'path'     => $domain,
					'domain'   => '',
					'samesite' => 'Strict',
					'secure'   => false,
					'httponly' => true,
				)
			);
		}

		/**
		 * Sets transient in the WordPress to check against
		 *
		 * @since 1.0.0
		 *
		 * @param [type] $user_id - the WP user.
		 * @param [type] $secret - secret TOTP.
		 *
		 * @return void
		 */
		private static function set_transient( $user_id, $secret ) {
			$device = self::get_device();

			\set_transient(
				self::$transient_prefix . md5( $device ) . '_' . $user_id,
				array(
					'uid'    => $user_id,
					'secret' => $secret,
					'device' => $device,
				),
				self::get_period()
			);
		}

		/**
		 * Generates TOTP secrets
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		private static function get_secret(): array {

			$otp = TOTP::create(
				null, // Let the secret be defined by the class.
				self::get_period(), // The period.
				self::$algorithm, // The digest algorithm.
				self::$digits // The output will generate 6 digits.
			);

			return array(
				'pass'   => $otp->at( time() ),
				'secret' => $otp->getSecret(),
			);
		}
	}
}
