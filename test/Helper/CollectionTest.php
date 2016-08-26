<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:44
 */

namespace Mindy\Tests\Helper;

use Mindy\Helper\Collection;
use Mindy\Helper\Json;
use Mindy\Interfaces\Arrayable;
use Mindy\Interfaces\CollectionInterface;
use Countable;
use Serializable;
use IteratorAggregate;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $c = new Collection();
        $this->assertInstanceOf(Countable::class, $c);
        $this->assertInstanceOf(IteratorAggregate::class, $c);
        $this->assertInstanceOf(Serializable::class, $c);
        $this->assertInstanceOf(CollectionInterface::class, $c);
        $this->assertInstanceOf(Arrayable::class, $c);
    }

    public function testSetGet()
    {
        $c = new Collection(['pk' => 1]);
        $this->assertEquals(['pk' => 1], $c->all());
        $this->assertEquals(1, $c->get('pk'));
        $this->assertEquals(2, $c->get('qwe', 2));
        $c->set('qwe', 3);
        $this->assertEquals(3, $c->get('qwe'));
        $c->remove('qwe');
        $this->assertEquals(1, count($c));
        $c->clear();
        $this->assertEquals([], $c->all());
        $c->set('pk', 1);
        $c->merge(['qwe' => 2]);
        $this->assertEquals(2, count($c));
        $this->assertEquals($c->all(), $c->toArray());
    }

    public function testArrayAccess()
    {
        $c = new Collection(['foo' => 1, 'bar' => 2]);
        $this->assertEquals(1, $c['foo']);
        $this->assertEquals(2, $c['bar']);
        $c['foo'] = 2;
        $c['bar'] = 1;
        $this->assertEquals(2, $c['foo']);
        $this->assertEquals(1, $c['bar']);
        unset($c['foo']);
        $this->assertNull($c['foo']);
        $this->assertFalse(isset($c['foo']));
        $this->assertTrue(isset($c['bar']));
    }

    public function testGetIterator()
    {
        $c = new Collection([1, 2, 3, 4, 5]);
        foreach ($c as $key => $value) {
            $this->assertEquals($key + 1, $value);
        }
    }

    public function testSerializeUnserialize()
    {
        $c = new Collection([1, 2, 3]);
        $this->assertEquals(serialize([1, 2, 3]), $c->serialize());
        $c->unserialize($c->serialize());
        $this->assertEquals(unserialize(serialize([1, 2, 3])), $c->all());
    }

    public function testToJson()
    {
        $this->assertEquals(Json::encode([1, 2, 3]), (new Collection([1, 2, 3]))->toJson());
    }

    public function testCount()
    {
        $c = new Collection(['foo' => 1, 'bar' => 2]);
        $this->assertEquals(2, count($c));
    }
}