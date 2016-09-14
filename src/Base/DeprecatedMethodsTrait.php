<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 12:52
 */

namespace Mindy\Base;

trait DeprecatedMethodsTrait
{
    public function getComponent($id)
    {
        return $this->getContainer()->get($id);
    }

    public function hasComponent($id)
    {
        return $this->getContainer()->has($id);
    }
}