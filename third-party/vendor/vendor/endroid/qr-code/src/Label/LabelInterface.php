<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Label;

use WPSEC_Vendor\Endroid\QrCode\Color\ColorInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Font\FontInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Margin\MarginInterface;
/** @internal */
interface LabelInterface
{
    public function getText() : string;
    public function getFont() : FontInterface;
    public function getAlignment() : LabelAlignmentInterface;
    public function getMargin() : MarginInterface;
    public function getTextColor() : ColorInterface;
}
