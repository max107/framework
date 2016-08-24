<?php
/**
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 09/06/14.06.2014 17:44
 */

namespace Mindy\Controller\Action;

/**
 * IAction is the interface that must be implemented by controller actions.
 *
 * @package Mindy\Base
 * @since 1.0
 */
interface IAction
{
    /**
     * @param array $params
     * @return mixed
     */
    public function runInternal(array $params = []);

    /**
     * @return string
     */
    public function getId();
}
