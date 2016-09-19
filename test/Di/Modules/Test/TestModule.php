<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 20:35
 */

namespace Modules\Test;

use Mindy\Base\Module;
use Mindy\Di\ServiceLocatorInterface;

class TestModule extends Module
{
    public $foo = '';

    public function boot(ServiceLocatorInterface $serviceLocator)
    {
        $serviceLocator->add('example', new \stdClass());
    }
}