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

use WPSEC\Helpers\WP_Helper;
use WPSEC\Helpers\Classes_Helper;
use WPSEC\Controllers\Modules\Login;
use WPSEC\Settings\Settings_Builder;
use WPSEC\Controllers\Modules\Remember_Me;
use WPSEC\Controllers\Modules\Login_Attempts;
use WPSEC\Controllers\Modules\Two_FA_Settings;
use WPSEC\Controllers\Modules\XML_RPC_Prevents;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( '\WPSEC\Controllers\Settings' ) ) {
	/**
	 * Holds information about the user
	 *
	 * @since 2.0.0
	 */
	class Settings {

		public const MENU_SLUG = 'wpsec_settings';

		public const SETTINGS_FILE_FIELD = 'wpsec_import_file';

		public const SETTINGS_FILE_UPLOAD_FIELD = 'wpsec_import_upload';

		public const OPTIONS_VERSION = '1'; // Incremented when the options array changes.

		private const NONCE_FIELD_ACTION = 'secwp-plugin-data';

		private const NONCE_FIELD_REQUEST_NAME = 'secwp-security';

		/**
		 * Array with the current options
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private static $current_options = array();

		/**
		 * Array with the default options
		 *
		 * @var array
		 *
		 * @since 2.0.0
		 */
		private static $default_options = array();

		/**
		 * Inits the class and sets all the hooks
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function init() {
			self::get_current_options();

			$modules = Classes_Helper::get_classes_by_namespace( '\WPSEC\Controllers\Modules' );

			foreach ( $modules as $module ) {
				if ( method_exists( $module, 'settings_init' ) ) {
					call_user_func_array( array( $module, 'settings_init' ), array() );
				}
			}

			if ( \is_admin() ) {
				/**
				 * Draws the save button in the settings
				 */
				\add_action( 'secwp_settings_save_button', array( __CLASS__, 'save_button' ) );

				/**
				 * Save Options
				 */
				\add_action( 'wp_ajax_secwp_plugin_data_save', array( __CLASS__, 'save_settings_ajax' ) );

				// Settings Related actions.
				\add_action( 'admin_menu', array( __CLASS__, 'settings_page' ) );

			}
		}

		/**
		 * Method responsible for AJAX data saving
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save_settings_ajax() {
			if ( ! \current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( \check_ajax_referer( self::NONCE_FIELD_ACTION, self::NONCE_FIELD_REQUEST_NAME ) ) {

				if ( isset( $_POST[ \WPSEC_PLUGIN_SECURED_SETTINGS_NAME ] ) && ! empty( $_POST[ \WPSEC_PLUGIN_SECURED_SETTINGS_NAME ] ) && \is_array( $_POST[ \WPSEC_PLUGIN_SECURED_SETTINGS_NAME ] ) ) {

					$data = array_map( 'sanitize_text_field', \stripslashes_deep( $_POST[ \WPSEC_PLUGIN_SECURED_SETTINGS_NAME ] ) );

					WP_Helper::set_option( WPSEC_PLUGIN_SECURED_SETTINGS_NAME, self::store_options( $data ) );

					\wp_send_json_success( 2 );
				}
				\wp_die();
			}
		}

		/**
		 * Displays the settings page.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function render() {
			\wp_enqueue_script(
				'secwp-admin-scripts',
				WPSEC_PLUGIN_SECURED_URL . 'classes/vendor/settings/js/admin/secwp-settings.js',
				array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'wp-color-picker', 'jquery-ui-autocomplete' ),
				WPSEC_PLUGIN_SECURED_VERSION,
				false
			);
			\wp_enqueue_style(
				'secwp-admin-style',
				WPSEC_PLUGIN_SECURED_URL . 'classes/vendor/settings/css/admin/style.css',
				array(),
				WPSEC_PLUGIN_SECURED_VERSION,
				'all'
			);

			self::wpsec_show_options();
		}

		/**
		 * The Settings Panel UI
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		private static function wpsec_show_options() {

			wp_enqueue_media();

			$settings_tabs = array(

				'login'            => array(
					'icon'  => 'admin-settings',
					'title' => esc_html__( 'Login', 'secured-wp' ),
				),

				'2fa-settings'     => array(
					'icon'  => 'buddicons-buddypress-logo',
					'title' => esc_html__( '2FA settings', 'secured-wp' ),
				),

				'remember-devices' => array(
					'icon'  => 'tickets-alt',
					'title' => esc_html__( 'Remember Devices', 'secured-wp' ),
				),

				'xml-rpc-settings' => array(
					'icon'  => 'admin-site',
					'title' => esc_html__( 'XML-RPC', 'secured-wp' ),
				),

				// 'backup'           => array(
				// 'icon'  => 'migrate',
				// 'title' => esc_html__( 'Export/Import', 'secured-wp' ),
				// ),
			);

			?>

			<div id="secwp-page-overlay"></div>

			<div id="secwp-saving-settings">
				<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
					<circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
					<path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
					<path class="checkmark__error_1" d="M38 38 L16 16 Z" />
					<path class="checkmark__error_2" d="M16 38 38 16 Z" />
				</svg>
			</div>

			<div class="secwp-panel wrap">

				<div class="secwp-panel-tabs">
					<div class="secwp-logo">
						<svg fill="currentColor" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"  viewBox="0 0 200 230" xml:space="preserve">
						<path class="cls-1" d="M94.631-1c14.306-.213,93.689,32.993,98.518,40.022,11.033,13.008-.534,42.095,1.99,60.033,10.4,73.9-29.1,114.38-84.586,126.07-12.891,2.715-26.411-1.614-34.83-4C25.66,206.917,1.091,172.951,1.088,108.06c0-13.736-3.443-59.436,1.99-67.037C11.767,25.63,78.1,12.184,94.631-1Zm0,8C79.964,15.1,17.115,34.561,11.04,44.025c-7.239,11.276-1.075,33.4-1,48.026,0.364,66.565,10.492,101.161,56.723,120.066,57.045,23.328,104.241-16.745,116.43-57.031,5.242-17.323,2.832-34.761,5.971-56.031,2.359-15.987,3.486-38.96,0-54.03C174.588,34.834,115.973,7.2,94.631,7Zm-1,44.024c16.765-.948,32,8.14,26.868,25.014h-1c-9.9-5.2-8.772-16.426-22.888-18.01C89,63.386,85.883,67.152,81.694,76.042H77.714l-1-1C76.721,59.352,83.989,56.833,93.636,51.029ZM62.787,121.067c-11.458.251-26.459,1.089-34.83-2v-3l1.99-2,32.839-1v-8l-12.937-2-5.971-7c-1.9-5.7-1.987-12.431-1-19.01l4.976-1c2.829,5.537,3.816,16.054,6.966,21.012H60.8c7.413-10.668,48.122-22.016,67.669-11.006,6.609,3.722,4.393,9.375,14.927,11.006,2.96-4.838,4.117-16.371,7.961-21.012l3.981,2q-1,10.5-1.991,21.012c-5.281,3.45-10.62,4.8-17.912,7q0.5,4,.995,8c9.142-2.655,24.894-.963,32.84,1q0.5,1,1,2-1,1.5-1.991,3l-32.839,2q0.5,4,.995,8c8.773,0.324,14.95,1.579,17.913,8,3.679,5.943,2.906,14.575,0,20.011h-2.986c-2.609-8-3.855-15.24-7.961-21.012-6.867.167-7.234,1.363-10.946,4-6.906,18.609-38.953,27.636-57.718,13.007L61.791,138.077c-5.959.125-6.674,0.935-9.951,3l-2.985,17.01c-4.5-.86-2.961.116-4.976-3-8.363-17.507,7.9-24.832,18.908-25.014v-9.005ZM96.621,92.051c-10.915-.6-18.594-1.289-25.873,3-4.45,28.77-.6,51.432,19.9,59.032l5.971-4V92.051Zm6.966,0v62.034h0.995c19.134-5.62,35.85-37.627,20.9-61.033C118.219,91.346,112.436,91.721,103.587,92.051Z"/>
						</svg>
					</div>
					<div class="plugin-name" style="color: #fff; text-align: center; font-size: 1.4em; padding: 30px 0;"><?php echo \esc_html( WPSEC_PLUGIN_SECURED_NAME ); ?></div>

					<ul>
						<?php
						foreach ( $settings_tabs as $tab => $settings ) {

							$icon  = $settings['icon'];
							$title = $settings['title'];
							?>

							<li class="secwp-tabs secwp-options-tab-<?php echo \esc_attr( $tab ); ?>">
								<a href="#secwp-options-tab-<?php echo \esc_attr( $tab ); ?>">
									<span class="dashicons-before dashicons-<?php echo \esc_html( $icon ); ?> secwp-icon-menu"></span>
									<?php echo \esc_html( $title ); ?>
								</a>
							</li>
							<?php
						}

						?>
					</ul>
					<div class="clear"></div>
				</div> <!-- .secwp-panel-tabs -->

				<div class="secwp-panel-content">

					<form method="post" name="secwp_form" id="secwp_form" enctype="multipart/form-data">

					<div class="secwp-tab-head">
					<div id="theme-options-search-wrap">
						<input id="theme-panel-search" type="text" placeholder="<?php esc_html_e( 'Search', 'secured-wp' ); ?>">
						<div id="theme-search-list-wrap" class="has-custom-scroll">
							<ul id="theme-search-list"></ul>
						</div>
					</div>

					<div class="secwp-panel-head-elements">

						<?php do_action( 'secwp_settings_save_button' ); ?>
					
						<ul>
							<li>
								<div id="secwp-panel-darkskin-wrap">
									<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="48" y2="96"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="256" x2="256" y1="416" y2="464"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="108.92" y2="142.86"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="369.14" y2="403.08"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="464" x2="416" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="96" x2="48" y1="256" y2="256"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="403.08" x2="369.14" y1="403.08" y2="369.14"/><line style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" x1="142.86" x2="108.92" y1="142.86" y2="108.92"/><circle cx="256" cy="256" r="80" style="fill:none;stroke:#000;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px"/></svg></span>
									<input id="secwp-panel-darkskin" class="secwp-js-switch" type="checkbox" value="true">
									<span class="darkskin-label"><svg height="512" viewBox="0 0 512 512" width="512" xmlns="http://www.w3.org/2000/svg"><title/><path d="M160,136c0-30.62,4.51-61.61,16-88C99.57,81.27,48,159.32,48,248c0,119.29,96.71,216,216,216,88.68,0,166.73-51.57,200-128-26.39,11.49-57.38,16-88,16C256.71,352,160,255.29,160,136Z" style="fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px"/></svg></span>
									<script>
										if( 'undefined' != typeof localStorage ){
											var skin = localStorage.getItem('secwp-backend-skin');
											if( skin == 'dark' ){
												document.getElementById('secwp-panel-darkskin').setAttribute('checked', 'checked');

												var $html    = jQuery('html');

												$html.addClass('secwp-darkskin');
											}
										}
									</script>
								</div>
							</li>

						</ul>
					</div>

				</div>

						<?php
						foreach ( $settings_tabs as $tab => $settings ) {

							?>
						<!-- <?php echo \esc_attr( $tab ); ?> Settings -->
						<div id="secwp-options-tab-<?php echo \esc_attr( $tab ); ?>" class="tabs-wrap">

							<?php
							include_once WPSEC_PLUGIN_SECURED_PATH . 'classes/vendor/settings/settings-options/' . $tab . '.php';

							do_action( 'secwp_plugin_options_tab_' . $tab );
							?>

						</div>
							<?php
						}
						?>

						<?php wp_nonce_field( self::NONCE_FIELD_ACTION, self::NONCE_FIELD_REQUEST_NAME ); ?>
						<input type="hidden" name="action" value="secwp_plugin_data_save" />

						<div class="secwp-footer">

							<?php \do_action( 'secwp_settings_save_button' ); ?>
						</div>
					</form>

				</div><!-- .secwp-panel-content -->
				<div class="clear"></div>

			</div><!-- .secwp-panel -->

			<?php
		}

		/**
		 * Creates an option and draws it
		 *
		 * @param array $value - The array with option data.
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function build_option( array $value ) {
			$data = null;

			if ( empty( $value['id'] ) ) {
				$value['id'] = ' ';
			}

			if ( isset( self::get_current_options()[ $value['id'] ] ) ) {
				$data = self::get_current_options()[ $value['id'] ];
			}

			Settings_Builder::create( $value, WPSEC_PLUGIN_SECURED_SETTINGS_NAME . '[' . $value['id'] . ']', $data );
		}

		/**
		 * Returns the current options.
		 * Fills the current options array with values if empty.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function get_current_options(): array {
			if ( empty( self::$current_options ) ) {

				// Get the current settings or setup some defaults if needed.
				self::$current_options = \get_option( WPSEC_PLUGIN_SECURED_SETTINGS_NAME );
				if ( ! self::$current_options ) {

					self::$current_options = self::get_default_options();
					WP_Helper::set_option( WPSEC_PLUGIN_SECURED_SETTINGS_NAME, self::$current_options );
				} elseif ( ! isset( self::$current_options['version'] ) || self::OPTIONS_VERSION !== self::$current_options['version'] ) {

					// Set any unset options.
					foreach ( self::get_default_options() as $key => $value ) {
						if ( ! isset( self::$current_options[ $key ] ) ) {
							self::$current_options[ $key ] = $value;
						}
					}
					self::$current_options['version'] = self::OPTIONS_VERSION;
					WP_Helper::set_option( WPSEC_PLUGIN_SECURED_SETTINGS_NAME, self::$current_options );
				}
			}

			return self::$current_options;
		}

		/**
		 * Returns the default plugin options
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function get_default_options(): array {

			if ( empty( self::$default_options ) ) {
				// Define default options.
				self::$default_options = array(
					Login::GLOBAL_SETTINGS_NAME            => false,
					Login_Attempts::GLOBAL_SETTINGS_NAME   => false,
					XML_RPC_Prevents::GLOBAL_SETTINGS_NAME => false,
					Remember_Me::GLOBAL_SETTINGS_NAME      => false,
					Two_FA_Settings::GLOBAL_SETTINGS_NAME  => true,
					Two_FA_Settings::TOTP_SETTINGS_NAME    => true,
					Two_FA_Settings::OOB_SETTINGS_NAME     => true,
				);
			}

			return self::$default_options;
		}

		/**
		 * Checks if current page is plugin settings page
		 *
		 * @return boolean
		 *
		 * @since 2.0.0
		 */
		public static function is_plugin_settings_page() {

			$current_page = ! empty( $_REQUEST['page'] ) ? \sanitize_text_field( \wp_unslash( $_REQUEST['page'] ) ) : '';

			return self::MENU_SLUG === $current_page;
		}

		/**
		 * Shows the save button in the settings
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function save_button() {

			?>
			<div class="secwp-panel-submit">
				<button name="save" class="secwp-save-button secwp-primary-button button button-primary button-hero"
						type="submit"><?php esc_html_e( 'Save Changes', 'secured-wp' ); ?></button>
			</div>
			<?php
		}

		/**
		 * Adds settings page to the admin menu
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function settings_page() {
			// \add_options_page(
			// 	WPSEC_PLUGIN_SECURED_NAME . ' settings',
			// 	WPSEC_PLUGIN_SECURED_NAME,
			// 	'manage_options',
			// 	WPSEC_PLUGIN_SECURED_SLUG,
			// 	array(
			// 		'WPSEC\\Secured',
			// 		'render_plugin_settings_page',
			// 	)
			// );
			\add_menu_page(
				WPSEC_PLUGIN_SECURED_NAME . ' settings',
				WPSEC_PLUGIN_SECURED_NAME,
				'manage_options',
				// WPSEC_PLUGIN_SECURED_SLUG,
				self::MENU_SLUG,
				array( self::class, 'render' ),
				'data:image/svg+xml;base64,' . \base64_encode( \file_get_contents( WPSEC_PLUGIN_SECURED_PATH . 'assets/images/the-logo.svg' ) ),
				81
			);

			// \add_submenu_page(
			// WPSEC_PLUGIN_SECURED_SLUG,
			// esc_html__( '2FA Policies', 'secured-wp' ),
			// esc_html__( '2FA Policies', 'secured-wp' ),
			// 'manage_options',
			// self::MENU_SLUG,
			// array( self::class, 'render' ),
			// 1
			// );
		}

		/**
		 * Collects the passed options, validates them and stores them.
		 *
		 * @param array $post_array - The collected settings array.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function store_options( array $post_array ): array {
			if ( ! current_user_can( 'manage_options' ) ) {
				\wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'secured-wp' ) );
			}

			$secwp_options = array();

			$secwp_options = \apply_filters( 'wpsec_store_settings', $secwp_options, $post_array );

			// 2FA menu start.
			$secwp_options[ Two_FA_Settings::GLOBAL_SETTINGS_NAME ] = ( array_key_exists( Two_FA_Settings::GLOBAL_SETTINGS_NAME, $post_array ) ) ? true : false;
			if ( $secwp_options[ Two_FA_Settings::GLOBAL_SETTINGS_NAME ] ) {
				if ( array_key_exists( Two_FA_Settings::TOTP_SETTINGS_NAME, $post_array ) ) {
					$secwp_options[ Two_FA_Settings::TOTP_SETTINGS_NAME ] = (bool) $post_array[ Two_FA_Settings::TOTP_SETTINGS_NAME ];
				} else {
					$secwp_options[ Two_FA_Settings::TOTP_SETTINGS_NAME ] = false;
				}
			}
			if ( $secwp_options[ Two_FA_Settings::GLOBAL_SETTINGS_NAME ] ) {
				if ( array_key_exists( Two_FA_Settings::OOB_SETTINGS_NAME, $post_array ) ) {
					$secwp_options[ Two_FA_Settings::OOB_SETTINGS_NAME ] = (bool) $post_array[ Two_FA_Settings::OOB_SETTINGS_NAME ];
				} else {
					$secwp_options[ Two_FA_Settings::OOB_SETTINGS_NAME ] = false;
				}
			}
			// 2FA menu end.

			// XML RPC menu start.
			$secwp_options[ XML_RPC_Prevents::GLOBAL_SETTINGS_NAME ] = ( array_key_exists( XML_RPC_Prevents::GLOBAL_SETTINGS_NAME, $post_array ) ) ? true : false;
			if ( $secwp_options[ XML_RPC_Prevents::GLOBAL_SETTINGS_NAME ] ) {
				if ( array_key_exists( 'xmlrpc-disabled', $post_array ) ) {
					$secwp_options['xmlrpc-disabled'] = (bool) $post_array['xmlrpc-disabled'];
				} else {
					$secwp_options['xmlrpc-disabled'] = false;
				}
			}
			// XML RPC end.

			// Remember device start.
			$secwp_options[ Remember_Me::GLOBAL_SETTINGS_NAME ] = ( array_key_exists( Remember_Me::GLOBAL_SETTINGS_NAME, $post_array ) ) ? true : false;
			if ( $secwp_options[ Remember_Me::GLOBAL_SETTINGS_NAME ] && array_key_exists( Remember_Me::TIME_REMEMBER_NAME, $post_array ) ) {
				$secwp_options[ Remember_Me::TIME_REMEMBER_NAME ] = filter_var(
					$post_array[ Remember_Me::TIME_REMEMBER_NAME ],
					FILTER_VALIDATE_INT,
					array(
						'options' => array(
							'min_range' => 1,
							'max_range' => 180,
						),
					)
				);
				if ( false === $secwp_options[ Remember_Me::TIME_REMEMBER_NAME ] ) {
					unset( $secwp_options[ Remember_Me::TIME_REMEMBER_NAME ] );
				}
			}
			// Remember device end.

			self::$current_options = $secwp_options;

			return $secwp_options;
		}
	}
}