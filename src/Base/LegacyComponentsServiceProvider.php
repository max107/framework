<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 12:54
 */

namespace Mindy\Base;

use Closure;
use League\Container\ServiceProvider\AbstractServiceProvider;
use RuntimeException;

class LegacyComponentsServiceProvider extends AbstractServiceProvider
{
    /**
     * @var array
     */
    protected $components = [];

    /**
     * ComponentsServiceProvider constructor.
     * @param array $components
     */
    public function __construct(array $components)
    {
        $this->components = $components;
        $this->provides = array_keys($components);
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
        foreach ($this->components as $id => $config) {
            if (is_string($config)) {
                $container->share($id, $config);
            } else if (is_array($config)) {
                if (isset($config['class'])) {
                    $className = $config['class'];
                    unset($config['class']);
                    $container->share($id, $className)->withArgument($config);
                } else {
                    throw new RuntimeException('Missing class in component config');
                }
            } else if ($config instanceof Closure) {
                $container->share($id, $config);
            } else {
                throw new RuntimeException('Unknown config format');
            }
        }
    }
}