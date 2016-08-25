<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 24/08/16
 * Time: 17:57
 */

namespace Mindy\Helper\Tests;

use Mindy\Helper\Alias;

class AliasTest extends \PHPUnit_Framework_TestCase
{
    public function testAliases()
    {
        $aliases = Alias::all();
        Alias::clear();
        
        Alias::set('foo', __DIR__);
        Alias::set('Modules', __DIR__ . DIRECTORY_SEPARATOR . 'Modules');

        $this->assertEquals(__DIR__, Alias::get('foo'));
        $this->assertNull(Alias::get('Foo'));
        $this->assertNull(Alias::get('FOO'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'test', Alias::get('foo.test'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'Modules', Alias::get('foo.Modules'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Core', Alias::get('foo.Modules.Core'));

        $this->assertEquals([
            'foo' => __DIR__,
            'Modules' => __DIR__ . DIRECTORY_SEPARATOR . 'Modules',
        ], Alias::all());
        
        Alias::replace($aliases);
    }
}