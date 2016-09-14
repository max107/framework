<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 10:10
 */

namespace Mindy\Di\Tests;

use League\Container\Container;
use League\Container\ContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\ServiceProviderInterface;

interface FooBarInterface
{
}

class FooBarLogger implements FooBarInterface
{
}

class DiTestElement
{
    public $logger;

    public function setLogger(FooBarInterface $logger)
    {
        $this->logger = $logger;
    }
}

class FooBarProvider extends AbstractServiceProvider
{
    protected $provides = ['foo'];

    /**
     * Use the register method to register items with the container via the
     * protected $this->container property or the `getContainer` method
     * from the ContainerAwareTrait.
     *
     * @return void
     */
    public function register()
    {
        $this->getContainer()->add('foo', DiTestElement::class);
    }
}

class DiTest extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $container = new Container;

        $container->add('logger', FooBarLogger::class);
        $container->add('foo', DiTestElement::class)->withMethodCall('setLogger', ['logger']);
        $container->add('bar', function () use ($container) {
            $element = new DiTestElement();
            $element->setLogger($container->get('logger'));
            return $element;
        });

        $foo = $container->get('foo');
        $this->assertTrue($foo instanceof DiTestElement);
        $this->assertTrue($foo->logger instanceof FooBarLogger);

        $foo = $container->get('bar');
        $this->assertTrue($foo instanceof DiTestElement);
        $this->assertTrue($foo->logger instanceof FooBarLogger);
    }

    public function testServiceProvider()
    {
        $container = new Container;
        $container->addServiceProvider(new FooBarProvider);

        $foo = $container->get('foo');
        $this->assertTrue($foo instanceof DiTestElement);
    }
}