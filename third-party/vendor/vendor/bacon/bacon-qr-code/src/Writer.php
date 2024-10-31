<?php

declare (strict_types=1);
namespace WPSEC_Vendor\BaconQrCode;

use WPSEC_Vendor\BaconQrCode\Common\ErrorCorrectionLevel;
use WPSEC_Vendor\BaconQrCode\Common\Version;
use WPSEC_Vendor\BaconQrCode\Encoder\Encoder;
use WPSEC_Vendor\BaconQrCode\Exception\InvalidArgumentException;
use WPSEC_Vendor\BaconQrCode\Renderer\RendererInterface;
/**
 * QR code writer.
 * @internal
 */
final class Writer
{
    /**
     * Renderer instance.
     *
     * @var RendererInterface
     */
    private $renderer;
    /**
     * Creates a new writer with a specific renderer.
     */
    public function __construct(RendererInterface $renderer)
    {
        $this->renderer = $renderer;
    }
    /**
     * Writes QR code and returns it as string.
     *
     * Content is a string which *should* be encoded in UTF-8, in case there are
     * non ASCII-characters present.
     *
     * @throws InvalidArgumentException if the content is empty
     */
    public function writeString(string $content, string $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING, ?ErrorCorrectionLevel $ecLevel = null, ?Version $forcedVersion = null) : string
    {
        if (\strlen($content) === 0) {
            throw new InvalidArgumentException('Found empty contents');
        }
        if (null === $ecLevel) {
            $ecLevel = ErrorCorrectionLevel::L();
        }
        return $this->renderer->render(Encoder::encode($content, $ecLevel, $encoding, $forcedVersion));
    }
    /**
     * Writes QR code to a file.
     *
     * @see Writer::writeString()
     */
    public function writeFile(string $content, string $filename, string $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING, ?ErrorCorrectionLevel $ecLevel = null, ?Version $forcedVersion = null) : void
    {
        \file_put_contents($filename, $this->writeString($content, $encoding, $ecLevel, $forcedVersion));
    }
}
