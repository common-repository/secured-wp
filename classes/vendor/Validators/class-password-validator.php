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

use ZxcvbnPhp\Zxcvbn;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'WPSEC\Helpers\ValidatePassword' ) ) {
	/**
	 * Responsible for proper Password Validation
	 *
	 * @since 2.0.0
	 */
	class ValidatePassword {

		/**
		 * Valid password regular expression string
		 *
		 * @since 2.0.0
		 *
		 * @var string
		 */
		private static $valid_re = '/(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!\"#$%&\'\(\'\)* ,\-.\/\:;<=>\?@\[\\\\\]\^_`\{\|\}~])[A-Za-z\d!\"#$%&\'\(\)* ,\-.\/\:;<=>\?@\[\\\\\]\^_`\{\|\}~\"]{16,}/m';

		/**
		 * Validates string against password validation expression
		 *
		 * @param string $password provided password which must be validated.
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function validate( string $password ): bool {
			$ret_val = false;

			if ( '' !== trim( $password ) ) {
				if ( 1 === \preg_match( self::$valid_re, $password ) ) {
					$ret_val = true;
				}
			}
			return $ret_val;
		}

		/**
		 * Gets password strength score
		 *
		 * @param string $password Checks the strength of the password.
		 *
		 * @return integer
		 *
		 * @since 2.0.0
		 */
		public static function check_password_strength_score( string $password ): int {
			$zxcvbn = new Zxcvbn();

			$score = $zxcvbn->passwordStrength( $password );

			return $score['score'];
		}
	}
}
