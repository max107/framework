<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 12:03
 */

namespace Mindy\Base;

use Mindy\Di\ModuleContainer;

trait ModulesAwareTrait
{
    protected $modulesContainer;

    /**
     * @param string $id
     * @return ModuleInterface
     */
    public function getModule(string $id) : ModuleInterface
    {
        return $this->getModulesContainer()->get($id);
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasModule($id) : bool
    {
        return $this->getModulesContainer()->has($id);
    }

    /**
     * Returns the configuration of the currently installed modules.
     * @param bool $returnInstances
     * @return array|Module[] the configuration of the currently installed modules (module ID => configuration)
     */
    public function getModules($returnInstances = false) : array
    {
        $container = $this->getModulesContainer();
        $modules = $container->all();
        if ($returnInstances === false) {
            return $modules;
        }
        $instances = [];
        foreach ($modules as $name => $config) {
            $instances[$name] = $container->get($name);
        }
        return $instances;
    }

    /**
     * @param ModuleContainer $container
     */
    public function setModulesContainer(ModuleContainer $container)
    {
        $this->modulesContainer = $container;
    }

    /**
     * @return ModuleContainer
     */
    public function getModulesContainer() : ModuleContainer
    {
        return $this->modulesContainer;
    }
}