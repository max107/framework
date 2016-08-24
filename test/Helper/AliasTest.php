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
        Alias::set('app', __DIR__);
        Alias::set('Modules', __DIR__ . DIRECTORY_SEPARATOR . 'Modules');

        $this->assertEquals(__DIR__, Alias::get('app'));
        $this->assertNull(Alias::get('App'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'test', Alias::get('app.test'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'Modules', Alias::get('app.Modules'));
        $this->assertEquals(__DIR__ . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . 'Core', Alias::get('app.Modules.Core'));

        $this->assertEquals([
            'app' => __DIR__,
            'Modules' => __DIR__ . DIRECTORY_SEPARATOR . 'Modules',
        ], Alias::all());
    }
}