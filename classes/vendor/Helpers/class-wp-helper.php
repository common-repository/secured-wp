<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 *
 * @since 2.0.0
 */

declare(strict_types=1);

namespace WPSEC\Helpers;

use WPSEC\Validators\Validator;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\WP_Helper' ) ) {
	/**
	 * WP helpers function class
	 *
	 * @since 1.0.0
	 */
	class WP_Helper {

		/**
		 * Deletes WP option by given name
		 *
		 * @since 1.0.0
		 *
		 * @param string $option - Option name.
		 * @param string $network_id - WP network ID.
		 * @param string $blog_id - WP blog ID.
		 *
		 * @return void
		 */
		public static function delete_option(
			string $option,
			$network_id = '',
			$blog_id = ''
		) {

			if ( self::is_multisite() ) {
				\delete_blog_option( self::get_blog_id( $blog_id ), $option );
			} else {
				\delete_network_option( self::get_network_id( $network_id ), $option );
			}
		}

		/**
		 * Sets WP Option
		 *
		 * @since 1.0.0
		 *
		 * @param string $option - option name.
		 * @param mixed  $value - value for the given option.
		 * @param string $network_id - WP network ID.
		 * @param string $blog_id - WP blog ID.
		 * @param bool   $global_option - is that global variable ? Multisite install.
		 *
		 * @return void
		 */
		public static function set_option(
			string $option,
			$value,
			$network_id = '',
			$blog_id = '',
			bool $global_option = false
		) {

			if ( self::is_multisite() && ! $global_option ) {
				\update_blog_option( self::get_blog_id( $blog_id ), $option, $value );
			} else {
				\update_network_option( self::get_network_id( $network_id ), $option, $value );
			}
		}

		/**
		 * Returns option value
		 *
		 * @since 1.0.0
		 *
		 * @param string $option - Option name to extract value from.
		 * @param mixed  $default_value - Default value tor return if option is not present.
		 * @param string $network_id - Network Id to extract value from.
		 * @param string $blog_id - Blog Id to extract value from.
		 * @param bool   $global_option - global setting or blog related setting flag.
		 *
		 * @return mixed
		 */
		public static function get_option(
			string $option,
			$default_value = false,
			$network_id = '',
			$blog_id = '',
			bool $global_option = false
		) {

			if ( self::is_multisite() && ! $global_option ) {
				return \get_blog_option( self::get_blog_id( $blog_id ), $option, $default_value );
			} else {
				return \get_network_option( self::get_network_id( $network_id ), $option, $default_value );
			}

			return false;
		}

		/**
		 * Checks if current WP installation is multisite or not
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		public static function is_multisite(): bool {
			if ( \is_multisite() ) {
				return true;
			}
			return false;
		}

		/**
		 * Returns current blog ID
		 *
		 * @since 1.0.0
		 *
		 * @param string $blog_id - WP blog ID.
		 *
		 * @return integer
		 */
		public static function get_blog_id( $blog_id = '' ): int {

			if ( '' === $blog_id && self::is_multisite() ) {
				$blog_id = \get_current_blog_id();
			} elseif ( ! Validator::filter_validate( $blog_id, 'int' ) ) {
					$blog_id = 0;
			}

			return (int) ( ( $blog_id ) ? $blog_id : 0 );
		}

		/**
		 * Returns current network ID
		 *
		 * @since 1.0.0
		 *
		 * @param string $network_id - WP network ID.
		 *
		 * @return mixed
		 */
		public static function get_network_id( $network_id = '' ) {

			if ( '' === $network_id && self::is_multisite() ) {
				$network_id = \get_current_network_id();
			} elseif ( ! Validator::filter_validate( $network_id, 'int' ) ) {
				$network_id = null;
			}

			return $network_id;
		}

		/**
		 * Returns the current site URL
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_site_url() {
			$url = \get_site_url( \get_current_blog_id() );

			return $url;
		}

		/**
		 * Returns all the sites in multisite install
		 *
		 * @param array $args - Array with the sites.
		 *
		 * @return array
		 *
		 * @since 1.0.0
		 */
		public static function get_sites( $args = array() ): array {
			return \get_sites( $args );
		}

		/**
		 * Add support for $args to the template part
		 *
		 * @param string $template_slug - the name of the template.
		 * @param array  $args - arguments to pass to template.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function get_template_part( string $template_slug, array $args = array() ) {

			if ( $args && is_array( $args ) ) {
                extract( $args ); 
			}

			$located = \locate_template( "{$template_slug}.php" );

			if ( empty( $located ) ) {
				$located = WPSEC_PLUGIN_SECURED_PATH . \DIRECTORY_SEPARATOR . 'templates' . \DIRECTORY_SEPARATOR . "{$template_slug}.php";
			}

			include $located;
		}

		/**
		 * Is the current request an XML-RPC or REST request.
		 *
		 * @return bool
		 */
		public static function is_api_request(): bool {
			if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
				return true;
			}

			if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
				return true;
			}

			return false;
		}
	}
}
