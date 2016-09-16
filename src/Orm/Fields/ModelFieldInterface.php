<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 15:15
 */

namespace Mindy\Orm\Fields;

interface ModelFieldInterface
{
    /**
     * @param string $name
     * @return mixed
     */
    public function setName(string $name);
}