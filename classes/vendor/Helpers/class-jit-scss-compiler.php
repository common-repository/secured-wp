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

use WPSEC\Helpers\List_Files;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;

if ( ! class_exists( 'WPSEC\Helpers\JIT_SCSS_Compiler' ) ) {
	/**
	 * Just In Time SASS generator
	 */
	class JIT_SCSS_Compiler {

		/**
		 * Holds the compiled CSS file name
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public static $css_file_name = '';

		/**
		 * Holds all the collected scss files and path
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		private static $scss_files = array();

		/**
		 * Holds the checksum for all the source SASS files
		 *
		 * @see JIT_SCSS_Compiler::getCheckSum for the used logic
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $check_sum = '';

		/**
		 * Holds the compiled SCSS string
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $compiled_css_string = '';

		/**
		 * Collects the SASS checksum
		 * Checks CSS existence
		 * Compiles new CSS if necessary
		 *
		 * @since 1.0.0
		 *
		 * @return string full path to the compiled CSS
		 */
		public static function get_css_file(): string {
			self::get_check_sum();

			if ( ! self::check_file_exists() ) {
				$scss = new Compiler();
				$scss->setOutputStyle( OutputStyle::COMPRESSED );
				foreach ( self::$scss_files as $file ) {
					self::$compiled_css_string .= $scss->compile( \file_get_contents( $file ) );
				}

				\file_put_contents( self::$css_file_name, self::$compiled_css_string );
			}

			return self::$css_file_name;
		}

		/**
		 * Checks if the CSS file exists based on the collected checksum
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		private static function check_file_exists(): bool {
			$uploads    = wp_upload_dir( null, false );
			$assets_dir = $uploads['basedir'] . WPSEC_PLUGIN_SECURED_ASSETS_DIR;

			if ( ! is_dir( $assets_dir ) ) {
				mkdir( $assets_dir, 0755, true );
			}

			self::$css_file_name = $assets_dir . \DIRECTORY_SEPARATOR . self::$check_sum . '.css';
			return \file_exists( self::$css_file_name );
		}

		/**
		 * Generates checksum for the SASS files
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
			self::$scss_files = List_Files::get_files( plugin_dir_path( WPSEC_PLUGIN_SECURED_FILENAME ) . 'assets/sass/', 100, array(), array( 'scss' ) );

			$files_string = '';

			if ( ! empty( self::$scss_files ) ) {
				foreach ( self::$scss_files as $file ) {
					$files_string .= \filesize( $file );
					$files_string .= \filemtime( $file );
				}
			}

			self::$check_sum = md5( $files_string );
		}
	}
}
