<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:40
 */

namespace Mindy\Http\Collection;

class GetParamCollection extends ParamCollection
{
    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return $this->request->getQueryParam($name, $defaultValue);
    }

    /**
     * @param $name
     * @return bool
     */
    public function has($name) : bool
    {
        return $this->request->getQueryParam($name, false) === false;
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->request->getQueryParams();
    }
}