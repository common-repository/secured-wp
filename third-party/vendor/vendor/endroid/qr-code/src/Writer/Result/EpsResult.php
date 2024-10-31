<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Writer\Result;

/** @internal */
final class EpsResult extends AbstractResult
{
    /** @var array<string> */
    private $lines;
    /** @param array<string> $lines */
    public function __construct(array $lines)
    {
        $this->lines = $lines;
    }
    public function getString() : string
    {
        return \implode("\n", $this->lines);
    }
    public function getMimeType() : string
    {
        return 'image/eps';
    }
}
