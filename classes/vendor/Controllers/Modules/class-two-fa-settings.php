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

use WPSEC\Controllers\Settings;
use WPSEC\Helpers\WP_Helper;
use WPSEC\Validators\Validator;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Controllers\Modules\Two_FA_Settings' ) ) {
	/**
	 * Responsible for storing and extracting the user settings for the methods
	 * TOTP and OOB
	 *
	 * @since 1.0.0
	 */
	class Two_FA_Settings extends Base_Module {

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = '2fa_settings_menu';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const TOTP_SETTINGS_NAME = '2fa_totp';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const OOB_SETTINGS_NAME = '2fa_oob';

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
		 * Holds the status of the totp global setting
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $totp_enabled = null;

		/**
		 * Holds the status of the oob global setting
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $oob_enabled = null;

		/**
		 * Returns the status of totp
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - the WP blog id.
		 *
		 * @return mixed
		 */
		public static function is_totp_enabled( $blog_id = '' ) {
			if ( null === self::$totp_enabled ) {
				self::$totp_enabled = Settings::get_current_options()[ self::TOTP_SETTINGS_NAME ];
			}

			return self::$totp_enabled;
		}

		/**
		 * Returns the status of oob
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - the WP blog id.
		 *
		 * @return mixed
		 */
		public static function is_oob_enabled( $blog_id = '' ) {
			if ( null === self::$oob_enabled ) {
				self::$oob_enabled = Settings::get_current_options()[ self::OOB_SETTINGS_NAME ];
			}

			return self::$oob_enabled;
		}

		/**
		 * Sets the new value for oob settings
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $value - the desired value which must be stored for the given blog id.
		 * @param string $blog_id - the WP blog id.
		 *
		 * @return void
		 */
		public static function set_totp( $value, $blog_id = '' ) {
			if ( Validator::filter_validate( $value, 'bool' ) ) {
				WP_Helper::set_option( WPSEC_PLUGIN_SECURED_2FA_TOTP_VAR_NAME, $value, '', $blog_id );
			}
		}

		/**
		 * Deletes the stored oob status
		 *
		 * @since 1.0.0
		 *
		 * @param string $blog_id - the WP blog id.
		 *
		 * @return void
		 */
		public static function delete_oob( $blog_id = '' ) {
			WP_Helper::delete_option( WPSEC_PLUGIN_SECURED_2FA_OOB_VAR_NAME, $blog_id );
		}

		/**
		 * Sets the new value for oob settings
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $value - the desired value which must be stored for the given blog id.
		 * @param string $blog_id - the WP blog id.
		 *
		 * @return void
		 */
		public static function set_oob( $value, $blog_id = '' ) {
			if ( Validator::filter_validate( $value, 'bool' ) ) {
				WP_Helper::set_option( WPSEC_PLUGIN_SECURED_2FA_OOB_VAR_NAME, $value, '', $blog_id );
			}
		}
	}
}
