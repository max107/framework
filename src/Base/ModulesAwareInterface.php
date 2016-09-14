<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 11:47
 */

namespace Mindy\Base;

use Mindy\Di\ModuleContainer;

interface ModulesAwareInterface
{
    /**
     * @param ModuleContainer $container
     */
    public function setModulesContainer(ModuleContainer $container);

    /**
     * @return ModuleContainer
     */
    public function getModulesContainer() : ModuleContainer;

    /**
     * @param string $id
     * @return ModuleInterface
     */
    public function getModule(string $id) : ModuleInterface;

    /**
     * @param $id
     * @return bool
     */
    public function hasModule($id) : bool;

    /**
     * Returns the configuration of the currently installed modules.
     * @param bool $returnInstances
     * @return array|Module[] the configuration of the currently installed modules (module ID => configuration)
     */
    public function getModules($returnInstances = false) : array;
}