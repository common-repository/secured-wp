<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Matrix;

use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
/** @internal */
interface MatrixFactoryInterface
{
    public function create(QrCodeInterface $qrCode) : MatrixInterface;
}
