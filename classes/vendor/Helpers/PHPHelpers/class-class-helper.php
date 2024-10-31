<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Helpers\PHPHelpers;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\PHPHelpers\Class_Helper' ) ) {

	/**
	 * General helper for php classes related logic
	 *
	 * Works only with PSR4
	 *
	 * @since 1.0.0
	 */
	class Class_Helper {

		/**
		 * Searches recursively through the given path for the all declared PHP classes
		 *
		 * @param string $namespace - Classes namespace.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function find_recursive( string $namespace ): array {
			$namespace_path = self::translate_namespace_path( $namespace );

			if ( '' === $namespace_path ) {
				return array();
			}

			return self::search_classes( $namespace, $namespace_path );
		}

		/**
		 * Converts the directory to namespaces
		 *
		 * @param string $namespace - Classes namespace.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		protected static function translate_namespace_path( string $namespace ): string {
			$root_path = WPSEC_PLUGIN_SECURED_PATH . 'classes' . DIRECTORY_SEPARATOR;

			$ns_parts = explode( '\\', $namespace );
			array_shift( $ns_parts );

			if ( empty( $ns_parts ) ) {
				return '';
			}

			return realpath( $root_path . implode( DIRECTORY_SEPARATOR, $ns_parts ) ) ?? '';
		}

		/**
		 * Returns the classes filtered by given namespace
		 *
		 * @param string $namespace - Classes namespace.
		 * @param string $namespace_path - Classes namespace path.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		private static function search_classes( string $namespace, string $namespace_path ): array {
			$classes = array();

			/**
			 * Walks through dirs and searches for classes of given namespace
			 *
			 * @var \RecursiveDirectoryIterator $iterator
			 * @var \SplFileInfo $item
			 */
			foreach ( $iterator = new RecursiveIteratorIterator( //phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
				new RecursiveDirectoryIterator( $namespace_path, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			) as $item ) {
				if ( $item->isDir() ) {
					$next_path      = $iterator->current()->getPathname();
					$next_namespace = $namespace . '\\' . $item->getFilename();
					$classes        = array_merge( $classes, self::search_classes( $next_namespace, $next_path ) );
					continue;
				}
				if ( $item->isFile() && $item->getExtension() === 'php' ) {
					$class = $namespace . '\\' . $item->getBasename( '.php' );
					if ( ! class_exists( $class ) ) {
						continue;
					}
					$classes[] = $class;
				}
			}

			return $classes;
		}
	}
}
