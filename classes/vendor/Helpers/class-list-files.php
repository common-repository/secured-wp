<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Helpers;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\List_Files' ) ) {
	/**
	 * WP function responsible for that has bugs
	 *
	 * @since 1.0.0
	 */
	class List_Files {

		/**
		 * Original WP function has bugs and does not support filtering by extension
		 *
		 * @since 1.0.0
		 *
		 * @param type|string $folder - folder to parse.
		 * @param type|int    $levels - how many levels to parse.
		 * @param type|array  $exclusions - option to exclude some.
		 * @param type|array  $include_ext - exclude by extension.
		 *
		 * @return array
		 */
		public static function get_files( $folder = '', $levels = 100, $exclusions = array(), $include_ext = array() ) {
			if ( empty( $folder ) ) {
				return false;
			}

			$folder = \trailingslashit( $folder );

			if ( ! $levels ) {
				return false;
			}

			$files = array();

			$dir = @opendir( $folder );
			if ( $dir ) {
				while ( false !== ( $file = readdir( $dir ) ) ) {
					// Skip current and parent folder links.
					if ( in_array( $file, array( '.', '..' ), true ) ) {
						continue;
					}

					// Skip hidden and excluded files.
					if ( '.' === $file[0] || in_array( $file, $exclusions, true ) ) {
						continue;
					}

					if ( ! is_dir( $folder . $file ) ) {
						$ext = pathinfo( $file, PATHINFO_EXTENSION );
						if ( ! in_array( strtolower( $ext ), $include_ext ) ) {
							continue;
						}
					}

					if ( is_dir( $folder . $file ) ) {
						$files2 = self::get_files( $folder . $file, $levels - 1, $exclusions, $include_ext );
						if ( $files2 ) {
							$files = array_merge( $files, $files2 );
						} elseif ( empty( $include_ext ) ) {
								$files[] = $folder . $file . '/';
						}
					} else {
						$files[] = $folder . $file;
					}
				}

				closedir( $dir );
			}

			return $files;
		}
	}
}
