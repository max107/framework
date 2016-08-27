<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:38
 */

namespace Mindy\Http\Collection;

use Mindy\Http\Request;

abstract class ParamCollection
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * ParamCollection constructor.
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    abstract public function get($name, $defaultValue = null);
}