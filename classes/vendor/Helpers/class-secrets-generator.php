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

use WPSEC_Vendor\ParagonIE\ConstantTime\Base32;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\Secrets_Generator' ) ) {
	/**
	 * Helper class for generating all the secrets
	 *
	 * @since 1.0.0
	 */
	class Secrets_Generator {

		/**
		 * Generates TOTP secret key
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function totp_generate_key(): string {
			$value = Base32::encodeUpper( random_bytes( 12 ) );

			return $value;
		}
	}
}
