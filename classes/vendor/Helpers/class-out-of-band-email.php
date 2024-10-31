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

use WPSEC\Controllers\User;
use WPSEC_Vendor\OTPHP\TOTP;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\Out_Of_Band_Email' ) ) {

	/**
	 * Creates and send single sign in link to the user via email, and logs the user
	 *
	 * @since 1.0.0
	 */
	class Out_Of_Band_Email {

		/**
		 * Transient prefix used for storing the user OOB secret value
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $transient_prefix = '_wpsec_oob_';

		/**
		 * Nonce name for the URL check
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $nonce_name = '_nonce';

		/**
		 * Nonce prefix for the URL check
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $nonce_name_prefix = '_wpsec_nonce_';

		/**
		 * Sets the time period for how long the TOTP is valid
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $period = 300;

		/**
		 * How many digits the password must be
		 *
		 * @since 1.0.0
		 *
		 * @var integer
		 */
		private static $digits = 12;

		/**
		 * Which algorithm is used for generating the proper TOTP
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		private static $algorithm = 'sha1';

		/**
		 * Used for AJAX calls, sends Out Of Band email to the user with single link
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function send_oob_email() {

			$subject = __( 'One time login link', 'secured-wp' );

			if ( ! isset( $_GET['userId'] ) || empty( $_GET['userId'] ) ) {
				\wp_die( 1 );
			}

			if ( ! isset( $_GET['redirectTo'] ) || empty( $_GET['redirectTo'] ) ) {
				$redirect_to = \user_admin_url();
			} else {
				$redirect_to = \esc_url_raw( \sanitize_text_field( \wp_unslash( $_GET['redirectTo'] ) ) );
			}

			$user_id = (int) $_GET['userId'];
			$to      = User::get_user( $user_id )->user_email;
			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			$message = \sprintf(
				/* translators: %1$s: Name of the user, %2$s: Url of the site, %3$s: Number of the login attempts */
				__( 'Hello %1$s,<br>Use the following link to login to site %2$s.<br>If not you who is trying to login, please contact the administrator immediately.<br>Click on the following link to login: %3$s', 'secured-wp' ),
				User::get_user( $user_id )->display_name,
				\get_bloginfo( 'name' ),
				self::generate_link( User::get_user( $user_id )->ID, $redirect_to )
			);

			ob_start();
			WP_Helper::get_template_part(
				'template-oob',
				array(
					'userName' => User::get_user( $user_id )->display_name,
					'blogName' => \get_bloginfo( 'name' ),
					'oobLink'  => self::generate_link( User::get_user( $user_id )->ID, $redirect_to ),
				)
			);
			$message = ob_get_clean();

			$message = Mail_Helper::get_mail_header( __( 'Out of band email link', 'secured-wp' ) ) . $message . Mail_Helper::get_mail_footer();

			$mail_status = \wp_mail( $to, $subject, $message, $headers );

			if ( $mail_status ) {
				echo \json_encode(
					array(
						'status'  => 'success',
						'message' => \sprintf(
							/* translators: %1$s: Seconds this link is valid */
							__( 'Check your mail, you have %1$s seconds to use that link.', 'secured-wp' ),
							self::$period
						),
						'time'    => self::$period,
					)
				);
			} else {
				echo \json_encode(
					array(
						'status'  => 'fail',
						'message' => \sprintf(
						/* translators: %1$s: Seconds this link is valid */
							__( 'There is a problem with mailing system on this site', 'secured-wp' ),
							self::$period
						),
						'time'    => self::$period,
					)
				);
			}
			\wp_die();
		}

		/**
		 * Validates the given password against the TOTP
		 *
		 * @since 1.0.0
		 *
		 * @param integer $user_id - the WP user ID.
		 * @param string  $pass - pass to check against.
		 *
		 * @return boolean
		 */
		public static function validate_user_oob( int $user_id, string $pass ): bool {
			$secret = \get_transient( self::$transient_prefix . $user_id );

			if ( ! isset( $secret ) || empty( $secret ) ) {
				return false;
			}

			$otp = TOTP::create(
				$secret,
				self::$period,
				self::$algorithm,
				self::$digits
			);

			if ( $pass === $otp->at( time() ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Returns the transient prefix
		 *
		 * @since 1.0.0
		 *
		 * @return string
		 */
		public static function get_transient_prefix() {
			return self::$transient_prefix;
		}

		/**
		 * Deletes the transient for specific user
		 *
		 * @since 1.0.0
		 *
		 * @param integer $user_id - The WP user ID.
		 *
		 * @return void
		 */
		public static function delete_transient( int $user_id ) {
			\delete_transient( self::$transient_prefix . $user_id );
		}

		/**
		 * Returns the name of the nonce
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_nonce_name(): string {
			return self::$nonce_name;
		}

		/**
		 * Returns the name prefix of the nonce
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_nonce_name_prefix(): string {
			return self::$nonce_name_prefix;
		}

		/**
		 * Generates OOB link which will be used in the email to the user
		 *
		 * @since 1.0.0
		 *
		 * @param integer $user_id - the WP user ID.
		 * @param string  $redirect_to - where to redirect - used in the link.
		 *
		 * @return string
		 */
		private static function generate_link( int $user_id, $redirect_to ): string {
			$link = '';

			$otp = TOTP::create(
				null, // Let the secret be defined by the class.
				self::$period,     // The period.
				self::$algorithm, // The digest algorithm.
				self::$digits      // The output will generate 6 digits.
			);

			\set_transient( self::$transient_prefix . $user_id, $otp->getSecret(), self::$period );

			$params                      = array();
			$params['wps_otp']           = $otp->at( time() );
			$params['user_id']           = $user_id;
			$params['redirect_to']       = $redirect_to;
			$params[ self::$nonce_name ] = \wp_create_nonce( self::$nonce_name_prefix . $user_id );

			$url = WP_Helper::get_site_url();

			$link = \add_query_arg(
				$params,
				$url
			);

			return "<a href='$link'>" . __( 'Click', 'secured-wp' ) . '</a>';
		}
	}
}
