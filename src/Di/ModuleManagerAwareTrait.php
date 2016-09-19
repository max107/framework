<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 21:10
 */

namespace Mindy\Di;

use Mindy\Base\ModuleInterface;

trait ModuleManagerAwareTrait
{
    /**
     * @var ModuleManagerInterface
     */
    protected $moduleManager;

    /**
     * @param ModuleManagerInterface $container
     * @return $this
     */
    public function setModuleManager(ModuleManagerInterface $container)
    {
        $this->moduleManager = $container;
        return $this;
    }

    /**
     * @return ModuleManagerInterface
     */
    public function getModuleManager()
    {
        return $this->moduleManager;
    }

    /**
     * @param string $id
     * @return ModuleInterface
     */
    public function getModule(string $id) : ModuleInterface
    {
        return $this->getModuleManager()->get($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasModule($id) : bool
    {
        return $this->getModuleManager()->has($id);
    }

    /**
     * @return ModuleInterface[]
     */
    public function getModules() : array
    {
        return $this->getModuleManager()->all();
    }
}