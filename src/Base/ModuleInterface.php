<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 11:59
 */

namespace Mindy\Base;

/**
 * Interface ModuleInterface
 * @package Mindy\Base
 */
interface ModuleInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getAdminMenu() : array;
}