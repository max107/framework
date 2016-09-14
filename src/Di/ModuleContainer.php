<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 12:00
 */

namespace Mindy\Di;

use League\Container\Container;

/**
 * Class ModuleContainer
 * @package Mindy\Di
 */
class ModuleContainer extends Container
{
    /**
     * @var array
     */
    protected $modules = [];

    /**
     * {@inheritdoc}
     */
    public function add($alias, $concrete = null, $share = false)
    {
        $this->addModule($alias, $concrete);
        return parent::add($alias, $concrete, $share);
    }

    /**
     * @param $alias
     * @param $concrete
     */
    protected function addModule($alias, $concrete)
    {
        $this->modules[$alias] = $concrete;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->modules;
    }
}