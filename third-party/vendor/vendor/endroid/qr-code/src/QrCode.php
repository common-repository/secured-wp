<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode;

use WPSEC_Vendor\Endroid\QrCode\Color\Color;
use WPSEC_Vendor\Endroid\QrCode\Color\ColorInterface;
use WPSEC_Vendor\Endroid\QrCode\Encoding\Encoding;
use WPSEC_Vendor\Endroid\QrCode\Encoding\EncodingInterface;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use WPSEC_Vendor\Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use WPSEC_Vendor\Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
/** @internal */
final class QrCode implements QrCodeInterface
{
    /** @var string */
    private $data;
    /** @var EncodingInterface */
    private $encoding;
    /** @var ErrorCorrectionLevelInterface */
    private $errorCorrectionLevel;
    /** @var int */
    private $size;
    /** @var int */
    private $margin;
    /** @var RoundBlockSizeModeInterface */
    private $roundBlockSizeMode;
    /** @var ColorInterface */
    private $foregroundColor;
    /** @var ColorInterface */
    private $backgroundColor;
    public function __construct(string $data, EncodingInterface $encoding = null, ErrorCorrectionLevelInterface $errorCorrectionLevel = null, int $size = 300, int $margin = 10, RoundBlockSizeModeInterface $roundBlockSizeMode = null, ColorInterface $foregroundColor = null, ColorInterface $backgroundColor = null)
    {
        $this->data = $data;
        $this->encoding = isset($encoding) ? $encoding : new Encoding('UTF-8');
        $this->errorCorrectionLevel = isset($errorCorrectionLevel) ? $errorCorrectionLevel : new ErrorCorrectionLevelLow();
        $this->size = $size;
        $this->margin = $margin;
        $this->roundBlockSizeMode = isset($roundBlockSizeMode) ? $roundBlockSizeMode : new RoundBlockSizeModeMargin();
        $this->foregroundColor = isset($foregroundColor) ? $foregroundColor : new Color(0, 0, 0);
        $this->backgroundColor = isset($backgroundColor) ? $backgroundColor : new Color(255, 255, 255);
    }
    public static function create(string $data) : self
    {
        return new self($data);
    }
    public function getData() : string
    {
        return $this->data;
    }
    public function setData(string $data) : self
    {
        $this->data = $data;
        return $this;
    }
    public function getEncoding() : EncodingInterface
    {
        return $this->encoding;
    }
    public function setEncoding(Encoding $encoding) : self
    {
        $this->encoding = $encoding;
        return $this;
    }
    public function getErrorCorrectionLevel() : ErrorCorrectionLevelInterface
    {
        return $this->errorCorrectionLevel;
    }
    public function setErrorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel) : self
    {
        $this->errorCorrectionLevel = $errorCorrectionLevel;
        return $this;
    }
    public function getSize() : int
    {
        return $this->size;
    }
    public function setSize(int $size) : self
    {
        $this->size = $size;
        return $this;
    }
    public function getMargin() : int
    {
        return $this->margin;
    }
    public function setMargin(int $margin) : self
    {
        $this->margin = $margin;
        return $this;
    }
    public function getRoundBlockSizeMode() : RoundBlockSizeModeInterface
    {
        return $this->roundBlockSizeMode;
    }
    public function setRoundBlockSizeMode(RoundBlockSizeModeInterface $roundBlockSizeMode) : self
    {
        $this->roundBlockSizeMode = $roundBlockSizeMode;
        return $this;
    }
    public function getForegroundColor() : ColorInterface
    {
        return $this->foregroundColor;
    }
    public function setForegroundColor(ColorInterface $foregroundColor) : self
    {
        $this->foregroundColor = $foregroundColor;
        return $this;
    }
    public function getBackgroundColor() : ColorInterface
    {
        return $this->backgroundColor;
    }
    public function setBackgroundColor(ColorInterface $backgroundColor) : self
    {
        $this->backgroundColor = $backgroundColor;
        return $this;
    }
}
