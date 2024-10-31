<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
interface WriterInterface
{
    /** @param array<mixed> $options */
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []) : ResultInterface;
}
