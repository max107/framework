<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 12:08
 */

namespace Mindy\Base;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Mindy\Helper\Alias;
use RuntimeException;

class ModuleServiceProvider extends AbstractServiceProvider
{
    /**
     * @var string
     */
    protected $modulesPath;
    /**
     * @var array
     */
    protected $modules = [];

    /**
     * ModuleServiceProvider constructor.
     * @param array $modules
     * @param string $modulesPath
     */
    public function __construct(array $modules, string $modulesPath)
    {
        $this->modulesPath = $modulesPath;
        $this->modules = $modules;
        $this->provides = array_keys($modules);
    }

    /**
     * Register module alias
     * @param $id
     */
    protected function registerAlias($id)
    {
        Alias::set($id, $this->modulesPath . DIRECTORY_SEPARATOR . $id);
    }

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $container = $this->getContainer();
        foreach ($this->modules as $id => $config) {
            if (is_string($config)) {
                $className = $config;
                $container->add($id, $config);
            } else if (is_array($config)) {
                if (isset($config['class'])) {
                    $className = $config['class'];
                    unset($config['class']);
                    $container->add($id, $className)->withArgument($config);
                } else {
                    $className = $this->getDefaultModuleClassNamespace($id);
                    $container->add($id, $className, $config);
                }
            } else {
                throw new RuntimeException('Unknown module config format');
            }

            $this->registerAlias($id);

            call_user_func([$className, 'preConfigure']);
        }
    }

    /**
     * @param $name
     * @return string module namespace
     */
    protected function getDefaultModuleClassNamespace(string $name) : string
    {
        return '\\Modules\\' . ucfirst($name) . '\\' . ucfirst($name) . 'Module';
    }
}