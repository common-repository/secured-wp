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
namespace WPSEC_Vendor\ScssPhp\ScssPhp\Block;

use WPSEC_Vendor\ScssPhp\ScssPhp\Block;
use WPSEC_Vendor\ScssPhp\ScssPhp\Type;
/**
 * @internal
 */
class ElseifBlock extends Block
{
    /**
     * @var array
     */
    public $cond;
    public function __construct()
    {
        $this->type = Type::T_ELSEIF;
    }
}
