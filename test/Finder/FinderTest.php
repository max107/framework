<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 13:48
 */

namespace Mindy\Tests\Finder;

use Mindy\Finder\Finder;
use Mindy\Finder\TemplateFinder\AppTemplateFinder;
use Mindy\Finder\TemplateFinder\TemplateFinder;
use Mindy\Finder\TemplateFinder\ThemeTemplateFinder;

class FinderTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $finder = new Finder(['finders' => [
            new TemplateFinder()
        ]]);
        $this->assertEquals(['/templates'], $finder->getPaths());

        $finder = new Finder(['finders' => [
            ['class' => TemplateFinder::class]
        ]]);
        $this->assertEquals(['/templates'], $finder->getPaths());
    }

    /**
     * @expectedException \Exception
     */
    public function testWrongConfiguration()
    {
        $finder = new Finder(['finders' => [
            new \stdClass()
        ]]);
        $this->assertEquals(['/templates'], $finder->getPaths());
    }

    public function testFinder()
    {
        $finder = new Finder(['finders' => [
            new TemplateFinder()
        ]]);
        $this->assertEquals(['/templates'], $finder->getPaths());
        $this->assertNull($finder->find('index.html'));
    }

    public function testTemplateFinder()
    {
        $finder = new TemplateFinder(['basePath' => __DIR__ . '/data']);
        $this->assertEquals([
            __DIR__ . '/data/templates'
        ], $finder->getPaths());
        $this->assertNull($finder->find('index.html'));
        $this->assertEquals(__DIR__ . '/data/templates/base.html', $finder->find('base.html'));
    }

    public function testThemeTemplateFinder()
    {
        $finder = new ThemeTemplateFinder(['basePath' => __DIR__ . '/data']);
        $this->assertEquals([
            __DIR__ . '/data/themes/default/templates'
        ], $finder->getPaths());
        $this->assertNull($finder->find('index.html'));
        $this->assertEquals(__DIR__ . '/data/themes/default/templates/base.html', $finder->find('base.html'));

        $finder = new ThemeTemplateFinder(['basePath' => __DIR__ . '/data', 'theme' => function () {
                return 'default';
        }]);
        $this->assertEquals([
            __DIR__ . '/data/themes/default/templates'
        ], $finder->getPaths());
        $this->assertNull($finder->find('index.html'));
        $this->assertEquals(__DIR__ . '/data/themes/default/templates/base.html', $finder->find('base.html'));
    }

    public function testAppTemplateFinder()
    {
        $finder = new AppTemplateFinder(['basePath' => __DIR__ . '/data/Modules']);
        $this->assertEquals([
            __DIR__ . '/data/Modules/Core/templates'
        ], $finder->getPaths());
        $this->assertNull($finder->find('index.html'));
        $this->assertEquals(__DIR__ . '/data/Modules/Core/templates/core/base.html', $finder->find('core/base.html'));
    }
}