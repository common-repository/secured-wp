<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Controllers;

use WPSEC\Validators\Validator;
use WPSEC\Helpers\{
	Notify_Admin,
	Out_Of_Band_Email,
};
use WPSEC\Controllers\{
	Modules\Two_FA_Settings,
	Modules\Login_Attempts,
	Modules\Remember_Me,
};
use WPSEC\Mosules\Views\Login_Forms;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Controllers\Login_Check' ) ) {
	/**
	 * Checks user input after user login
	 *
	 * @since 1.0.0
	 */
	class Login_Check {

		/**
		 * Checks the user login credentials
		 *
		 * TODO: change the logging in user check - set proper priorities
		 *
		 * 1. Checks if user is locked
		 * 2. Checks if module is enabled
		 * 3. Checks if user is set and not error
		 * 4. Checks login attempts
		 * 5. Clear and login
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_USER $user - user object or error.
		 * @param string   $username - the username currently tries to login.
		 * @param string   $password - the password for currently trying to login user.
		 *
		 * @return \WP_User|\WP_Error
		 */
		public static function check( $user, $username, $password ) {

			if ( \is_a( $user, '\WP_Error' ) ) {
				return $user;
			}

			/**
			 * If Login_Attempts is not enabled - return the user
			 */
			if ( ! (bool) Login_Attempts::get_global_settings_value() ) {
				if ( (bool) Two_FA_Settings::get_global_settings_value() && Two_FA_Settings::is_totp_enabled() && ! User::is_two_fa_user_excluded( $username ) ) {
					Login_Forms::login_totp( '', $user );

					exit();
				}

				return $user;
			}

			/**
			 * If 2FA is enabled and user is excluded but the login attempts is not enabled - return the user
			 */
			// if ( (bool) ! Two_FA_Settings::get_global_settings_value() || User::is_two_fa_user_excluded( $username ) ) {
			// if ( (bool) ! Login_Attempts::get_global_settings_value() ) {
			// return $user;
			// }
			// }

			/**
			 * Checks if user is locked out and if the module is enabled
			 */
			if ( User::is_locked( $username ) ) {

				\wp_clear_auth_cookie();

				$error = new \WP_Error(
					'authentication_failed',
					__( '<strong>Error</strong>: Too soon.', 'secured-wp' )
				);
				\do_action( 'wp_login_failed', $username, $error );

				return $error;
			}

			if ( null === $user || \is_wp_error( $user ) ) {
				if ( (bool) Login_Attempts::get_global_settings_value() ) {
					$user_tried_to_log_in = \get_user_by( 'login', $username );

					if ( $user_tried_to_log_in ) {

						Login_Attempts::increase_login_attempts( $user_tried_to_log_in );

						if (
						Login_Attempts::get_login_attempts( $user_tried_to_log_in ) > Login_Attempts::get_allowed_attempts() ) {

							User::lock_user( $username );

							Notify_Admin::send_notification_email( $user_tried_to_log_in, Login_Attempts::get_login_attempts( $user_tried_to_log_in ) );

							$error = new \WP_Error(
								'authentication_failed',
								__( '<strong>Error</strong>: Too many attempts.', 'secured-wp' )
							);
							\do_action( 'wp_login_failed', $username, $error );

							return $error;
						}
					}
				}
			} elseif ( ! \is_wp_error( $user ) ) {
				Login_Attempts::clear_login_attempts( $user );
				if ( (bool) Two_FA_Settings::get_global_settings_value() && Two_FA_Settings::is_totp_enabled() && ! User::is_two_fa_user_excluded( $username ) ) {
					Login_Forms::login_totp( '', $user );

					exit();
				}
			}

			return $user;
		}

		/**
		 * Checks and validates 2FA login
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function login_validate_two_fa() {
			if ( ! isset( $_POST[ Login_Forms::get_login_nonce_name() ] ) ) {
				return;
			}

			if (
				! isset( $_POST[ Login_Forms::get_login_nonce_name() ] )
				|| ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ Login_Forms::get_login_nonce_name() ] ) ), Login_Forms::get_login_nonce_name() )
				) {
				\wp_nonce_ays( __( 'Non Valid nonce' ) );
			}

			$user_id     = ( isset( $_REQUEST['2fa-auth-id'] ) ) ? (int) $_REQUEST['2fa-auth-id'] : false;
			$auth_code   = ( isset( $_REQUEST['authcode'] ) ) ? (string) \sanitize_text_field( \wp_unslash( $_REQUEST['authcode'] ) ) : false;
			$redirect_to = ( isset( $_REQUEST['redirect_to'] ) ) ? (string) \esc_url_raw( \sanitize_text_field( \wp_unslash( $_REQUEST['redirect_to'] ) ) ) : '';

			if ( $user_id && $auth_code ) {
				if ( User::validate_totp_authentication( $auth_code, $user_id ) ) {
					\wp_set_auth_cookie( User::get_user()->ID );

					Login_Forms::interim_login();

					// Check if user has any roles/caps set - if doesn't, we know its a "network" user.
					if ( \is_multisite() &&
						! \get_active_blog_for_user( User::get_user()->ID ) &&
						empty( User::get_user()->caps ) ) {
						$redirect_to = \user_admin_url();
					} else {
						$redirect_to = \apply_filters(
							'login_redirect',
							$redirect_to,
							$redirect_to,
							User::get_user()
						);
					}

					/** Sets the TOTP status of the user */
					if ( ! User::is_totp_user_enabled() ) {
						User::enable_totp();
					}

					\wp_safe_redirect( $redirect_to );

					exit();
				} else {
					$error = __( 'Invalid code provided', 'secured-wp' );
					Login_Forms::login_totp( $error );

					exit();
				}
			}
		}

		/**
		 * Validates the link from the WP form
		 *
		 * @hook login_form_confirm_oob
		 *
		 * @return void
		 *
		 * @since 1.7
		 */
		public static function login_validate_oob() {
			self::check_oob_and_login( true );
		}

		/**
		 * Send remember me cookie - Remember_Me call
		 * Sets the users logged in devices (adds new one if necessarily)
		 *
		 * @hook set_logged_in_cookie
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function set_remember_me() {
			if ( ! isset( $_POST[ Login_Forms::get_login_nonce_name() ] ) ) {
				return;
			}

			if (
				! isset( $_POST[ Login_Forms::get_login_nonce_name() ] )
				|| ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST[ Login_Forms::get_login_nonce_name() ] ) ), Login_Forms::get_login_nonce_name() )
				) {
				\wp_nonce_ays( __( 'Non Valid nonce' ) );
			}

			if ( isset( $_POST['rememberme'] ) && ! empty( $_POST['rememberme'] ) ) {
				Remember_Me::set_remember_me( User::get_user()->ID );
				User::set_logged_in_device( Remember_Me::get_device() );
			}
		}

		/**
		 * Checks Out of band email link and logging the user if passed
		 *
		 * @param bool $second_pass - first pass comes from e-mail (link), second pass comes from WP form itself.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function check_oob_and_login( bool $second_pass = false ) {
			// No logged user? continue if so.
			if ( ! User::is_currently_logged() ) {
				$params = $_GET;
				if ( $second_pass ) {
					$params = $_POST;
				}
				// is there otp key?
				if ( isset( $params['wps_otp'] ) && ! empty( $params['wps_otp'] ) ) {
					// is there user id?
					if ( isset( $params['user_id'] ) && ! empty( $params['user_id'] ) ) {

						// are the formats correct.
						if ( Validator::filter_validate( \sanitize_text_field( \wp_unslash( $params['user_id'] ) ), 'int' ) &&
						Validator::validate_number( \sanitize_text_field( \wp_unslash( $params['wps_otp'] ) ) ) ) {
							$user_id = (int) \sanitize_text_field( \wp_unslash( $params['user_id'] ) );
							$wps_otp = (string) \sanitize_text_field( \wp_unslash( $params['wps_otp'] ) );

							$nonce_name = Out_Of_Band_Email::get_nonce_name();

							if ( isset( $params[ $nonce_name ] ) && ! empty( $params[ $nonce_name ] ) ) {
								if ( ! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $params[ $nonce_name ] ) ), Out_Of_Band_Email::get_nonce_name_prefix() . $user_id ) ) {
									exit();
								}

								// check transient.
								$data_timeout = \get_option( '_transient_timeout_' . Out_Of_Band_Email::get_transient_prefix() . ( (string) $user_id ) );
								if ( $data_timeout > time() ) {
									if ( Out_Of_Band_Email::validate_user_oob( $user_id, $wps_otp ) ) {

										if ( ! $second_pass ) {
											Login_Forms::login_oob( '', $user_id );
											exit();
										}
										\wp_clear_auth_cookie();
										Out_Of_Band_Email::delete_transient( $user_id );
										\wp_set_current_user( $user_id );
										\wp_set_auth_cookie( $user_id );

										if ( ! isset( $params['redirect_to'] ) || empty( $params['redirect_to'] ) ) {
											$redirect_to = \user_admin_url();
										} else {
											$redirect_to = \esc_url_raw( \wp_unslash( $params['redirect_to'] ) );
										}

										\wp_safe_redirect( $redirect_to );
										exit();
									}
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Clears the stored cookie and transient from the system
		 * Clean the logged in devices from the user array
		 *
		 * @hook wp_logout
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user_id - the id of the user.
		 *
		 * @return void
		 */
		public static function log_out( $user_id ) {
			Remember_Me::clear_remember_me( $user_id );
			User::clean_logged_in_devices( $user_id );
		}
	}
}
