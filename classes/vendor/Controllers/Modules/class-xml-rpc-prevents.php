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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Controllers\Modules\XML_RPC_Prevents' ) ) {
	/**
	 * Responsible for preventing the XML-RPC for the given WP installation
	 *
	 * @since 1.0.0
	 */
	class XML_RPC_Prevents extends Base_Module {

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = 'xml_rpc_settings_menu';

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
		 * Removes all the XML-RPC methods
		 *
		 * @param array $methods - array with all the XML-RPC methods.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function remove_xml_rpc_methods( $methods ): array {
			$methods = array(); // empty the array.
			return $methods;
		}

		/**
		 * Removes all the XML-RPC link
		 *
		 * @param array $headers - array with headers.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function remove_xml_rpc_link( $headers ): array {
			unset( $headers['X-Pingback'] );

			return $headers;
		}

		/**
		 * Strips out ping-back link from html head
		 *
		 * @param [type] $output - Output collected for sending.
		 * @param [type] $property - Currently parsed property.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function remove_header_ping_link( $output, $property ) {
			return ( 'pingback_url' === $property ) ? null : $output;
		}
	}
}
