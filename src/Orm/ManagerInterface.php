<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 19:14
 */

namespace Mindy\Orm;

interface ManagerInterface
{
    /**
     * @param array $condition
     * @return mixed
     */
    public function get(array $condition = []);
}