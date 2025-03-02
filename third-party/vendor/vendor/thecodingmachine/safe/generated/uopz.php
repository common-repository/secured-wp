<?php

namespace WPSEC_Vendor\Safe;

use WPSEC_Vendor\Safe\Exceptions\UopzException;
/**
 * Makes class extend parent
 *
 * @param string $class The name of the class to extend
 * @param string $parent The name of the class to inherit
 * @throws UopzException
 *
 * @internal
 */
function uopz_extend(string $class, string $parent) : void
{
    \error_clear_last();
    $safeResult = \uopz_extend($class, $parent);
    if ($safeResult === \false) {
        throw UopzException::createFromPhpError();
    }
}
/**
 * Makes class implement interface
 *
 * @param string $class
 * @param string $interface
 * @throws UopzException
 *
 * @internal
 */
function uopz_implement(string $class, string $interface) : void
{
    \error_clear_last();
    $safeResult = \uopz_implement($class, $interface);
    if ($safeResult === \false) {
        throw UopzException::createFromPhpError();
    }
}
