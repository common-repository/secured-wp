<?php

declare (strict_types=1);
namespace WPSEC_Vendor\Endroid\QrCode\Builder;

use WPSEC_Vendor\Endroid\QrCode\Color\ColorInterface;
use WPSEC_Vendor\Endroid\QrCode\Encoding\EncodingInterface;
use WPSEC_Vendor\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Alignment\LabelAlignmentInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Font\FontInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Label;
use WPSEC_Vendor\Endroid\QrCode\Label\LabelInterface;
use WPSEC_Vendor\Endroid\QrCode\Label\Margin\MarginInterface;
use WPSEC_Vendor\Endroid\QrCode\Logo\Logo;
use WPSEC_Vendor\Endroid\QrCode\Logo\LogoInterface;
use WPSEC_Vendor\Endroid\QrCode\QrCode;
use WPSEC_Vendor\Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\PngWriter;
use WPSEC_Vendor\Endroid\QrCode\Writer\Result\ResultInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\ValidatingWriterInterface;
use WPSEC_Vendor\Endroid\QrCode\Writer\WriterInterface;
/** @internal */
class Builder implements BuilderInterface
{
    /** @var array<mixed> */
    private $options;
    public function __construct()
    {
        $this->options = ['writer' => new PngWriter(), 'writerOptions' => [], 'qrCodeClass' => QrCode::class, 'logoClass' => Logo::class, 'labelClass' => Label::class, 'validateResult' => \false];
    }
    public static function create() : BuilderInterface
    {
        return new self();
    }
    public function writer(WriterInterface $writer) : BuilderInterface
    {
        $this->options['writer'] = $writer;
        return $this;
    }
    /** @param array<mixed> $writerOptions */
    public function writerOptions(array $writerOptions) : BuilderInterface
    {
        $this->options['writerOptions'] = $writerOptions;
        return $this;
    }
    public function data(string $data) : BuilderInterface
    {
        $this->options['data'] = $data;
        return $this;
    }
    public function encoding(EncodingInterface $encoding) : BuilderInterface
    {
        $this->options['encoding'] = $encoding;
        return $this;
    }
    public function errorCorrectionLevel(ErrorCorrectionLevelInterface $errorCorrectionLevel) : BuilderInterface
    {
        $this->options['errorCorrectionLevel'] = $errorCorrectionLevel;
        return $this;
    }
    public function size(int $size) : BuilderInterface
    {
        $this->options['size'] = $size;
        return $this;
    }
    public function margin(int $margin) : BuilderInterface
    {
        $this->options['margin'] = $margin;
        return $this;
    }
    public function roundBlockSizeMode(RoundBlockSizeModeInterface $roundBlockSizeMode) : BuilderInterface
    {
        $this->options['roundBlockSizeMode'] = $roundBlockSizeMode;
        return $this;
    }
    public function foregroundColor(ColorInterface $foregroundColor) : BuilderInterface
    {
        $this->options['foregroundColor'] = $foregroundColor;
        return $this;
    }
    public function backgroundColor(ColorInterface $backgroundColor) : BuilderInterface
    {
        $this->options['backgroundColor'] = $backgroundColor;
        return $this;
    }
    public function logoPath(string $logoPath) : BuilderInterface
    {
        $this->options['logoPath'] = $logoPath;
        return $this;
    }
    public function logoResizeToWidth(int $logoResizeToWidth) : BuilderInterface
    {
        $this->options['logoResizeToWidth'] = $logoResizeToWidth;
        return $this;
    }
    public function logoResizeToHeight(int $logoResizeToHeight) : BuilderInterface
    {
        $this->options['logoResizeToHeight'] = $logoResizeToHeight;
        return $this;
    }
    public function logoPunchoutBackground(bool $logoPunchoutBackground) : BuilderInterface
    {
        $this->options['logoPunchoutBackground'] = $logoPunchoutBackground;
        return $this;
    }
    public function labelText(string $labelText) : BuilderInterface
    {
        $this->options['labelText'] = $labelText;
        return $this;
    }
    public function labelFont(FontInterface $labelFont) : BuilderInterface
    {
        $this->options['labelFont'] = $labelFont;
        return $this;
    }
    public function labelAlignment(LabelAlignmentInterface $labelAlignment) : BuilderInterface
    {
        $this->options['labelAlignment'] = $labelAlignment;
        return $this;
    }
    public function labelMargin(MarginInterface $labelMargin) : BuilderInterface
    {
        $this->options['labelMargin'] = $labelMargin;
        return $this;
    }
    public function labelTextColor(ColorInterface $labelTextColor) : BuilderInterface
    {
        $this->options['labelTextColor'] = $labelTextColor;
        return $this;
    }
    public function validateResult(bool $validateResult) : BuilderInterface
    {
        $this->options['validateResult'] = $validateResult;
        return $this;
    }
    public function build() : ResultInterface
    {
        if (!isset($this->options['writer']) || !$this->options['writer'] instanceof WriterInterface) {
            throw new \Exception('Pass a valid writer via $builder->writer()');
        }
        $writer = $this->options['writer'];
        if ($this->options['validateResult'] && !$writer instanceof ValidatingWriterInterface) {
            throw new \Exception('Unable to validate result with ' . \get_class($writer));
        }
        /** @var QrCode $qrCode */
        $qrCode = $this->buildObject($this->options['qrCodeClass']);
        /** @var LogoInterface|null $logo */
        $logo = $this->buildObject($this->options['logoClass'], 'logo');
        /** @var LabelInterface|null $label */
        $label = $this->buildObject($this->options['labelClass'], 'label');
        $result = $writer->write($qrCode, $logo, $label, $this->options['writerOptions']);
        if ($this->options['validateResult'] && $writer instanceof ValidatingWriterInterface) {
            $writer->validateResult($result, $qrCode->getData());
        }
        return $result;
    }
    /**
     * @param class-string $class
     *
     * @return mixed
     */
    private function buildObject(string $class, string $optionsPrefix = null)
    {
        /** @var \ReflectionClass<object> $reflectionClass */
        $reflectionClass = new \ReflectionClass($class);
        $arguments = [];
        $hasBuilderOptions = \false;
        $missingRequiredArguments = [];
        /** @var \ReflectionMethod $constructor */
        $constructor = $reflectionClass->getConstructor();
        $constructorParameters = $constructor->getParameters();
        foreach ($constructorParameters as $parameter) {
            $optionName = null === $optionsPrefix ? $parameter->getName() : $optionsPrefix . \ucfirst($parameter->getName());
            if (isset($this->options[$optionName])) {
                $hasBuilderOptions = \true;
                $arguments[] = $this->options[$optionName];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                $missingRequiredArguments[] = $optionName;
            }
        }
        if (!$hasBuilderOptions) {
            return null;
        }
        if (\count($missingRequiredArguments) > 0) {
            throw new \Exception(\sprintf('Missing required arguments: %s', \implode(', ', $missingRequiredArguments)));
        }
        return $reflectionClass->newInstanceArgs($arguments);
    }
}
