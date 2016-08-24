<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:44
 */

namespace Mindy\Http\Tests;


use Mindy\Http\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $this->assertEquals(['pk' => 1], (new Collection(['pk' => 1]))->all());
        $this->assertEquals(1, (new Collection(['pk' => 1]))->get('pk'));
        $this->assertEquals(2, (new Collection(['pk' => 1]))->get('qwe', 2));
    }
}