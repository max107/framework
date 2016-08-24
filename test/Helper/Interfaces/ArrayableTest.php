<?php

namespace Mindy\Helper\Tests\Interfaces;

use Mindy\Helper\Interfaces\Arrayable;

/**
 * 
 *
 * All rights reserved.
 * 
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 07/01/14.01.2014 14:54
 */


class ArrayableObject implements Arrayable
{
    public $data = [1, 2, 3];

    /**
     * Converts the object into an array.
     * @return array the array representation of this object
     */
    public function toArray()
    {
        return ['data' => $this->data];
    }
}


class ArrayableTest extends \PHPUnit_Framework_TestCase
{
    public function testToArray()
    {
        $obj = new ArrayableObject();
        $this->assertEquals([
            'data' => [1, 2, 3]
        ], $obj->toArray());
    }
}
