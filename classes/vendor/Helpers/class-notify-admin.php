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

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\Notify_Admin' ) ) {
	/**
	 * Sends notification email to the site admin
	 *
	 * @since 1.0.0
	 */
	class Notify_Admin {

		/**
		 * Notifies admin when some of the accounts generates too many attempts
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_User $user - The WP user.
		 * @param integer  $attempts - number of attempts.
		 *
		 * @return boolean
		 */
		public static function send_notification_email( \WP_User $user, int $attempts ): bool {
			$url = WP_Helper::get_site_url();

			$to = \get_bloginfo( 'admin_email' );

			$subject = \__( 'Maximum number of unsuccessful login attempts reached', 'secured-wp' );

			$message = \sprintf(
				/* translators: %1$s: Name of the user, %2$s: Url of the site, %3$s: Number of the login attempts */
				__( 'User %1$s has tried to log in with an unsuitable password too many times to your site %2$s.\nThe system has identified %3$s unsuccessful login attempts.', 'secured-wp' ),
				$user->display_name,
				$url,
				$attempts
			);

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );

			return \wp_mail( $to, $subject, $message, $headers );
		}
	}
}
