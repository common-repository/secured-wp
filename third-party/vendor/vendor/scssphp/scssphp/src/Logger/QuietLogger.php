<?php

/**
 * SCSSPHP
 *
 * @copyright 2012-2020 Leaf Corcoran
 *
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @link http://scssphp.github.io/scssphp
 */
namespace WPSEC_Vendor\ScssPhp\ScssPhp\Logger;

/**
 * A logger that silently ignores all messages.
 *
 * @final
 * @internal
 */
class QuietLogger implements LoggerInterface
{
    public function warn($message, $deprecation = \false)
    {
    }
    public function debug($message)
    {
    }
}
