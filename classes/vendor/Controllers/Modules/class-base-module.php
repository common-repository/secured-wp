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

use WPSEC\Helpers\WP_Helper;
use WPSEC\Controllers\Settings;

if ( ! class_exists( 'WPSEC\Controllers\Modules\Base_Module' ) ) {
	/**
	 * Base module class
	 *
	 * @since 1.0.0
	 */
	abstract class Base_Module {

		/**
		 * Every inherited module sets that for its own
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = '';

		/**
		 * Global settings name - declared in the plugin main file
		 * That must be always set (overridden) in the extending class
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected static $global_setting_name;

		/**
		 * Is that module (this is abstract class) enabled?
		 *
		 * @since 1.0.0
		 *
		 * @var null|bool
		 */
		protected static $module_enabled;

		/**
		 * Holds the name of the give module
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		protected static $module_name;

		/**
		 * Saves global option for given module
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value - the value from the given option.
		 * @param string $blog_id - blog id for the option.
		 *
		 * @return void
		 */
		public static function save_global_enabled( $value, $blog_id = '' ) {
			WP_Helper::set_option( static::$global_setting_name, $value, '', $blog_id );
			static::$module_enabled = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		}

		/**
		 * Returns the name of the global setting for the given module
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_global_settings_name(): string {
			return static::$global_setting_name;
		}

		/**
		 * Returns global settings module status - enabled / disabled
		 *
		 * @since 1.0.0
		 *
		 * @param string $blog_id - blog id for the option.
		 *
		 * @return boolean
		 */
		public static function get_global_settings_value( $blog_id = '' ): bool {
			if ( null === static::$module_enabled ) {
				static::$module_enabled = Settings::get_current_options()[ static::GLOBAL_SETTINGS_NAME ];
			}

			return static::$module_enabled;
		}
	}
}
