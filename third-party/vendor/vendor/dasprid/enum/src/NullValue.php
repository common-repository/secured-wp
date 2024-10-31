<?php

declare (strict_types=1);
namespace WPSEC_Vendor\DASPRiD\Enum;

use WPSEC_Vendor\DASPRiD\Enum\Exception\CloneNotSupportedException;
use WPSEC_Vendor\DASPRiD\Enum\Exception\SerializeNotSupportedException;
use WPSEC_Vendor\DASPRiD\Enum\Exception\UnserializeNotSupportedException;
/** @internal */
final class NullValue
{
    /**
     * @var self
     */
    private static $instance;
    private function __construct()
    {
    }
    public static function instance() : self
    {
        return self::$instance ?: (self::$instance = new self());
    }
    /**
     * Forbid cloning enums.
     *
     * @throws CloneNotSupportedException
     */
    public final function __clone()
    {
        throw new CloneNotSupportedException();
    }
    /**
     * Forbid serializing enums.
     *
     * @throws SerializeNotSupportedException
     */
    public final function __sleep() : array
    {
        throw new SerializeNotSupportedException();
    }
    /**
     * Forbid unserializing enums.
     *
     * @throws UnserializeNotSupportedException
     */
    public final function __wakeup() : void
    {
        throw new UnserializeNotSupportedException();
    }
}
