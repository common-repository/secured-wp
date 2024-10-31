<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Bacon\MatrixFactory;
use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\BinaryResult;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
final class BinaryWriter implements WriterInterface
{
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []) : ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);
        return new BinaryResult($matrix);
    }
}
