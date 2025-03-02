<?php

namespace WPSEC_Vendor\Safe;

use WPSEC_Vendor\Safe\Exceptions\MysqliException;
/**
 * Returns client per-process statistics.
 *
 * @return array Returns an array with client stats if success, FALSE otherwise.
 * @throws MysqliException
 *
 * @internal
 */
function mysqli_get_client_stats() : array
{
    \error_clear_last();
    $result = \mysqli_get_client_stats();
    if ($result === \false) {
        throw MysqliException::createFromPhpError();
    }
    return $result;
}
