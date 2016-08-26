<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 16:49
 */

namespace Mindy\Helper;

use LogicException;

class ReadOnlyCollection extends Collection
{
    private function error()
    {
        throw new LogicException('Failed to set value. Read only method.');
    }

    public function offsetSet($offset, $value)
    {
        $this->error();
    }

    public function set($key, $value)
    {
        $this->error();
    }

    public function merge(array $data)
    {
        $this->error();
    }

    public function clear()
    {
        $this->error();
    }

    public function remove($key)
    {
        $this->error();
    }
}