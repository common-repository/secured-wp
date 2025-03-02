<?php

namespace WPSEC_Vendor\Safe;

use WPSEC_Vendor\Safe\Exceptions\VarException;
/**
 * Set the type of variable var to
 * type.
 *
 * @param mixed $var The variable being converted.
 * @param string $type Possibles values of type are:
 *
 *
 *
 * "boolean" or "bool"
 *
 *
 *
 *
 * "integer" or "int"
 *
 *
 *
 *
 * "float" or "double"
 *
 *
 *
 *
 * "string"
 *
 *
 *
 *
 * "array"
 *
 *
 *
 *
 * "object"
 *
 *
 *
 *
 * "null"
 *
 *
 *
 * @throws VarException
 *
 * @internal
 */
function settype(&$var, string $type) : void
{
    \error_clear_last();
    $safeResult = \settype($var, $type);
    if ($safeResult === \false) {
        throw VarException::createFromPhpError();
    }
}
