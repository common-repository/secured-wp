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

if ( ! class_exists( 'WPSEC\Helpers\Mail_Helper' ) ) {
	/**
	 * Provides general functionalities for plugin mailing
	 *
	 * @since 1.0.0
	 */
	class Mail_Helper {

		/**
		 * Returns the email header
		 *
		 * @param string $header_text - text to be put in between h1 tags in the mail header.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_mail_header( string $header_text = '' ): string {

			ob_start();
			WP_Helper::get_template_part(
				'email-header',
				array(
					'emailHeading' => $header_text,
				)
			);

			$header = ob_get_clean();

			return $header;
		}

		/**
		 * Returns the global email footer
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public static function get_mail_footer(): string {

			ob_start();
			WP_Helper::get_template_part(
				'email-footer',
			);

			$footer = ob_get_clean();

			return $footer;
		}
	}
}
