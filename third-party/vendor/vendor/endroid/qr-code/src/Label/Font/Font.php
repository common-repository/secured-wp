<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Label\Font;

/** @internal */
final class Font implements FontInterface
{
    /** @var string */
    private $path;
    /** @var int */
    private $size;
    public function __construct(string $path, int $size = 16)
    {
        $this->validatePath($path);
        $this->path = $path;
        $this->size = $size;
    }
    private function validatePath(string $path) : void
    {
        if (!\file_exists($path)) {
            throw new \Exception(\sprintf('Invalid font path "%s"', $path));
        }
    }
    public function getPath() : string
    {
        return $this->path;
    }
    public function getSize() : int
    {
        return $this->size;
    }
}
