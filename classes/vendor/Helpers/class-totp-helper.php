<?php
/**
 * Plugin WPS secured
 *
 * @package   WPS
 * @author    wp-secured.com
 * @copyright Copyright © 2021
 *
 * @since 2.0.0
 */

declare(strict_types=1);

namespace WPSEC\Helpers;

use WPSEC\Controllers\User;
use WPSEC_Vendor\OTPHP\TOTP;
use WPSEC_Vendor\Endroid\QrCode\QrCode;
use WPSEC_Vendor\Endroid\QrCode\Writer\SvgWriter;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'WPSEC\Helpers\TOTP_Helper' ) ) {

	/**
	 * TOTP helper class - ease the process of working with TOTP related code
	 *
	 * @since 1.7
	 */
	class TOTP_Helper {

		/**
		 * Generates QR Code in svg format, ready to be used in the <img> HTML tag (src attribute)
		 *
		 * @param \WP_User $user - The \WP_User for which we have to generate the QR code, null if we want to use the current user.
		 *
		 * @return string
		 *
		 * @since 1.7
		 */
		public static function generate_qrsvg_data( \WP_User $user = null ): string {
			$otp = TOTP::create( User::get_user_totp( $user ) );

			$otp->setLabel(
				\get_bloginfo( 'name' )
			);

			$uri         = $otp->getProvisioningUri();
			$qr          = QrCode::create( $uri );
			$writer      = new SvgWriter();
			$result      = $writer->write( $qr, null, null, array( SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true ) );
			$gr_code_uri = $result->getDataUri();

			return $gr_code_uri;
		}
	}
}
