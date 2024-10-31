<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode;

use WPSEC_Vendor\Endroid\QrCode\Color\ColorInterface;
use WPSEC_Vendor\Endroid\QrCode\Encoding\EncodingInterface;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use WPSEC_Vendor\Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
/** @internal */
interface QrCodeInterface
{
    public function getData() : string;
    public function getEncoding() : EncodingInterface;
    public function getErrorCorrectionLevel() : ErrorCorrectionLevelInterface;
    public function getSize() : int;
    public function getMargin() : int;
    public function getRoundBlockSizeMode() : RoundBlockSizeModeInterface;
    public function getForegroundColor() : ColorInterface;
    public function getBackgroundColor() : ColorInterface;
}
