<?php

/**
 * Assert
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */
namespace WPSEC_Vendor\Assert;

use Throwable;
/** @internal */
interface AssertionFailedException extends Throwable
{
    /**
     * @return string|null
     */
    public function getPropertyPath();
    /**
     * @return mixed
     */
    public function getValue();
    public function getConstraints() : array;
}
