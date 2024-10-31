<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer;

use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
/** @internal */
interface ValidatingWriterInterface
{
    public function validateResult(ResultInterface $result, string $expectedData) : void;
}
