<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:47
 */

namespace Mindy\Http\Collection;

class FileParamCollection extends ParamCollection
{
    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function get($name, $defaultValue = null)
    {
        return $this->request->getUploadedFile($name);
    }

    /**
     * @return array
     */
    public function all() : array
    {
        return $this->request->getUploadedFiles();
    }
}