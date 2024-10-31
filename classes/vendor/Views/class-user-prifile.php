<?php
/**
 * Plugin WPS security
 *
 * @package   WPS
 * @author    wp-security.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Views;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use WPSEC\Controllers\{
	User,
	Modules\Remember_Me,
};
use WPSEC\Helpers\TOTP_Helper;

if ( ! class_exists( 'WPSEC\Views\User_Profile' ) ) {

	/**
	 * Responsible for users profile page
	 *
	 * @since 1.0.0
	 */
	class User_Profile {

		/**
		 * QR code regeneration nonce prefix
		 *
		 * @var string
		 *
		 * @since 1.2
		 */
		private static $qr_nonce_prefix = '_regenerate-qr-code_';

		/**
		 * Shows user profile section
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $user - the WP user.
		 * @param array $additional_args - Additional args - not in use currently.
		 *
		 * @return void
		 */
		public static function user_edit_profile( $user, $additional_args = array() ) {

			$devices = User::get_logged_in_devices( $user );
			?>
				<h2><?php echo \esc_html__( 'Secured WP - Options', 'secured-wp' ); ?></h2>
				<h3><?php echo \esc_html__( 'Current devices with "Remember Me" option enabled', 'secured-wp' ); ?></h3>

				<style>
					.wps-table thead th, .wps-table tfoot th {
						background-color: black;
						color: white;
						text-transform: uppercase;
						padding:3px;
					}
					.wps-table td {
						padding:3px;
					}
					.wps-table tr:nth-of-type(odd) {
						background: #EEEEEE;
					}

					.wps-button {
						background: #191B1E;
						border-color: #000;
						color: #fff;
						text-decoration: none;
						text-shadow: none;
						font-size: 13px;
						line-height: 2.15384615;
						min-height: 30px;
						margin: 0;
						padding: 0 10px;
						cursor: pointer;
						border-width: 1px;
						border-style: solid;
						-webkit-appearance: none;
						border-radius: 3px;
						white-space: nowrap;
						box-sizing: border-box;
					}
				</style>

				<table id="wp-secured-logged-devices" class="wp-secured-logged-devices widefat striped table-view-list wps-table">
					<thead>
					<tr>
						<th scope="col" class="wp-secured-device manage-column column-device column-primary">
						<?php echo \esc_html__( 'Device', 'secured-wp' ); ?>
						</th>
						<th scope="col" class="wp-secured-device manage-column column-device column-primary">
						<?php echo \esc_html__( 'Expires', 'secured-wp' ); ?>
						</th>
						<th scope="col" class="wp-secured-remove manage-column column-remove">
						<?php echo \esc_html__( 'Remove', 'secured-wp' ); ?>
						</th> 
					</tr>
					</thead>

					<tbody id="the-list">
				<?php
				if ( empty( $devices ) ) {
					?>
						<tr class="no-items">
							<td class="colspanchange" colspan="3"><?php echo \esc_html__( 'No logged in devices found', 'secured-wp' ); ?></td>
						</tr>
						<?php
				} else {
					foreach ( $devices as $device ) {
						?>
						<tr class="wp-secured-device-row">
							<td>
							<?php echo \esc_html( $device ); ?>
							</td>
							<td>
							<?php
							$exp_time = Remember_Me::get_expire_time( $device, $user->ID );
							if ( $exp_time < time() ) {
								echo \esc_html__( 'Expired', 'secured-wp' );
							} else {
								echo \esc_html( \date_i18n( \get_option( 'date_format' ) . ', ' . \get_option( 'time_format' ), Remember_Me::get_expire_time( $device, $user->ID ) ) );
							}
							?>
							</td>
							<td>
								<input type="submit" name="remove-device-password-<?php echo md5( $device ); ?>" id="remove-device-password-<?php echo md5( $device ); ?>" class="button delete wps-button" value="<?php echo \esc_html__( 'Remove', 'secured-wp' ); ?>" data-device="<?php echo \base64_encode( $device ); ?>">

							</td>
						</tr>
							<?php
					}
				}
				?>
					</tbody>

					<tfoot>
					<tr>
						<th scope="col" class="wp-secured-device manage-column column-device column-primary">
						<?php echo \esc_html__( 'Device', 'secured-wp' ); ?>
						</th>
						<th scope="col" class="wp-secured-device manage-column column-device column-primary">
						<?php echo \esc_html__( 'Expires', 'secured-wp' ); ?>
						</th>
						<th scope="col" class="wp-secured-remove manage-column column-remove">
						<?php echo \esc_html__( 'Remove', 'secured-wp' ); ?>
						</th> 
					</tr>
					</tfoot>

				</table>
				<div class="tablenav" style="overflow:hidden;">
					<div class="alignright">
					
					<input type="submit" name="remove-all-logged-devices" id="remove-all-logged-devices" class="button delete wps-button" value="<?php echo \esc_html__( 'Remove all logged in devices', 'secured-wp' ); ?>">
						
					</div>
				</div>
				<hr style="clear:both; margin-top:5px;">
				<?php
				if ( \current_user_can( 'edit_user', User::get_user()->ID ) && ! (bool) User::is_two_fa_user_excluded() ) {
					self::edit_user_qr();
				}
				?>
				<?php
				self::delete_device_js();
				self::delete_all_device_js();
		}

		/**
		 * Shows the QR code for the given user in the setting
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function edit_user_qr() {
			if ( (bool) \WPSEC\Controllers\Modules\Two_FA_Settings::get_global_settings_value() ) {
				?>
			<h3><?php echo \esc_html__( 'QR code for the TOTP 2FA login', 'secured-wp' ); ?></h3>

			<div style='width:100%;margin: 0 auto; text-align:center;'><img src='<?php echo \esc_attr( TOTP_Helper::generate_qrsvg_data() ); ?>'></div>
			<div><?php echo \esc_html__( 'Or use the following key, by entering it directly in you preferable authentication application:', 'secured-wp' ); ?></div>
			<div><strong><?php echo \esc_html( User::get_user_totp() ); ?></strong></div>
			<div class="tablenav" style="overflow:hidden;">
				<div class="alignleft">
				<?php
				$nonce = \wp_create_nonce( self::$qr_nonce_prefix . User::get_user()->ID );

				?>
				<input type="hidden" name="qr-nonce" value="<?php echo \esc_attr( $nonce ); ?>" />

				<input type="submit" name="regenerate-qr-code" id="regenerate-qr-code" class="button delete wps-button" value="<?php echo \esc_html__( 'Regenerate QR code', 'secured-wp' ); ?>">
					<?php
					self::delete_qr_code_js();

					?>
				</div>
			</div>
			<hr style="clear:both; margin-top:5px;">
				<?php
			}
		}

		/**
		 * Deletes the TOTP code for the user, it will be regenerated next time the user logs / see its profile page
		 *
		 * @param int $user_id - the user which TOTP must be regenerated.
		 *
		 * @return void
		 *
		 * @since 1.3
		 */
		public static function regenerate_qr_code( $user_id ) {
			if ( \current_user_can( 'edit_user', $user_id ) ) {
				if ( isset( $_POST['qr-nonce'] ) &&
				\wp_verify_nonce( \sanitize_text_field( \wp_unslash( $_POST['qr-nonce'] ) ), self::$qr_nonce_prefix . $user_id ) &&
				isset( $_POST['regenerate-qr-code'] ) ) {

					User::delete_user_totp( $user_id );
				}
			}
		}

		/**
		 * Javascript for single logged in device deletion
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function delete_device_js() {
			?>
		<script>
			window.addEventListener('load', function () {
				const elems = document.querySelectorAll('.button.delete')

				elems.forEach(element => element.addEventListener('click', e => {
					e.preventDefault();
					var request = new XMLHttpRequest();

					request.open('POST', '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>', true);
					request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
					request.responseType = 'json';
					request.onload = function () {
						if (this.status >= 200 && this.status < 400) {
							location.reload();
						} else {
							// If fail
							console.log(this.response);
						}
					};
					request.onerror = function() {
						console.log('Connection error');
					};

					var deleteData = {
						'action': 'logged_device_delete',
						'user': '<?php echo \esc_attr( User::get_user()->ID ); ?>',
						'nonce': '<?php echo \esc_attr( \wp_create_nonce( 'wp-secured-delete_device-ajax-nonce' ) ); ?>',
						'device': e.target.getAttribute('data-device')
					};

					request.send(
						(new URLSearchParams(deleteData)).toString()
					);

				}));
			});

		</script>
			<?php
		}

		/**
		 * Javascript for all logged in device deletion
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function delete_all_device_js() {
			?>
		<script>
			window.addEventListener('load', function () {
				var qrRegenerate = document.getElementById('remove-all-logged-devices');

				if (!!qrRegenerate) {
					qrRegenerate.addEventListener("click", function(e) {
						e.preventDefault();
						var request = new XMLHttpRequest();

						request.open('POST', '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>', true);
						request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
						request.responseType = 'json';
						request.onload = function () {
							if (this.status >= 200 && this.status < 400) {
								location.reload();
							} else {
								// If fail
								console.log(this.response);
							}
						};
						request.onerror = function() {
							console.log('Connection error');
						};

						var deleteData = {
							'action': 'all_logged_device_delete',
							'user': '<?php echo \esc_attr( User::get_user()->ID ); ?>',
							'nonce': '<?php echo \esc_attr( \wp_create_nonce( 'wp-secured-delete_all_device-ajax-nonce' ) ); ?>',
						};

						request.send(
							(new URLSearchParams(deleteData)).toString()
						);
					}, false);
				}
			});
		</script>
			<?php
		}

		/**
		 * Javascript for qr code deletion
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function delete_qr_code_js() {
			?>
		<script>
			window.addEventListener('load', function () {
				var qrRegenerate = document.getElementById('regenerate-qr-code');

				if (!!qrRegenerate) {
					qrRegenerate.addEventListener("click", function(e) {
						e.preventDefault();
						var request = new XMLHttpRequest();

						request.open('POST', '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>', true);
						request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
						request.responseType = 'json';
						request.onload = function () {
							if (this.status >= 200 && this.status < 400) {
								location.reload();
							} else {
								// If fail
								console.log(this.response);
							}
						};
						request.onerror = function() {
							console.log('Connection error');
						};

						var deleteData = {
							"action": "wps_delete_qr",
							"user": "<?php echo \esc_attr( User::get_user()->ID ); ?>",
							"nonce": "<?php echo \esc_attr( \wp_create_nonce( 'wp-secured-wps_delete_qr-ajax-nonce' ) ); ?>",
						};

						request.send(
							(new URLSearchParams(deleteData)).toString()
						);
					}, false);
				}
			});
		</script>
			<?php
		}
	}
}
