<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Bacon\MatrixFactory;
use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\EpsResult;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
final class EpsWriter implements WriterInterface
{
    public const DECIMAL_PRECISION = 10;
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []) : ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);
        $lines = ['%!PS-Adobe-3.0 EPSF-3.0', '%%BoundingBox: 0 0 ' . $matrix->getOuterSize() . ' ' . $matrix->getOuterSize(), '/F { rectfill } def', \number_format($qrCode->getBackgroundColor()->getRed() / 100, 2, '.', ',') . ' ' . \number_format($qrCode->getBackgroundColor()->getGreen() / 100, 2, '.', ',') . ' ' . \number_format($qrCode->getBackgroundColor()->getBlue() / 100, 2, '.', ',') . ' setrgbcolor', '0 0 ' . $matrix->getOuterSize() . ' ' . $matrix->getOuterSize() . ' F', \number_format($qrCode->getForegroundColor()->getRed() / 100, 2, '.', ',') . ' ' . \number_format($qrCode->getForegroundColor()->getGreen() / 100, 2, '.', ',') . ' ' . \number_format($qrCode->getForegroundColor()->getBlue() / 100, 2, '.', ',') . ' setrgbcolor'];
        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                if (1 === $matrix->getBlockValue($matrix->getBlockCount() - 1 - $rowIndex, $columnIndex)) {
                    $x = $matrix->getMarginLeft() + $matrix->getBlockSize() * $columnIndex;
                    $y = $matrix->getMarginLeft() + $matrix->getBlockSize() * $rowIndex;
                    $lines[] = \number_format($x, self::DECIMAL_PRECISION, '.', '') . ' ' . \number_format($y, self::DECIMAL_PRECISION, '.', '') . ' ' . \number_format($matrix->getBlockSize(), self::DECIMAL_PRECISION, '.', '') . ' ' . \number_format($matrix->getBlockSize(), self::DECIMAL_PRECISION, '.', '') . ' F';
                }
            }
        }
        return new EpsResult($lines);
    }
}
