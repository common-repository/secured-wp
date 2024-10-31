<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Bacon;

use WPSEC_Vendor\BaconQrCode\Common\ErrorCorrectionLevel;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelQuartile;
/** @internal */
final class ErrorCorrectionLevelConverter
{
    public static function convertToBaconErrorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel) : ErrorCorrectionLevel
    {
        if ($errorCorrectionLevel instanceof ErrorCorrectionLevelLow) {
            return ErrorCorrectionLevel::valueOf('L');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelMedium) {
            return ErrorCorrectionLevel::valueOf('M');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelQuartile) {
            return ErrorCorrectionLevel::valueOf('Q');
        } elseif ($errorCorrectionLevel instanceof ErrorCorrectionLevelHigh) {
            return ErrorCorrectionLevel::valueOf('H');
        }
        throw new \Exception('Error correction level could not be converted');
    }
}
