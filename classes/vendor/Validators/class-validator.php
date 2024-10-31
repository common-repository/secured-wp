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

namespace WPSEC\Validators;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Validators\Validator' ) ) {
	/**
	 * Validator class - responsible for validating of different simple variables
	 *
	 * @since 1.0.0
	 */
	class Validator {

		/**
		 * Hold all the errors collected through the validating process
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		private static $errors = array();

		/**
		 * Name of the variable checked for validation
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $variable_key = '';

		/**
		 * Validates given array with rules
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $vars - vars that need to be checked.
		 * @param array $rules - rules to be applied.
		 *
		 * @return bool
		 */
		public static function validate( $vars, $rules = array() ): bool {
			if ( ! is_array( $vars ) ) {
				$vars = array( $vars );
			}

			foreach ( $vars as $variable ) {
				$valid = true;
				self::variable_as_key( $variable );
				self::$errors[ self::$variable_key ] = ''; // init variable.

				if ( empty( $rules ) ) {
					$valid                               = false;
					self::$errors[ self::$variable_key ] = __( 'No rules are set - nothing to test against', 'secured-wp' );
				}
				foreach ( $rules as $rule => $params ) {
					$rule = strval( $rule );
					switch ( $rule ) {
						case 'required':
							if ( ! isset( $variable ) ) {
								$valid                               = false;
								self::$errors[ self::$variable_key ] = __( 'Variable is not set', 'secured-wp' );
							}
							break;
						case 'positiveInt':
							$valid = self::validate_number( $variable, 1 );
							break;
						case 'intRange':
							$min = false;
							$max = false;
							if ( isset( $params['min'] ) ) {
								$min = $params['min'];
							}
							if ( isset( $params['max'] ) ) {
								$max = $params['max'];
							}
							$valid = self::validate_number( $variable, $min, $max );
							break;
						case 'email':
							$valid = self::validate_email( $variable );
							break;
						default:
							$valid                               = false;
							self::$errors[ self::$variable_key ] = __( 'No rules are set - nothing to test against', 'secured-wp' );
							break;
					}
				}
			}
			return $valid;
		}

		/**
		 * Returns array with the errors collected (if any)
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public static function get_errors(): array {
			return self::$errors;
		}

		/**
		 * Validates email
		 *
		 * @since 1.0.0
		 *
		 * @param string $variable - mail string to be validated.
		 *
		 * @return bool
		 */
		public static function validate_email( $variable ): bool {
			$valid = true;

			if ( false === ( $valid = self::filter_validate( $variable, 'email' ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, Generic.CodeAnalysis.AssignmentInCondition.Found
				self::$errors[ self::$variable_key ] .= __( 'Variable is not valid e-mail', 'secured-wp' ) . "\n";
				$valid                                = false;
			}
			return $valid;
		}

		/**
		 * Uses standard PHP filter validation
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $variable - variable that needs to be checked.
		 * @param mixed $type - type to be checked against.
		 *
		 * @return bool
		 */
		public static function filter_validate( $variable, $type ): bool {
			$result = false;

			switch ( $type ) {
				case 'email':
					$result = (bool) filter_var( $variable, FILTER_VALIDATE_EMAIL );
					break;

				case 'int':
					if ( false !== ( $result = filter_var( $variable, FILTER_VALIDATE_INT ) ) ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure, Generic.CodeAnalysis.AssignmentInCondition.Found
						$result = true;
					}
					break;

				case 'bool':
					if ( null !== filter_var( $variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ) {
						$result = true;
					}
					break;

				default:
					break;
			}

			return $result;
		}

		/**
		 * Validates numbers
		 *
		 * @since 1.0.0
		 *
		 * @param type      $variable - variable that needs to be checked.
		 * @param type|bool $min - minimul value.
		 * @param type|bool $max - maximum value.
		 *
		 * @return bool
		 */
		public static function validate_number( $variable, $min = false, $max = false ): bool {
			$valid          = true;
			$filter_options = array( 'options' => array() );
			if ( $min ) {
				$filter_options['options']['min_range'] = $min;
			}
			if ( $max ) {
				$filter_options['options']['max_range'] = $max;
			}
			if ( ! is_numeric( $variable ) ) {
				$valid                               = false;
				self::$errors[ self::$variable_key ] = __( 'Variable is not integer', 'secured-wp' );
			} elseif ( is_string( $variable ) ) {
				if ( false === ctype_digit( $variable ) ) {
					self::$errors[ self::$variable_key ] .= __( 'Variable is not positive integer!', 'secured-wp' ) . "\n";
					$valid                                = false;
				}

				if ( $valid ) {
					/*
					 *  It is valid but it is string, casting gives the
					 *  opportunity to validate the range using filter_var
					 */
					$variable = (int) $variable;
				}
			}
			if ( $valid && false === filter_var( $variable, FILTER_VALIDATE_INT, $filter_options ) ) {
				$valid = false;
				if ( $min > 0 && false === $max ) {
					self::$errors[ self::$variable_key ] .= __( 'Variable is not positive integer!', 'secured-wp' ) . "\n";
				}
				if ( false !== $max ) {
					self::$errors[ self::$variable_key ] .= __( 'Variable is not in given range', 'secured-wp' ) . "\n";
				}
			}
			return $valid;
		}

		/**
		 * You can not use integers as key without breaking the PHP arrays most of the time
		 *
		 * @since 1.0.0
		 *
		 * @param type $variable - variable usually int that needs to be converted to string.
		 *
		 * @return void
		 */
		private static function variable_as_key( $variable ): void {
			self::$variable_key = '_' . $variable;
		}
	}
}
