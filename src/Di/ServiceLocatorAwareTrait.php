<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 21:07
 */

namespace Mindy\Di;

/**
 * Class ServiceLocatorAwareTrait
 * @package Mindy\Di
 */
trait ServiceLocatorAwareTrait
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * @param ServiceLocatorInterface $container
     * @return $this
     */
    public function setServiceLocator(ServiceLocatorInterface $container)
    {
        $this->serviceLocator = $container;
        return $this;
    }

    /**
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator() : ServiceLocatorInterface
    {
        return $this->serviceLocator;
    }
}