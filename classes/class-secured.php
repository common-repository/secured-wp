<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use WPSEC\{
	Controllers\User,
	Controllers\Modules\Two_FA_Settings,
	Controllers\Modules\Remember_Me,
	Controllers\Modules\XML_RPC_Prevents,
	Views\UsersList,
	Helpers\JIT_SCSS_Compiler,
	Helpers\JIT_JS_Compiler,
	Helpers\WP_Helper,
	Helpers\Ajax_Requests,
};
use WPSEC\Views\User_Profile;
use WPSEC\Controllers\Settings;
use WPSEC\Validators\Validator;
use WPSEC\Controllers\Login_Check;

if ( ! class_exists( 'WPSEC\Secured' ) ) {
	/**
	 * Base class for WPS secured
	 *
	 * @since 1.0.0
	 */
	class Secured {

		/**
		 * Holds the status of the delete data upon uninstall setting
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		private static $delete_upon_uninstall = null;

		/**
		 * Sets all the necessary hooks and creates the tables if needed
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function init() {
			self::init_hooks();

			Settings::init();
		}

		/**
		 * Adds settings link
		 *
		 * @since 1.0.0
		 *
		 * @param array $links - Global links array.
		 *
		 * @return array
		 */
		public static function add_action_links( array $links ): array {
			$new_links = array(
				'<a href="' . \network_admin_url( 'admin.php?page=' . Settings::MENU_SLUG ) . '">' . \esc_html__( 'Settings', 'secured-wp' ) . '</a>',
			);
			return array_merge( $links, $new_links );
		}

		/**
		 * Checks for the minimum requirements and bails if they are not met
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function plugin_activation() {

			// Minimum PHP version check.
			if ( \version_compare( \phpversion(), WPSEC_REQUIRED_PHP_VERSION, '<' ) ) {

				// Plugin not activated info message.
				?>
				<div class="update-nag">
				<?php
				\printf(
				/* translators: %1$s: PHP version */
					\esc_html__( 'You need to update your PHP version to %1s.', 'secured-wp' ),
					\esc_html( WPSEC_REQUIRED_PHP_VERSION )
				);
				?>
				<br />
				<?php echo \esc_html__( 'Actual version is:', 'secured-wp' ); ?> <strong><?php echo \esc_html( phpversion() ); ?></strong>
				</div>
				<?php

				exit();
			}

			// Minimum WP version check.
			if ( version_compare( $GLOBALS['wp_version'], WPSEC_REQUIRED_WP_VERSION, '<' ) ) {

				// Plugin not activated info message.
				?>
				<div class="update-nag">
				<?php
				\printf(
				/* translators: %1$s: WP version */
					\esc_html__( 'You need to update your WP version to %1s.', 'secured-wp' ),
					\esc_html( WPSEC_REQUIRED_WP_VERSION )
				);
				?>
				<br />
				<?php echo \esc_html__( 'Actual version is:', 'secured-wp' ); ?> <strong><?php echo esc_html( $GLOBALS['wp_version'] ); ?></strong>
				</div>
				<?php

				exit;
			}
		}

		/**
		 * Returns the status of delete data upon uninstall
		 *
		 * @param mixed $blog_id - WP blog ID.
		 *
		 * @return mixed
		 *
		 * @since 1.0.0
		 */
		public static function is_delete_data_enabled( $blog_id = '' ) {
			if ( null === self::$delete_upon_uninstall ) {
				self::$delete_upon_uninstall =
				WP_Helper::get_option(
					WPSEC_PLUGIN_SECURED_DELETE_DATA_VAR_NAME,
					WPSEC_PLUGIN_SECURED_DELETE_DATA_VAR_DEFAULT,
					'',
					$blog_id,
					true
				);

				self::$delete_upon_uninstall = filter_var( self::$delete_upon_uninstall, FILTER_VALIDATE_BOOLEAN );
			}

			return self::$delete_upon_uninstall;
		}

		/**
		 * Deletes data from the options
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public static function delete_data() {
			WP_Helper::delete_option( WPSEC_PLUGIN_SECURED_DELETE_DATA_VAR_NAME );
		}

		/**
		 * Shows the content for user settings when short code is used
		 *
		 * @return string - the parsed HTML.
		 *
		 * @since 1.6
		 */
		public static function settings_short_code() {
			ob_start();

			if ( User::is_currently_logged() ) {
				$user = User::get_user();
				User_Profile::user_edit_profile( $user );

			} else {
				?>
				<h2><?php echo \esc_html__( 'You must be logged in to see this content', 'secured-wp' ); ?></h2>
				<?php
			}
			$content = ob_get_clean();

			return $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Inits all the hooks the plugin will use
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		private static function init_hooks() {

			/**
			 * Check users and login attempts - validates the login
			 */
			\add_action( 'authenticate', array( Login_Check::class, 'check' ), 9999, 3 );

			/**
			 * Validate OOB login
			 */
			\add_action( 'login_form_confirm_oob', array( Login_Check::class, 'login_validate_oob' ), 1 );

			/**
			 * Adds shortcode - that gives the ability to create our own page with user settings
			 */
			\add_shortcode( 'wps_custom_settings', array( __CLASS__, 'settings_short_code' ) );

			/**
			 * Validates 2FA login from
			 */
			\add_action( 'login_form_validate_2fa', array( Login_Check::class, 'login_validate_two_fa' ) );
			if ( class_exists( 'WooCommerce' ) && (bool) Two_FA_Settings::get_global_settings_value() ) {
				\add_action( 'wp_loaded', array( Login_Check::class, 'login_validate_two_fa' ), 20 );
				/**
				 * Show QR code in the user profile section for WOOCOMMERCE
				 */
				if ( User::is_currently_logged() && ! (bool) User::is_two_fa_user_excluded() ) {
					\add_action(
						'woocommerce_account_dashboard',
						array( User_Profile::class, 'edit_user_qr' )
					);
				}
			}

			if ( Remember_Me::get_global_settings_value() ) {
				\add_action( 'set_logged_in_cookie', array( Login_Check::class, 'set_remember_me' ) );
			}
			\add_action( 'wp_logout', array( Login_Check::class, 'log_out' ), 1 );

			if ( XML_RPC_Prevents::get_global_settings_value() ) {
				add_filter( 'xmlrpc_enabled', '__return_false', 5 );
				remove_action( 'wp_head', 'rsd_link' );
				add_filter( 'bloginfo_url', array( 'WPSEC\\Controllers\\Modules\\XML_RPC_Prevents', 'remove_header_ping_link' ), 11, 2 );
				add_filter( 'wp_headers', array( 'WPSEC\\Controllers\\Modules\\XML_RPC_Prevents', 'remove_xml_rpc_link' ), PHP_INT_MAX );
				add_filter( 'xmlrpc_methods', array( 'WPSEC\\Controllers\\Modules\\XML_RPC_Prevents', 'remove_xml_rpc_methods' ), PHP_INT_MAX );
			}

			/**
			 * No user is logged in - add ajax hooks
			 */
			if ( ! User::is_currently_logged() && Two_FA_Settings::is_oob_enabled() ) {
				// \wp_enqueue_script( 'jquery' );
				/**
				 * Adds ajax request for sending the OOB
				 */
				\add_action( 'wp_ajax_nopriv_send_oob', array( 'WPSEC\\Helpers\\Out_Of_Band_Email', 'send_oob_email' ) );
				\add_action( 'wp_ajax_send_oob', array( 'WPSEC\\Helpers\\Out_Of_Band_Email', 'send_oob_email' ) );
			}

			if ( is_admin() ) {

				/**
				 * Adds columns to the users listing table
				 * showing the status of every given user
				 */
				UsersList::init();

				// Hide all unrelated to the plugin notices on the plugin admin pages.
				\add_action( 'admin_print_scripts', array( __CLASS__, 'hide_unrelated_notices' ) );

				// User_Profile.
				global $pagenow;
				if ( 'profile.php' !== $pagenow || 'user-edit.php' !== $pagenow ) {
					\add_action( 'show_user_profile', array( 'WPSEC\Views\User_Profile', 'user_edit_profile' ) );
					\add_action( 'edit_user_profile', array( 'WPSEC\Views\User_Profile', 'user_edit_profile' ) );

					// AJAX request for the user - do we need this globally for the Admin Part of the WP ?.
					Ajax_Requests::init_admin();

					\add_action( 'personal_options_update', array( 'WPSEC\Views\User_Profile', 'regenerate_qr_code' ), 10, 1 );
					\add_action( 'edit_user_profile_update', array( 'WPSEC\Views\User_Profile', 'regenerate_qr_code' ), 10, 1 );
				}
			}

			/**
			 * If user is logged in and there is woocommerce installed and the method is enabled,
			 * we gonna need the AJAX methods available.
			 */
			if ( User::is_currently_logged() ) {
				// if ( class_exists( 'WooCommerce' ) ) {.
				if ( (bool) Two_FA_Settings::get_global_settings_value() ) {
					// AJAX request for the user - do we need this globally for the Admin Part of the WP ?.
					Ajax_Requests::init_admin();
				}
				// }
			}
		}

		/**
		 * Remove all non-WP Mail SMTP plugin notices from our plugin pages.
		 *
		 * @since 2.0.0
		 */
		public static function hide_unrelated_notices() {
			// Bail if we're not on our screen or page.
			if ( ! self::is_admin_page() ) {
				return;
			}

			self::remove_unrelated_actions( 'user_admin_notices' );
			self::remove_unrelated_actions( 'admin_notices' );
			self::remove_unrelated_actions( 'all_admin_notices' );
			self::remove_unrelated_actions( 'network_admin_notices' );
		}

		/**
		 * Remove all notices from the our plugin pages based on the provided action hook.
		 *
		 * @since 2.0.0
		 *
		 * @param string $action - The name of the action.
		 */
		public static function remove_unrelated_actions( $action ) {
			global $wp_filter;

			if ( empty( $wp_filter[ $action ]->callbacks ) || ! is_array( $wp_filter[ $action ]->callbacks ) ) {
				return;
			}

			foreach ( $wp_filter[ $action ]->callbacks as $priority => $hooks ) {
				foreach ( $hooks as $name => $arr ) {
					if (
					( // Cover object method callback case.
						is_array( $arr['function'] ) &&
						isset( $arr['function'][0] ) &&
						is_object( $arr['function'][0] ) &&
						false !== strpos( ( get_class( $arr['function'][0] ) ), 'WPSEC' )
					) ||
					( // Cover class static method callback case.
						! empty( $name ) &&
						false !== strpos( ( $name ), 'WPSEC' )
					)
					) {
						continue;
					}

					unset( $wp_filter[ $action ]->callbacks[ $priority ][ $name ] );
				}
			}
		}

		/**
		 * Check whether we are on an admin and plugin page.
		 *
		 * @since 2.0.0
		 *
		 * @return bool
		 */
		public static function is_admin_page() {

			return \is_admin() && ( false !== Settings::is_plugin_settings_page() );
		}
	}
}
