<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 */

declare(strict_types=1);

namespace WPSEC\Controllers\Modules;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

use WPSEC\Controllers\User;
use WPSEC\Helpers\WP_Helper;
use WPSEC\Controllers\Settings;

if ( ! class_exists( 'WPSEC\Controllers\Modules\Login' ) ) {
	/**
	 * Masks original WP login
	 * Redirects to new login slug
	 *
	 * @since 1.0.0
	 */
	class Login extends Base_Module {

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const GLOBAL_SETTINGS_NAME = 'login_redirection_menu';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const NEW_LOGIN_SLUG_SETTINGS_NAME = 'new_login_redirect';

		/**
		 * Default login slug
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const NEW_LOGIN_SLUG_DEFAULT_VALUE = 'login';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		public const OLD_LOGIN_SLUG_SETTINGS_NAME = 'org_login_redirect';

		/**
		 * Global setting name - stored the global value for enable / disable module
		 *
		 * @var string
		 */
		protected static $global_setting_name = null;

		/**
		 * Holds the global new login slug setting
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		private static $new_login_slug = null;

		/**
		 * Holds the global old login slug setting to redirect to
		 *
		 * @var string
		 *
		 * @since 2.0.0
		 */
		private static $old_login_slug = null;

		/**
		 * Is the module enabled or not
		 *
		 * @var bool
		 *
		 * @since 1.0.0
		 */
		protected static $module_enabled;

		/**
		 * Holds the name of the give module
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		protected static $module_name;

		/**
		 * Flag for user request - tries to login?
		 *
		 * @since 1.0.0
		 *
		 * @var boolean
		 */
		private static $wp_login = false;
		/**
		 * Inits the class and sets the hooks
		 *
		 * @return void
		 *
		 * @since 2.0.0
		 */
		public static function settings_init() {

			if ( \current_user_can( 'manage_options' ) ) {
				\add_filter( 'wpsec_store_settings', array( __CLASS__, 'store_settings' ), 2, 2 );
			}
		}

		/**
		 * Stores the module settings.
		 *
		 * @param array $settings - Array with the currently collected settings.
		 * @param array $post_array - The array with settings to check against.
		 *
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public static function store_settings( array $settings, array $post_array ): array {

			// Login menu start.
			$settings[ self::GLOBAL_SETTINGS_NAME ] = ( array_key_exists( self::GLOBAL_SETTINGS_NAME, $post_array ) ) ? true : false;
			if ( $settings[ self::GLOBAL_SETTINGS_NAME ] && array_key_exists( self::NEW_LOGIN_SLUG_SETTINGS_NAME, $post_array ) ) {
				$settings[ self::NEW_LOGIN_SLUG_SETTINGS_NAME ] = \sanitize_title( $post_array[ self::NEW_LOGIN_SLUG_SETTINGS_NAME ] );
			}
			if ( $settings[ self::GLOBAL_SETTINGS_NAME ] && array_key_exists( self::OLD_LOGIN_SLUG_SETTINGS_NAME, $post_array ) ) {
				$settings[ self::OLD_LOGIN_SLUG_SETTINGS_NAME ] = \sanitize_title( $post_array[ self::OLD_LOGIN_SLUG_SETTINGS_NAME ] );
			}

			return $settings;
		}

		/**
		 * Init all the hooks related to login redirection
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function init() {

			if ( WP_Helper::is_multisite() && ! function_exists( 'is_plugin_active_for_network' ) || ! function_exists( 'is_plugin_active' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			if ( WP_Helper::is_multisite() ) {
				\add_action( 'wp_before_admin_bar_render', array( __CLASS__, 'modify_mysites_menu' ), 999 );
			}

			\add_action( 'plugins_loaded', array( __CLASS__, 'plugins_loaded' ), 9999 );
			\add_action( 'wp_loaded', array( __CLASS__, 'wp_loaded' ) );
			\add_action( 'wp_redirect', array( __CLASS__, 'wp_redirect' ), 10, 2 );
			\add_action( 'setup_theme', array( __CLASS__, 'setup_theme' ), 1 );

			\add_filter( 'site_url', array( __CLASS__, 'site_url' ), 10, 4 );
			\add_filter( 'network_site_url', array( __CLASS__, 'network_site_url' ), 10, 3 );
			\add_filter( 'site_option_welcome_email', array( __CLASS__, 'welcome_email' ) );

			\remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
			\add_action( 'template_redirect', array( __CLASS__, 'redirect_export_data' ) );
			\add_filter( 'login_url', array( __CLASS__, 'login_url' ), 10, 3 );

			\add_filter( 'user_request_action_email_content', array( __CLASS__, 'user_request_action_email_content' ), 999, 2 );

			\add_filter( 'site_status_tests', array( __CLASS__, 'site_status_tests' ) );
		}

		/**
		 * Removes loopback requests tests
		 *
		 * @since 1.0.0
		 *
		 * @param array $tests - status test array.
		 *
		 * @return array
		 */
		public static function site_status_tests( array $tests ): array {
			unset( $tests['async']['loopback_requests'] );

			return $tests;
		}

		/**
		 * Filters the text of the email sent to user
		 *
		 * @since 1.0.0
		 *
		 * @param string $email_text - text for the email.
		 * @param array  $email_data - email data vars.
		 *
		 * @see user_request_action_email_content filter
		 *
		 * @return string
		 */
		public static function user_request_action_email_content( $email_text, $email_data ): string {
			$email_text = str_replace( '###CONFIRM_URL###', \esc_url_raw( str_replace( self::new_login_slug() . '/', 'wp-login.php', $email_data['confirm_url'] ) ), $email_text );

			return $email_text;
		}

		/**
		 * Should trailing slashes be used or not
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		private static function use_trailing_slashes(): bool {

			return ( '/' === substr( WP_Helper::get_option( 'permalink_structure', '' ), - 1, 1 ) );
		}

		/**
		 * Adds trailing slash if necessary
		 *
		 * @since 1.0.0
		 *
		 * @param string $string_to_add - adds trailing slash based on the selection.
		 *
		 * @return string
		 */
		private static function use_trailingslashit( string $string_to_add ): string {

			return self::use_trailing_slashes() ? \trailingslashit( $string_to_add ) : \untrailingslashit( $string_to_add );
		}

		/**
		 * Loads the WP template
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private static function wp_template_loader() {
			global $pagenow;

			$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			if ( ! defined( 'WP_USE_THEMES' ) ) {

				define( 'WP_USE_THEMES', true );

			}

			\wp();

			require_once ABSPATH . WPINC . '/template-loader.php';

			\wp_die();
		}

		/**
		 * Modifies Multisite My Sites menu login links
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 *
		 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
		 */
		public static function modify_mysites_menu() {
			global $wp_admin_bar;

			$all_toolbar_nodes = $wp_admin_bar->get_nodes();

			foreach ( $all_toolbar_nodes as $node ) {
				if ( preg_match( '/^blog-(\d+)(.*)/', $node->id, $matches ) ) {
					$blog_id = $matches[1];
					if ( $login_slug = self::new_login_slug( $blog_id ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
						if ( ! $matches[2] || '-d' === $matches[2] ) {
							$args       = $node;
							$old_href   = $args->href;
							$args->href = preg_replace( '/wp-admin\/$/', "$login_slug/", $old_href );
							if ( $old_href !== $args->href ) {
								$wp_admin_bar->add_node( $args );
							}
						} elseif ( strpos( $node->href, '/wp-admin/' ) !== false ) {
							$wp_admin_bar->remove_node( $node->id );
						}
					}
				}
			}
		}

		/**
		 * Extracts new login slug and returns it
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - WP blog id.
		 *
		 * @return string
		 */
		public static function new_login_slug( $blog_id = '' ): string {
			if ( null === self::$new_login_slug ) {
				if ( isset( Settings::get_current_options()[ self::NEW_LOGIN_SLUG_SETTINGS_NAME ] ) ) {
					self::$new_login_slug = Settings::get_current_options()[ self::NEW_LOGIN_SLUG_SETTINGS_NAME ];
				} else {
					self::$new_login_slug = self::NEW_LOGIN_SLUG_DEFAULT_VALUE;
				}
			}

			return self::$new_login_slug;
		}

		/**
		 * Returns slug if someone tries to access the default wp-login.php
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $blog_id - WP blog id.
		 *
		 * @return string
		 */
		public static function new_redirect_slug( $blog_id = '' ): string {
			if ( null === self::$old_login_slug ) {
				self::$old_login_slug = Settings::get_current_options()[ self::OLD_LOGIN_SLUG_SETTINGS_NAME ];
			}

			return self::$old_login_slug;
		}

		/**
		 * Returns new login url
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $scheme - the URL scheme.
		 *
		 * @return string
		 */
		public static function new_login_url( $scheme = null ): string {

			$url = \home_url( '/', $scheme );

			if ( \get_option( 'permalink_structure' ) ) {
				return self::use_trailingslashit( $url . self::new_login_slug() );
			} else {
				return $url . '?' . self::new_login_slug();
			}
		}

		/**
		 * Builds URL to redirect to newly selected login location
		 *
		 * @since 1.0.0
		 *
		 * @param string $scheme - the URL scheme.
		 *
		 * @return string
		 */
		public static function new_redirect_url( $scheme = null ) {

			if ( \get_option( 'permalink_structure' ) ) {
				return self::use_trailingslashit( home_url( '/', $scheme ) . self::new_redirect_slug() );
			} else {
				return \home_url( '/', $scheme ) . '?' . self::new_redirect_slug();
			}
		}

		/**
		 * Fires on template redirect - @see 'template_redirect'
		 *
		 * @hook 'template_redirect'
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 *
		 * @SuppressWarnings(PHPMD.ExitExpression)
		 * @SuppressWarnings(PHPMD.Superglobals)
		 */
		public static function redirect_export_data() {
			if ( ! empty( $_GET ) && isset( $_GET['action'] ) && 'confirmaction' === $_GET['action'] && isset( $_GET['request_id'] ) && isset( $_GET['confirm_key'] ) ) {
				$request_id = (int) $_GET['request_id'];
				$key        = \sanitize_text_field( \wp_unslash( $_GET['confirm_key'] ) );
				$result     = \wp_validate_user_request_key( $request_id, $key );
				if ( ! \is_wp_error( $result ) ) {
					\wp_safe_redirect(
						\add_query_arg(
							array(
								'action'      => 'confirmaction',
								'request_id'  => $request_id,
								'confirm_key' => $key,
							),
							self::new_login_url()
						)
					);
					exit();
				}
			}
		}

		/**
		 * Fires right after all the plugins are loaded
		 * checks the current URL and sets the flag if
		 * redirection is needed later on
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			global $pagenow;

			if ( ! WP_Helper::is_multisite()
				&& ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-signup' ) !== false
				|| strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-activate' ) !== false )
				&& \apply_filters( 'wpsec_hide_login_signup_enable', false ) === false ) {

				\wp_die( \esc_html__( 'This feature is not enabled.', 'secured-wp' ) );
			}

			$request = ( ( isset( $_SERVER['REQUEST_URI'] ) ) ? parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) ) : '' );

			if ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-login.php' ) !== false
				|| ( isset( $request['path'] )
				&& \untrailingslashit( $request['path'] ) === \site_url( 'wp-login', 'relative' ) ) )
				&& ! \is_admin() ) {

				if ( 'checkemail=registered' === $request['query'] ) {
					$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					self::set_wp_login( true );

					$_SERVER['REQUEST_URI'] = self::use_trailingslashit( '/' . str_repeat( '-/', 10 ) );

					$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}
			} elseif ( ( isset( $request['path'] ) && \untrailingslashit( $request['path'] ) === \home_url( self::new_login_slug(), 'relative' ) )
				|| ( ! \get_option( 'permalink_structure' )
				&& isset( $_GET[ self::new_login_slug() ] )
				&& empty( $_GET[ self::new_login_slug() ] ) ) ) {

				$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

			} elseif ( ( strpos( rawurldecode( $_SERVER['REQUEST_URI'] ), 'wp-register.php' ) !== false
				|| ( isset( $request['path'] )
				&& \untrailingslashit( $request['path'] ) === \site_url( 'wp-register', 'relative' ) ) )
				&& ! \is_admin() ) {

				self::set_wp_login( true );

				$_SERVER['REQUEST_URI'] = self::use_trailingslashit( '/' . str_repeat( '-/', 10 ) );

				$pagenow = 'index.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		/**
		 * IF the user is not logged and tries to customize
		 * the theme - returns error
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function setup_theme() {
			global $pagenow;

			if ( ! User::is_currently_logged() && 'customize.php' === $pagenow ) {
				\wp_die( \esc_html__( 'Nothing to see here', 'secured-wp' ), 403 );
			}
		}

		/**
		 * Triggers right after the WP is loaded and ready
		 * Checks current page and if the user is logged in
		 * If the call is from the new login url and flag is set
		 * makes redirection
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public static function wp_loaded() {
			global $pagenow;

			$request = parse_url( rawurldecode( $_SERVER['REQUEST_URI'] ) );

			if ( ! ( isset( $_GET['action'] ) && 'postpass' === $_GET['action'] && isset( $_POST['post_password'] ) ) ) {

				if ( ( self::get_wp_login() && ! User::is_currently_logged() ) || ( \is_admin() && ! User::is_currently_logged() && ! defined( 'DOING_AJAX' ) && 'admin-post.php' !== $pagenow && '/wp-admin/options.php' !== $request['path'] ) ) {
					\wp_safe_redirect( self::new_redirect_url() );
					die();
				}

				if ( 'wp-login.php' === $pagenow
				&& self::use_trailingslashit( $request['path'] ) !== $request['path']
				&& \get_option( 'permalink_structure' ) ) {

					\wp_safe_redirect(
						self::use_trailingslashit( self::new_login_url() )
						. ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . \wp_unslash( $_SERVER['QUERY_STRING'] ) : '' )
					);

					die();

				} elseif ( self::get_wp_login() ) {

					if ( ( $referer = \wp_get_referer() ) //phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					&& strpos( $referer, 'wp-activate.php' ) !== false
					&& ( $referer = parse_url( $referer ) ) //phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
					&& ! empty( $referer['query'] ) ) {

						parse_str( $referer['query'], $referer );

						@require_once \WPINC . '/ms-functions.php';

						if ( ! empty( $referer['key'] )
						&& ( $result = \wpmu_activate_signup( $referer['key'] ) ) //phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.Found, Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure
						&& \is_wp_error( $result )
						&& ( $result->get_error_code() === 'already_active'
						|| $result->get_error_code() === 'blog_taken' ) ) {

							\wp_safe_redirect(
								self::new_login_url()
								. ( ! empty( $_SERVER['QUERY_STRING'] ) ? '?' . \wp_unslash( $_SERVER['QUERY_STRING'] ) : '' )
							);

							die();
						}
					}

					self::wp_template_loader();

				} elseif ( 'wp-login.php' === $pagenow ) {
					global $error, $interim_login, $action, $user_login;

					$redirect_to = \admin_url();

					if ( User::is_currently_logged() ) {
						if ( ! isset( $_REQUEST['action'] ) ) {
							$logged_in_redirect = $redirect_to;
							\wp_safe_redirect( $logged_in_redirect );

							die();
						}
					}

					@require_once ABSPATH . 'wp-login.php';

					die();
				}
			}
		}

		/**
		 * Checks the site url and changes it if necessary
		 *
		 * @since 1.0.0
		 *
		 * @param string $url - the url of the site.
		 * @param string $path - currently not in use.
		 * @param string $scheme - the URL scheme.
		 * @param string $blog_id - WP blog id.
		 *
		 * @return string
		 */
		public static function site_url( $url, $path, $scheme, $blog_id ) {

			return self::filter_wp_login_php( $url, $scheme );
		}

		/**
		 * Checks the network site url and changes it if necessary
		 *
		 * @since 1.0.0
		 *
		 * @param string $url - the url of the site.
		 * @param string $path - currently not in use.
		 * @param string $scheme - the URL scheme.
		 *
		 * @return string
		 */
		public static function network_site_url( $url, $path, $scheme ) {

			return self::filter_wp_login_php( $url, $scheme );
		}

		/**
		 * Called on wp_redirect action. Checks the URL string, and filters it accordingly if wp-login.php is presented (substitute it with the new slug)
		 *
		 * @param string  $url - The URL to which WP is trying to redirect to.
		 * @param integer $status - Number code of the status.
		 *
		 * @return string
		 *
		 * @since 1.6
		 */
		public static function wp_redirect( string $url, int $status ): string {

			if ( strpos( $url, 'https://wordpress.com/wp-login.php' ) !== false ) {
				return $url;
			}

			return self::filter_wp_login_php( $url );
		}

		/**
		 * Changes the login link in the welcome register email to user
		 *
		 * @since 1.0.0
		 *
		 * @param string $value - with what to replace the original login URL.
		 *
		 * @return string
		 */
		public static function welcome_email( $value ): string {

			return str_replace(
				'wp-login.php',
				\trailingslashit(
					self::new_login_slug()
				),
				$value
			);
		}

		/**
		 * Update url redirect : wp-admin/options.php
		 *
		 * @since 1.0.0
		 *
		 * @param mixed $login_url - the login url of the site.
		 * @param mixed $redirect - to where to redirect.
		 * @param mixed $force_reauth - should it be enforced?.
		 *
		 * @return string
		 */
		public static function login_url( $login_url, $redirect, $force_reauth ) {
			if ( \is_404() ) {
				return '#';
			}

			if ( false === $force_reauth ) {
				return $login_url;
			}

			if ( empty( $redirect ) ) {
				return $login_url;
			}

			$redirect = explode( '?', $redirect );

			if ( \admin_url( 'options.php' ) === $redirect[0] ) {
				$login_url = \admin_url();
			}

			return $login_url;
		}

		/**
		 * Checks the current url and changes it if necessary
		 *
		 * @since 1.0.0
		 *
		 * @param string $url - the url of the site.
		 * @param mixed  $scheme - the URL scheme.
		 *
		 * @return string
		 */
		private static function filter_wp_login_php( $url, $scheme = null ): string {

			if ( false !== strpos( $url, 'wp-login.php?action=postpass' ) ) {
				return $url;
			}

			if ( false !== strpos( $url, 'wp-login.php' ) && false === strpos( (string) \wp_get_referer(), 'wp-login.php' ) ) {

				if ( \is_ssl() ) {

					$scheme = 'https';

				}

				$args = explode( '?', $url );

				if ( isset( $args[1] ) ) {

					parse_str( $args[1], $args );

					if ( isset( $args['login'] ) ) {
						$args['login'] = rawurlencode( $args['login'] );
					}

					$url = \add_query_arg( $args, self::new_login_url( $scheme ) );

				} else {

					$url = self::new_login_url( $scheme );

				}
			}

			return $url;
		}

		/**
		 * Returns the wpLogin status
		 *
		 * @since 1.0.0
		 *
		 * @return boolean
		 */
		private static function get_wp_login(): bool {
			return self::$wp_login;
		}

		/**
		 * Sets the wpLogin status
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $status - sets the module status.
		 *
		 * @return boolean
		 */
		private static function set_wp_login( bool $status ): bool {
			self::$wp_login = $status;
			return self::$wp_login;
		}
	}
}
