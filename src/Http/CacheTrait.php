<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 06/09/16
 * Time: 12:11
 */

namespace Mindy\Http;

use Micheh\Cache\CacheUtil;

/**
 * Class CacheTrait
 * @package Mindy\Http
 */
trait CacheTrait
{
    /**
     * @var CacheUtil
     */
    private $_cache_util;

    /**
     * @return CacheUtil
     */
    protected function getCacheUtil() : CacheUtil
    {
        if ($this->_cache_util === null) {
            $this->_cache_util = new CacheUtil();
        }
        return $this->_cache_util;
    }

    /**
     * @param $tag
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withETag($tag)
    {
        return $this->getCacheUtil()->withETag($this, $tag);
    }

    /**
     * @param bool $public
     * @param int $maxAge
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withCache($public = false, $maxAge = 600)
    {
        return $this->getCacheUtil()->withCache($this, $public, $maxAge);
    }

    /**
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withCachePrevention()
    {
        return $this->getCacheUtil()->withCachePrevention($this);
    }

    /**
     * @param $time
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function withLastModified($time)
    {
        return $this->getCacheUtil()->withLastModified($this, $time);
    }
}