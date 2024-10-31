<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Mosules\Views;

use WPSEC\Helpers\TOTP_Helper;
use WPSEC\{
	Controllers\User,
	Controllers\Modules\Two_FA_Settings,
	Helpers\Out_Of_Band_Email
};

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Views\Modules\Login_Forms' ) ) {
	/**
	 * Class responsible for proper showing the login forms to the user based on the selected / available methods
	 *
	 * @since 1.0.0
	 */
	class Login_Forms {

		/**
		 * Holds the name of the login nonce
		 *
		 * @var string - login nonce name.
		 *
		 * @since 1.0.0
		 */
		private static $login_nonce_name = '_wpsec_totp_login';

		/**
		 * TOTP login form - shows the login form for TOTP method
		 *
		 * @since 1.0.0
		 *
		 * @param string $error - the error to show.
		 * @param mixed  $user - WP user.
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.ExitExpression)
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function login_totp( $error = '', $user = null ) {

			if ( 0 === User::get_user( $user )->ID ) {
				\auth_redirect();

				exit();
			}

			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? \esc_url_raw( \wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

			if ( class_exists( 'WooCommerce' ) && empty( $redirect_to ) ) {
				if ( ! empty( $_REQUEST['redirect'] ) ) {
					$redirect_to = \wp_unslash( $_REQUEST['redirect'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				} elseif ( \wc_get_raw_referer() ) {
					$redirect_to = \wc_get_raw_referer();
				} else {
					$redirect_to = \wc_get_page_permalink( 'myaccount' );
				}
			}

			if ( empty( $redirect_to ) ) {
				$redirect_to = \admin_url();
			}

			$interim_login = ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( \wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false;

			$remember_me = false;

			if ( ! empty( $_REQUEST['rememberme'] ) ) {
				$remember_me = true;
			}

			$remember_me = (bool) \apply_filters( 'wpsec_rememberme', $remember_me );
			if ( ! function_exists( 'login_header' ) ) {
				self::login_header();
			} else {
				\login_header();
			}

			if ( ! empty( $error ) ) {
				echo '<div id="login_error"><strong>' . \esc_html( $error ) . '</strong><br /></div>';
			}
			?>
		<form name="validate_2fa_form" id="loginform" action="<?php echo \esc_url( \add_query_arg( array( 'action' => 'validate_2fa' ) ) ); ?>" method="post" autocomplete="off">
				<input type="hidden" name="2fa-auth-id" id="2fa-auth-id" value="<?php echo \esc_attr( User::get_user()->ID ); ?>" />
			<?php if ( $interim_login ) { ?>
					<input type="hidden" name="interim-login" value="1" />
				<?php } else { ?>
					<input type="hidden" name="redirect_to" value="<?php echo \esc_attr( $redirect_to ); ?>" />
				<?php } ?>
				<input type="hidden" name="rememberme" id="rememberme" value="<?php echo \esc_attr( $remember_me ); ?>" />
			<?php \wp_nonce_field( self::$login_nonce_name, self::$login_nonce_name ); ?>
			<?php

			if ( ! User::is_totp_user_enabled() ) {
				echo \esc_html( \get_bloginfo( 'name' ) );
				?>
				<div style='width:100%;margin: 0 auto; text-align:center;'><img src='<?php echo \esc_attr( TOTP_Helper::generate_qrsvg_data() ); ?>' width="100%"></div>
				<?php
				echo \esc_html__( 'Scan above with your favorite Authenticator Application and enter the code below or add the following code:', 'secured-wp' );
				echo '<div><strong>' . \esc_html( User::get_user_totp() ) . '</strong></div>';
			} else {
				echo \esc_html__( 'Open the Authenticator Application on your phone and enter the code for ', 'secured-wp' ) .
				\get_bloginfo( 'name' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
			<div>
				<label for="authcode"><?php \esc_html_e( 'Authentication Code:', 'secured-wp' ); ?></label>
				<input type="tel" name="authcode" id="authcode" class="input" value="" size="20" pattern="[0-9\s]*" autocomplete="off" />
			</div>
			<?php
			// include the submit_button function.
			require_once ABSPATH . '/wp-admin/includes/template.php';
			\submit_button( __( 'Log In', 'secured-wp' ) );

			echo \esc_html( self::add_oob_link( User::get_user()->ID, $redirect_to ) );
			?>
		</form>
			<?php
			\do_action( 'login_footer' );
			?>
					<div class="clear"></div>
		</body>
		</html>
			<?php
		}

		/**
		 * OOB login form - shows the login form for OOB method
		 *
		 * @since 1.0.0
		 *
		 * @param string $error - the error to show.
		 * @param mixed  $user - WP user.
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.ExitExpression)
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function login_oob( $error = '', $user = null ) {

			if ( 0 === User::get_user( $user )->ID ) {
				\auth_redirect();

				exit();
			}

			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? \esc_url_raw( \wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

			if ( empty( $redirect_to ) ) {
				$redirect_to = \admin_url();
			}

			$remember_me = false;

			if ( ! empty( $_REQUEST['rememberme'] ) ) {
				$remember_me = true;
			}

			$remember_me = (bool) \apply_filters( 'wpsec_remember_me', $remember_me );
			if ( ! function_exists( 'login_header' ) ) {
				self::login_header();
			} else {
				\login_header();
			}

			if ( ! empty( $error ) ) {
				echo '<div id="login_error"><strong>' . \esc_html( $error ) . '</strong><br /></div>';
			}

			$wps_otp = ( isset( $_GET['wps_otp'] ) ) ? \esc_attr( \sanitize_text_field( \wp_unslash( $_GET['wps_otp'] ) ) ) : '';
			$user_id = ( isset( $_GET['user_id'] ) ) ? \esc_attr( \sanitize_text_field( \wp_unslash( $_GET['user_id'] ) ) ) : '';
			?>
		<form name="confirm_oob_form" id="loginform" action="<?php echo \esc_url( \wp_login_url() ); ?>" method="post" autocomplete="off">
				<input type="hidden" name="2fa-auth-id" id="2fa-auth-id" value="<?php echo \esc_attr( User::get_user()->ID ); ?>" />
				<input type="hidden" name="redirect_to" value="<?php echo \esc_attr( $redirect_to ); ?>" />
				<input type="hidden" name="rememberme" id="rememberme" value="<?php echo \esc_attr( $remember_me ); ?>" />
				<input type="hidden" name="action" id="action" value="confirm_oob" />
				<input type="hidden" name="wps_otp" id="wps_otp" value="<?php echo \esc_attr( $wps_otp ); ?>" />
				<input type="hidden" name="user_id" id="wps_otp" value="<?php echo \esc_attr( $user_id ); ?>" />
			<?php \wp_nonce_field( Out_Of_Band_Email::get_nonce_name_prefix() . $user_id, Out_Of_Band_Email::get_nonce_name() ); ?>
			<div>
				<p>
					<?php echo \esc_html__( 'Click the button to finish the login.', 'secured-wp' ); ?>
				</p>
			</div>
			<?php
			// include the submit_button function.
			require_once ABSPATH . '/wp-admin/includes/template.php';
			\submit_button( __( 'Log In', 'secured-wp' ) );
			?>
		</form>
			<?php
			\do_action( 'login_footer' );
			?>
			<div class="clear"></div>
		</body>
		</html>
			<?php
		}

		/**
		 * Shows interim login form
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.ExitExpressions)
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function interim_login() {

			$interim_login = ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( \wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false;

			if ( $interim_login ) {
				$message       = '<p class="message">' . __( 'You have logged in successfully.', 'secured-wp' ) . '</p>';
				$interim_login = 'success';
				\login_header( '', $message );
				/** This action is documented in wp-login.php */
				\do_action( 'login_footer' );

				exit();
			}
		}

		/**
		 * Returns the nonce login name
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_login_nonce_name() {
			return self::$login_nonce_name;
		}

		/**
		 * This is also a mirror of WP function (@see wp-login.php wp_login_viewport_meta), function - unfortunately just including the file wont work because of a call to wp-load which clears the user
		 *
		 * @since 1.0.0
		 *
		 * TODO: regular checks for that function changes are necessarily
		 *
		 * @return void
		 */
		public static function wp_login_viewport_meta() {
			?>
		<meta name="viewport" content="width=device-width" />
			<?php
		}

		/**
		 * Adds Out of Band link to the login form
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $user_id - the WP user.
		 * @param string $redirect_to - where to redirect.
		 *
		 * @return void
		 */
		private static function add_oob_link( $user_id, $redirect_to ) {
			if ( Two_FA_Settings::is_oob_enabled() ) {
				?>
			<div style="clear: both;" id="wsc-oob-wrapper">
				<a href="#" id="send-oob-mail"><?php echo \esc_html__( 'Send me out of band email instead', 'secured-wp' ); ?></a>
			</div>
			<script>
				var element = document.getElementById('send-oob-mail');
				element.addEventListener("click", function(e) {
					e.preventDefault();
					var request = new XMLHttpRequest();

					request.open('GET', '<?php echo \esc_url( \admin_url( 'admin-ajax.php' ) ); ?>?action=send_oob&userId=<?php echo \esc_attr( $user_id ); ?>&redirectTo=<?php echo \esc_attr( $redirect_to ); ?>', true);
					request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
					request.responseType = 'json';
					request.onload = function () {
						if (this.status >= 200 && this.status < 400) {
							document.getElementById('wsc-oob-wrapper').innerHTML = this.response['message'];
						} else {
							// If fail
							console.log(this.response);
						}
					};
					request.onerror = function() {
						console.log('Connection error');
					};
					request.send();
				}, false);
			</script>
				<?php
			} else {
				return;
			}
		}

		/**
		 * This method is mirror of the WP login_header (@see wp-login.php), function - unfortunately just including the file wont work because of a call to wp-load which clears the user
		 *
		 * @since 1.0.0
		 *
		 * TODO: regular checks for that function changes are necessarily
		 *
		 * @param string $title - form title.
		 * @param string $message - message to show.
		 * @param mixed  $wp_error - error to show.
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
		 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
		 */
		private static function login_header( $title = 'Log In', $message = '', $wp_error = null ) {

			global $error, $interim_login, $action, $wp_version;

			global $wp_version;

			if ( version_compare( $wp_version, '5.7' ) >= 0 ) {
				// Don't index any of these forms.
				\add_filter( 'wp_robots', 'wp_robots_sensitive_page' );
				\add_action( 'login_head', 'wp_strict_cross_origin_referrer' );
			} else {
				\add_action( 'login_head', 'wp_no_robots' );
				\add_action( 'login_head', array( __CLASS__, 'wpLoginViewportMeta' ) );
			}

			if ( ! \is_wp_error( $wp_error ) ) {
				$wp_error = new \WP_Error();
			}

			// Shake it!
			$shake_error_codes = array( 'empty_password', 'empty_email', 'invalid_email', 'invalidcombo', 'empty_username', 'invalid_username', 'incorrect_password', 'retrieve_password_email_failure' );
			/**
			 * Filters the error codes array for shaking the login form.
			 *
			 * @since 3.0.0
			 *
			 * @param array $shake_error_codes Error codes that shake the login form.
			 */
			$shake_error_codes = \apply_filters( 'shake_error_codes', $shake_error_codes );

			if ( $shake_error_codes && $wp_error->has_errors() && in_array( $wp_error->get_error_code(), $shake_error_codes, true ) ) {
				\add_action( 'login_footer', 'wp_shake_js', 12 );
			}

			$login_title = \get_bloginfo( 'name', 'display' );

			/* translators: Login screen title. 1: Login screen name, 2: Network or site name. */
			$login_title = sprintf( __( '%1$s &lsaquo; %2$s &#8212; WordPress' ), $title, $login_title );

			if ( \wp_is_recovery_mode() ) {
				/* translators: %s: Login screen title. */
				$login_title = sprintf( __( 'Recovery Mode &#8212; %s' ), $login_title );
			}

			/**
			 * Filters the title tag content for login page.
			 *
			 * @since 4.9.0
			 *
			 * @param string $login_title The page title, with extra context added.
			 * @param string $title       The original page title.
			 */
			$login_title = apply_filters( 'login_title', $login_title, $title );

			?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>" />
	<title><?php echo \esc_html( $login_title ); ?></title>
			<?php

			wp_enqueue_style( 'login' );

			/*
			 * Remove all stored post data on logging out.
			 * This could be added by add_action('login_head'...) like wp_shake_js(),
			 * but maybe better if it's not removable by plugins.
			 */
			if ( 'loggedout' === $wp_error->get_error_code() ) {
				?>
		<script>if("sessionStorage" in window){try{for(var key in sessionStorage){if(key.indexOf("wp-autosave-")!=-1){sessionStorage.removeItem(key)}}}catch(e){}};</script>
				<?php
			}

			/**
			 * Enqueue scripts and styles for the login page.
			 *
			 * @since 3.1.0
			 */
			do_action( 'login_enqueue_scripts' );

			/**
			 * Fires in the login page header after scripts are enqueued.
			 *
			 * @since 2.1.0
			 */
			do_action( 'login_head' );

			$login_header_url = __( 'https://wordpress.org/' );

			/**
			 * Filters link URL of the header logo above login form.
			 *
			 * @since 2.1.0
			 *
			 * @param string $login_header_url Login header logo URL.
			 */
			$login_header_url = apply_filters( 'login_headerurl', $login_header_url );

			$login_header_title = '';

			/**
			 * Filters the title attribute of the header logo above login form.
			 *
			 * @since 2.1.0
			 * @deprecated 5.2.0 Use {@see 'login_headertext'} instead.
			 *
			 * @param string $login_header_title Login header logo title attribute.
			 */
			$login_header_title = apply_filters_deprecated(
				'login_headertitle',
				array( $login_header_title ),
				'5.2.0',
				'login_headertext',
				__( 'Usage of the title attribute on the login logo is not recommended for accessibility reasons. Use the link text instead.' )
			);

			$login_header_text = empty( $login_header_title ) ? __( 'Powered by WordPress' ) : $login_header_title;

			/**
			 * Filters the link text of the header logo above the login form.
			 *
			 * @since 5.2.0
			 *
			 * @param string $login_header_text The login header logo link text.
			 */
			$login_header_text = apply_filters( 'login_headertext', $login_header_text );

			$classes = array( 'login-action-' . $action, 'wp-core-ui' );

			if ( is_rtl() ) {
				$classes[] = 'rtl';
			}

			if ( $interim_login ) {
				$classes[] = 'interim-login';

				?>
		<style type="text/css">html{background-color: transparent;}</style>
				<?php

				if ( 'success' === $interim_login ) {
					$classes[] = 'interim-login-success';
				}
			}

			$classes[] = ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );

			/**
			 * Filters the login page body classes.
			 *
			 * @since 3.5.0
			 *
			 * @param array  $classes An array of body classes.
			 * @param string $action  The action that brought the visitor to the login page.
			 */
			$classes = apply_filters( 'login_body_class', $classes, $action );

			?>
	</head>
	<body class="login no-js <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<script type="text/javascript">
		document.body.className = document.body.className.replace('no-js','js');
	</script>
			<?php
			/**
			 * Fires in the login page header after the body tag is opened.
			 *
			 * @since 4.6.0
			 */
			do_action( 'login_header' );

			?>
	<div id="login">
		<h1><a href="<?php echo esc_url( $login_header_url ); ?>"><?php echo \esc_html( $login_header_text ); ?></a></h1>
				<?php
				/**
				 * Filters the message to display above the login form.
				 *
				 * @since 2.1.0
				 *
				 * @param string $message Login message text.
				 */
				$message = apply_filters( 'login_message', $message );

				if ( ! empty( $message ) ) {
					echo \esc_html( $message ) . "\n";
				}

				// In case a plugin uses $error rather than the $wp_errors object.
				if ( ! empty( $error ) ) {
					$wp_error->add( 'error', $error );
					unset( $error );
				}

				if ( $wp_error->has_errors() ) {
					$errors   = '';
					$messages = '';

					foreach ( $wp_error->get_error_codes() as $code ) {
						$severity = $wp_error->get_error_data( $code );
						foreach ( $wp_error->get_error_messages( $code ) as $error_message ) {
							if ( 'message' === $severity ) {
								$messages .= '	' . $error_message . "<br />\n";
							} else {
								$errors .= '	' . $error_message . "<br />\n";
							}
						}
					}

					if ( ! empty( $errors ) ) {
						/**
						 * Filters the error messages displayed above the login form.
						 *
						 * @since 2.1.0
						 *
						 * @param string $errors Login error message.
						 */
						echo '<div id="login_error">' . \esc_html( apply_filters( 'login_errors', $errors ) ) . "</div>\n";
					}

					if ( ! empty( $messages ) ) {
						/**
						 * Filters instructional messages displayed above the login form.
						 *
						 * @since 2.5.0
						 *
						 * @param string $messages Login messages.
						 */
						echo '<p class="message">' . \esc_html( apply_filters( 'login_messages', $messages ) ) . "</p>\n";
					}
				}
		} // End of login_header().
	}
}
