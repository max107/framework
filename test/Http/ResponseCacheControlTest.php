<?php
/**
 * PSR-7 Cache Helpers
 *
 * @copyright Copyright (c) 2016, Michel Hunziker <php@michelhunziker.com>
 * @license http://www.opensource.org/licenses/BSD-3-Clause The BSD-3-Clause License
 */

namespace Mindy\Tests\Http;

use Mindy\Http\Cache\ResponseCacheControl;

class ResponseCacheControlTest extends CacheControlTestCase
{
    /**
     * @var ResponseCacheControl
     */
    protected $cacheControl;

    /**
     * @var string
     */
    protected $controlClass = 'Mindy\Http\Cache\ResponseCacheControl';

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::fromString
     */
    public function testFromString()
    {
        $control = ResponseCacheControl::fromString('max-age=100');
        $this->assertInstanceOf($this->controlClass, $control);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublic
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublicPrivate
     */
    public function testWithPublic()
    {
        $clone = $this->cacheControl->withPublic();
        $this->assertAttributeSame(['public' => true], 'directives', $clone);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPrivate
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublicPrivate
     */
    public function testWithPrivate()
    {
        $clone = $this->cacheControl->withPrivate();
        $this->assertAttributeSame(['private' => true], 'directives', $clone);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublic
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPrivate
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublicPrivate
     */
    public function testWithPublicOverridesPrivate()
    {
        $clone = $this->cacheControl->withPrivate()->withPublic();
        $this->assertAttributeSame(['public' => true], 'directives', $clone);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublic
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPrivate
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublicPrivate
     */
    public function testWithPrivateOverridesPublic()
    {
        $clone = $this->cacheControl->withPublic()->withPrivate();
        $this->assertAttributeSame(['private' => true], 'directives', $clone);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublic
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPrivate
     * @covers Mindy\Http\Cache\ResponseCacheControl::withPublicPrivate
     */
    public function testWithPublicDoesNotOverwriteFalse()
    {
        $clone = $this->cacheControl->withPrivate()->withPublic(false);
        $this->assertAttributeSame(['private' => true], 'directives', $clone);
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::isPublic
     */
    public function testIsPublic()
    {
        $control = $this->getControlWithHasFlag('public');
        $this->assertReturn($control->isPublic());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::isPrivate
     */
    public function testIsPrivate()
    {
        $control = $this->getControlWithHasFlag('private');
        $this->assertReturn($control->isPrivate());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withSharedMaxAge
     */
    public function testWithSharedMaxAge()
    {
        $control = $this->getControlWithDirective('s-maxage', 10);
        $this->assertReturn($control->withSharedMaxAge(10));
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getSharedMaxAge
     */
    public function testGetSharedMaxAge()
    {
        $control = $this->getControlWithGetDirective('s-maxage');
        $this->assertReturn($control->getSharedMaxAge());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getLifetime
     */
    public function testGetLifetimeWithNormal()
    {
        $control = $this->cacheControl->withMaxAge(20);
        $this->assertSame(20, $control->getLifetime());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getLifetime
     */
    public function testGetLifetimeWithShared()
    {
        $control = $this->cacheControl->withSharedMaxAge(60);
        $this->assertSame(60, $control->getLifetime());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getLifetime
     */
    public function testGetLifetimeWithBoth()
    {
        $control = $this->cacheControl->withSharedMaxAge(60)->withMaxAge(20);
        $this->assertSame(60, $control->getLifetime());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getLifetime
     */
    public function testGetLifetimeWithoutDirective()
    {
        $this->assertNull($this->cacheControl->getLifetime());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withStaleWhileRevalidate
     */
    public function testWithStaleWhileRevalidate()
    {
        $control = $this->getControlWithDirective('stale-while-revalidate', 10);
        $this->assertReturn($control->withStaleWhileRevalidate(10));
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getStaleWhileRevalidate
     */
    public function testGetStaleWhileRevalidate()
    {
        $control = $this->getControlWithGetDirective('stale-while-revalidate');
        $this->assertReturn($control->getStaleWhileRevalidate());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withStaleIfError
     */
    public function testWithStaleIfError()
    {
        $control = $this->getControlWithDirective('stale-if-error', 10);
        $this->assertReturn($control->withStaleIfError(10));
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::getStaleIfError
     */
    public function testGetStaleIfError()
    {
        $control = $this->getControlWithGetDirective('stale-if-error');
        $this->assertReturn($control->getStaleIfError());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withMustRevalidate
     */
    public function testWithMustRevalidate()
    {
        $control = $this->getControlWithDirective('must-revalidate', true);
        $this->assertReturn($control->withMustRevalidate(true));
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::hasMustRevalidate
     */
    public function testHasMustRevalidate()
    {
        $control = $this->getControlWithHasFlag('must-revalidate');
        $this->assertReturn($control->hasMustRevalidate());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withProxyRevalidate
     */
    public function testWithProxyRevalidate()
    {
        $control = $this->getControlWithDirective('proxy-revalidate', true);
        $this->assertReturn($control->withProxyRevalidate(true));
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::hasProxyRevalidate
     */
    public function testHasProxyRevalidate()
    {
        $control = $this->getControlWithHasFlag('proxy-revalidate');
        $this->assertReturn($control->hasProxyRevalidate());
    }

    /**
     * @covers Mindy\Http\Cache\ResponseCacheControl::withCachePrevention
     */
    public function testWithCachePrevention()
    {
        $control = $this->cacheControl->withCachePrevention();
        $directives = ['no-cache' => true, 'no-store' => true, 'must-revalidate' => true];

        $this->assertAttributeSame($directives, 'directives', $control);
    }
}
