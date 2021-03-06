<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

namespace Mindy\Tests\Http;

use Mindy\Http\Cache\RequestCacheControl;

class RequestCacheControlTest extends CacheControlTestCase
{
    /**
     * @var string
     */
    protected $controlClass = 'Mindy\Http\Cache\RequestCacheControl';

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::fromString
     */
    public function testFromString()
    {
        $control = RequestCacheControl::fromString('max-age=100');
        $this->assertInstanceOf($this->controlClass, $control);
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::withMaxStale
     */
    public function testWithMaxStale()
    {
        $control = $this->getControlWithDirective('max-stale', 10);
        $this->assertReturn($control->withMaxStale(10));
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::getMaxStale
     */
    public function testGetMaxStale()
    {
        $control = $this->getControlWithGetDirective('max-stale');
        $this->assertReturn($control->getMaxStale());
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::withMinFresh
     */
    public function testWithMinFresh()
    {
        $control = $this->getControlWithDirective('min-fresh', 10);
        $this->assertReturn($control->withMinFresh(10));
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::getMinFresh
     */
    public function testGetMinFresh()
    {
        $control = $this->getControlWithGetDirective('min-fresh');
        $this->assertReturn($control->getMinFresh());
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::withOnlyIfCached
     */
    public function testWithOnlyIfCached()
    {
        $control = $this->getControlWithDirective('only-if-cached', true);
        $this->assertReturn($control->withOnlyIfCached(true));
    }

    /**
     * @covers Mindy\Http\Cache\RequestCacheControl::hasOnlyIfCached
     */
    public function testHasOnlyIfCached()
    {
        $control = $this->getControlWithHasFlag('only-if-cached');
        $this->assertReturn($control->hasOnlyIfCached());
    }
}
