<?php

namespace WPSEC_Vendor\Safe\Exceptions;

/** @internal */
class JsonException extends \JsonException implements SafeExceptionInterface
{
    public static function createFromPhpError() : self
    {
        return new self(\json_last_error_msg(), \json_last_error());
    }
}
