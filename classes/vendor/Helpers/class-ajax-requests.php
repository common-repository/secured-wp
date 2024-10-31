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
use WPSEC\Validators\Validator;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\Ajax_Requests' ) ) {
	/**
	 * AJAX requests helper class
	 *
	 * @since 1.0.0
	 */
	class Ajax_Requests {

		/**
		 * Initiates and sets the AJAX methods used from the plugin in the admin section of the WP
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function init_admin() {

			// delete logged in device for user.
			\add_action( 'wp_ajax_logged_device_delete', array( __CLASS__, 'delete_remember_me_device' ) );

			// delete all logged in devices for user.
			\add_action( 'wp_ajax_all_logged_device_delete', array( __CLASS__, 'delete_all_remember_me_devices' ) );

			// delete all logged in devices for user.
			\add_action( 'wp_ajax_wps_delete_qr', array( __CLASS__, 'delete_qr_code_for_user' ) );
		}

		/**
		 * AJAX request for deleting the device from the logged in devices for given user
		 * It also can be used outside AJAX requests
		 *
		 * @since 1.0.0
		 *
		 * ! WP do_action sends first empty parameter if there are no parameters
		 * @param string $device - if that is set to null, global POST array will be checked.
		 * @param mixed  $user - WP user for which remember me must be deleted.
		 *
		 * @return void
		 */
		public static function delete_remember_me_device( string $device = null, $user = null ) {
			if ( ! isset( $_POST['nonce'] ) ||
				empty( $_POST['nonce'] ) ||
				! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ), 'wp-secured-delete_device-ajax-nonce' ) ) {
				die();
			}

			if ( empty( $device ) && isset( $_POST['device'] ) && ! empty( $_POST['device'] ) ) {
				$device = base64_decode( \sanitize_text_field( \wp_unslash( $_POST['device'] ) ) );
			}

			if ( ! isset( $_POST['user'] ) || empty( $_POST['user'] ) ) {
				die();
			}

			$user = \sanitize_text_field( \wp_unslash( $_POST['user'] ) );

			if ( Validator::filter_validate( $user, 'int' ) ) {

				if ( (int) \get_current_user_id() === (int) $user || \current_user_can( 'manage_options' ) ) {
					if ( User::delete_logged_in_device( $device, $user ) ) {
						echo \json_encode( array( 'result' => 'success' ) );
					} else {
						echo \json_encode( array( 'result' => 'failed' ) );
					}
				}
			}

			die();
		}

		/**
		 * AJAX request for deleting all the devices from the logged in devices for given user
		 * It also can be used outside AJAX requests
		 *
		 * @since 1.0.0
		 *
		 * ! WP do_action sends first empty parameter if there are no parameters
		 * TODO: extend this and the method above so it can receives parameters as well
		 * @param mixed $user - WP user for which remember me device must be deleted.
		 *
		 * @return void
		 */
		public static function delete_all_remember_me_devices( $user = null ) {
			if ( ! isset( $_POST['nonce'] ) ||
				empty( $_POST['nonce'] ) ||
				! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ), 'wp-secured-delete_all_device-ajax-nonce' ) ) {
				die();
			}

			if ( ! isset( $_POST['user'] ) || empty( $_POST['user'] ) ) {
				die();
			}

			$user = \sanitize_text_field( \wp_unslash( $_POST['user'] ) );

			if ( Validator::filter_validate( $user, 'int' ) ) {

				if ( (int) \get_current_user_id() === (int) $user || \current_user_can( 'manage_options' ) ) {
					if ( User::delete_all_logged_in_devices( $user ) ) {
						echo \json_encode( array( 'result' => 'success' ) );
					} else {
						echo \json_encode( array( 'result' => 'failed' ) );
					}
				}
			}

			die();
		}

		/**
		 * Deletes QR code for the given user
		 *
		 * @param mixed $user - WP user for which remember me device must be deleted.
		 *
		 * @return void
		 *
		 * @since 1.5
		 * @since 2.0.0 $user parameter is added
		 */
		public static function delete_qr_code_for_user( $user = null ) {
			if ( ! isset( $_POST['nonce'] ) ||
				empty( $_POST['nonce'] ) ||
				! \wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['nonce'] ) ), 'wp-secured-wps_delete_qr-ajax-nonce' ) ) {
				die();
			}

			if ( ! isset( $_POST['user'] ) || empty( $_POST['user'] ) ) {
				die();
			}

			$user = \sanitize_text_field( \wp_unslash( $_POST['user'] ) );

			if ( Validator::filter_validate( $user, 'int' ) ) {

				if ( (int) \get_current_user_id() === (int) $user || \current_user_can( 'manage_options' ) ) {
					User::delete_user_totp( $user );
					echo \json_encode( array( 'result' => 'success' ) );
				}
			}

			die();
		}
	}
}
