<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer\Result;

/** @internal */
final class PdfResult extends AbstractResult
{
    /** @var \FPDF */
    private $fpdf;
    public function __construct(\WPSEC_Vendor\FPDF $fpdf)
    {
        $this->fpdf = $fpdf;
    }
    public function getPdf() : \WPSEC_Vendor\FPDF
    {
        return $this->fpdf;
    }
    public function getString() : string
    {
        return $this->fpdf->Output('S');
    }
    public function getMimeType() : string
    {
        return 'application/pdf';
    }
}
