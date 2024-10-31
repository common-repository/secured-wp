<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);
namespace WPSEC\Helpers\Information;

use WPSEC\Helpers\PHPHelpers\Class_Helper;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\Information\Module_Information' ) ) {
	/**
	 * Extracts information about every module
	 * Logic is simple - it collects information for all module classes (the namespace is used for that - against all the classes defined)
	 * Everything in the Controllers\Modules is considered a module (base is removed)
	 *
	 * Once collected the above info - every module is asked for additional info which we want to show to the user
	 *
	 * @since 1.0.0
	 */
	class Module_Information {

		/**
		 * Collects the info from all the modules and shows it
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function get_info() {
			$classes = self::get_module_classes();

			foreach ( $classes as $class ) {
				if ( false !== strpos( $class, 'Base' ) ) {
					continue;
				}

				$module_info[] = $class::getInfo();
			}

			foreach ( $module_info as $module ) {
				echo '<h3>' . \esc_html( $module['name'] ) . '</h3>';
				echo '<div><strong>' . \esc_html__( 'Disabled: ', 'secured-wp' ) . '</strong>' . ( ( $module['disabled'] ) ? \esc_html__( 'true', 'secured-wp' ) : \esc_html__( 'false', 'secured-wp' ) ) . '</div>';

				if ( isset( $module['additional_info'] ) && ! empty( $module['additional_info'] ) ) {
					foreach ( $module['additional_info'] as $info ) {
						echo '<div><strong>' . \esc_html( $info['translate'] ) . ': </strong>' . \esc_html( $info['value'] ) . '</div>';
					}
				}
			}
		}

		/**
		 * Collects all the currently available modules
		 *
		 * Probably that code must be part of the helper, if there is a need of additional PHP classes manipulations lets move all the methods into separate class
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_module_classes(): array {
			$namespace = 'WPSEC\Controllers\Modules';

			$my_classes = Class_Helper::find_recursive( $namespace );

			// $myClasses = array_filter(
			// get_declared_classes(),
			// function( $item ) use ( $namespace ) {
			// return substr( $item, 0, strlen( $namespace ) ) === $namespace;
			// }
			// );

			return $my_classes;
		}
	}
}
