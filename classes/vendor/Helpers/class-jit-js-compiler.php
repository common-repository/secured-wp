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

use JShrink\Minifier;
use WPSEC\Helpers\List_Files;

if ( ! class_exists( 'WPSEC\Helpers\JIT_JS_Compiler' ) ) {
	/**
	 * Just In Time JS generator
	 */
	class JIT_JS_Compiler {

		/**
		 * Holds the compiled JS file name
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public static $js_file_name = '';

		/**
		 * Holds all the collected js files for compression and path
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		private static $js_files = array();

		/**
		 * Holds the checksum for all the source SASS files
		 *
		 * @see JIT_JS_Compiler::getCheckSum for the used logic
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $check_sum = '';

		/**
		 * Holds the compiled JS string
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $compiled_js_string = '';

		/**
		 * Collects the uncompressed JS checksum
		 * Checks JS existence
		 * Compiles new JS if necessary
		 *
		 * @since 1.0.0
		 *
		 * @return string full path to the compiled JS
		 */
		public static function get_js_file(): string {
			self::get_check_sum();

			if ( ! self::check_file_exists() ) {
				foreach ( self::$js_files as $file ) {
					self::$compiled_js_string .= Minifier::minify( \file_get_contents( $file ) );
				}

				\file_put_contents( self::$js_file_name, self::$compiled_js_string );
			}

			return self::$js_file_name;
		}

		/**
		 * Checks if the JS file exists based on the collected checksum
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		private static function check_file_exists(): bool {
			$uploads    = wp_upload_dir( null, false );
			$assets_dir = $uploads['basedir'] . WPSEC_PLUGIN_SECURED_ASSETS_DIR;

			if ( ! is_dir( $assets_dir ) ) {
				\mkdir( $assets_dir, 0755, true );
			}

			self::$js_file_name = $assets_dir . \DIRECTORY_SEPARATOR . self::$check_sum . '.js';

			return \file_exists( self::$js_file_name );
		}

		/**
		 * Generates checksum for the JS files
		 * Current logic:
		 * - get file size
		 * - get file modification date
		 * - sets string for all the collected files
		 * - generates MD5 string
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function get_check_sum() {
			self::$js_files = List_Files::get_files( plugin_dir_path( WPSEC_PLUGIN_SECURED_FILENAME ) . 'assets/js-full/', 100, array(), array( 'js' ) );

			$files_string = '';

			if ( ! empty( self::$js_files ) ) {
				foreach ( self::$js_files as $file ) {
					$files_string .= \filesize( $file );
					$files_string .= \filemtime( $file );
				}
			}

			self::$check_sum = md5( $files_string );
		}
	}
}
