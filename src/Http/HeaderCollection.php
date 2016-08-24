<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 20:20
 */

namespace Mindy\Http;

class HeaderCollection extends Collection
{
    private $_headerStrings = [];

    /**
     * HeaderCollection constructor.
     * @param null $data
     */
    public function __construct($data)
    {
        $newData = [];
        foreach ($data as $headerString) {
            $tmp = explode(':', $headerString);
            $headerKey = trim(array_shift($tmp));
            $this->_headerStrings[$headerKey] = trim(implode(': ', $tmp));
            $newData[$headerKey] = array_map('trim', explode(',', $tmp));
        }
        parent::__construct($newData);
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key)
    {
        return parent::has(strtolower($key));
    }

    /**
     * @param $key
     * @param null $defaultValue
     * @return array|null
     */
    public function get($key, $defaultValue = null)
    {
        return parent::get(strtolower($key), $defaultValue);
    }
}