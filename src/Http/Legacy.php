<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 08/08/16
 * Time: 10:04
 */

namespace Mindy\Http;

/**
 * Class Legacy
 * @package Mindy\Http
 */
trait Legacy
{
    /**
     * @return bool
     */
    public function getIsPost()
    {
        return strtoupper($this->getRequest()->getMethod()) === 'POST';
    }

    /**
     * @return bool
     */
    public function getIsAjax()
    {
        return $this->isXhr();
    }
}