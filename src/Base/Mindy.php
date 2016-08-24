<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 13:11
 */

declare(strict_types = 1);

namespace Mindy\Base;

class Mindy extends MindyBase
{
    /**
     * @return string
     */
    public static function getVersion() : string
    {
        return '3.0beta';
    }
}