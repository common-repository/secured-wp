<?php

declare (strict_types=1);
namespace WPSEC_Vendor\BaconQrCode\Renderer;

use WPSEC_Vendor\BaconQrCode\Encoder\QrCode;
/** @internal */
interface RendererInterface
{
    public function render(QrCode $qrCode) : string;
}
