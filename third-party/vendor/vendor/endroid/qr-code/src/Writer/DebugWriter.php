<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCodeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\DebugResult;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
final class DebugWriter implements WriterInterface, ValidatingWriterInterface
{
    public function write(QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = []) : ResultInterface
    {
        return new DebugResult($qrCode, $logo, $label, $options);
    }
    public function validateResult(ResultInterface $result, string $expectedData) : void
    {
        if (!$result instanceof DebugResult) {
            throw new \Exception('Unable to write logo: instance of DebugResult expected');
        }
        $result->setValidateResult(\true);
    }
}
