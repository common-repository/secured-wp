<?php

declare (strict_types=1);
namespace WPSEC_Vendor\BaconQrCode\Renderer\Path;

/** @internal */
interface OperationInterface
{
    /**
     * Translates the operation's coordinates.
     */
    public function translate(float $x, float $y) : self;
}
