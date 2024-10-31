<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Bacon\MatrixFactory;
use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\PdfResult;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
final class PdfWriter implements WriterInterface
{
    public const WRITER_OPTION_UNIT = 'unit';
    public const WRITER_OPTION_PDF = 'fpdf';
    public const WRITER_OPTION_X = 'x';
    public const WRITER_OPTION_Y = 'y';
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []) : ResultInterface
    {
        $matrixFactory = new MatrixFactory();
        $matrix = $matrixFactory->create($qrCode);
        $unit = 'mm';
        if (isset($options[self::WRITER_OPTION_UNIT])) {
            $unit = $options[self::WRITER_OPTION_UNIT];
        }
        $allowedUnits = ['mm', 'pt', 'cm', 'in'];
        if (!\in_array($unit, $allowedUnits)) {
            throw new \Exception(\sprintf('PDF Measure unit should be one of [%s]', \implode(', ', $allowedUnits)));
        }
        $labelSpace = 0;
        if ($label instanceof LabelInterface) {
            $labelSpace = 30;
        }
        if (!\class_exists(\WPSEC_Vendor\FPDF::class)) {
            throw new \Exception('Unable to find FPDF: check your installation');
        }
        $foregroundColor = $qrCode->getForegroundColor();
        if ($foregroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }
        $backgroundColor = $qrCode->getBackgroundColor();
        if ($backgroundColor->getAlpha() > 0) {
            throw new \Exception('PDF Writer does not support alpha channels');
        }
        if (isset($options[self::WRITER_OPTION_PDF])) {
            $fpdf = $options[self::WRITER_OPTION_PDF];
            if (!$fpdf instanceof \WPSEC_Vendor\FPDF) {
                throw new \Exception('pdf option must be an instance of FPDF');
            }
        } else {
            // @todo Check how to add label height later
            $fpdf = new \WPSEC_Vendor\FPDF('P', $unit, [$matrix->getOuterSize(), $matrix->getOuterSize() + $labelSpace]);
            $fpdf->AddPage();
        }
        $x = 0;
        if (isset($options[self::WRITER_OPTION_X])) {
            $x = $options[self::WRITER_OPTION_X];
        }
        $y = 0;
        if (isset($options[self::WRITER_OPTION_Y])) {
            $y = $options[self::WRITER_OPTION_Y];
        }
        $fpdf->SetFillColor($backgroundColor->getRed(), $backgroundColor->getGreen(), $backgroundColor->getBlue());
        $fpdf->Rect($x, $y, $matrix->getOuterSize(), $matrix->getOuterSize(), 'F');
        $fpdf->SetFillColor($foregroundColor->getRed(), $foregroundColor->getGreen(), $foregroundColor->getBlue());
        for ($rowIndex = 0; $rowIndex < $matrix->getBlockCount(); ++$rowIndex) {
            for ($columnIndex = 0; $columnIndex < $matrix->getBlockCount(); ++$columnIndex) {
                if (1 === $matrix->getBlockValue($rowIndex, $columnIndex)) {
                    $fpdf->Rect($x + $matrix->getMarginLeft() + $columnIndex * $matrix->getBlockSize(), $y + $matrix->getMarginLeft() + $rowIndex * $matrix->getBlockSize(), $matrix->getBlockSize(), $matrix->getBlockSize(), 'F');
                }
            }
        }
        if ($logo instanceof LogoInterface) {
            $this->addLogo($logo, $fpdf, $x, $y, $matrix->getOuterSize());
        }
        if ($label instanceof LabelInterface) {
            $fpdf->SetXY($x, $y + $matrix->getOuterSize() + $labelSpace - 25);
            $fpdf->SetFont('Helvetica', null, $label->getFont()->getSize());
            $fpdf->Cell($matrix->getOuterSize(), 0, $label->getText(), 0, 0, 'C');
        }
        return new PdfResult($fpdf);
    }
    private function addLogo(LogoInterface $logo, \WPSEC_Vendor\FPDF $fpdf, float $x, float $y, float $size) : void
    {
        $logoPath = $logo->getPath();
        $logoHeight = $logo->getResizeToHeight();
        $logoWidth = $logo->getResizeToWidth();
        if (null === $logoHeight || null === $logoWidth) {
            [$logoSourceWidth, $logoSourceHeight] = \getimagesize($logoPath);
            if (null === $logoWidth) {
                $logoWidth = (int) $logoSourceWidth;
            }
            if (null === $logoHeight) {
                $aspectRatio = $logoWidth / $logoSourceWidth;
                $logoHeight = (int) ($logoSourceHeight * $aspectRatio);
            }
        }
        $logoX = $x + $size / 2 - (int) $logoWidth / 2;
        $logoY = $y + $size / 2 - (int) $logoHeight / 2;
        $fpdf->Image($logoPath, $logoX, $logoY, $logoWidth, $logoHeight);
    }
}
