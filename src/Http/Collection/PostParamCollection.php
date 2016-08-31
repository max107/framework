<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:45
 */

namespace Mindy\Http\Collection;

class PostParamCollection extends ParamCollection
{
    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return $this->request->getParsedBodyParam($name, $defaultValue);
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->request->getParsedBody();
    }
}