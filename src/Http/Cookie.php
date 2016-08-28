<?php

namespace Mindy\Http;

/**
 * Class Cookie
 * @package Mindy\Http
 */
class Cookie
{
    private $name;
    private $value;
    private $expires = 0;
    private $maxAge = 0;
    private $path = '/';
    private $domain;
    private $secure = false;
    private $httpOnly = false;

    /**
     * Cookie constructor.
     * @param $name
     * @param null $value
     * @param array $options
     */
    public function __construct($name, $value = null, array $options = [])
    {
        $this->name = $name;
        $this->value = $value;
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->getHeaderValue();
    }

    /**
     * @return string
     */
    public function getHeaderValue() : string
    {
        $cookieStringParts = [
            urlencode($this->name) . '=' . urlencode($this->value),
        ];
        if ($this->domain) {
            $cookieStringParts[] = sprintf("Domain=%s", $this->domain);
        }
        if ($this->path) {
            $cookieStringParts[] = sprintf("Path=%s", $this->path);
        }
        if ($this->expires) {
            $cookieStringParts[] = sprintf("Expires=%s", gmdate('D, d M Y H:i:s T', $this->expires));
        }
        if ($this->maxAge) {
            $cookieStringParts[] = sprintf("Max-Age=%s", $this->maxAge);
        }
        if ($this->secure) {
            $cookieStringParts[] = 'Secure';
        }
        if ($this->httpOnly) {
            $cookieStringParts[] = 'HttpOnly';
        }
        return implode('; ', $cookieStringParts);
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setExpires($expires)
    {
        $this->expires = $expires;
        return $this;
    }

    public function getExpires()
    {
        return $this->expires;
    }

    public function setMaxAge($maxAge)
    {
        $this->maxAge = $maxAge;
        return $this;
    }

    public function getMaxAge()
    {
        return $this->maxAge;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setHttpOnly($httpOnly)
    {
        $this->httpOnly = $httpOnly;
        return $this;
    }

    public function getHttpOnly()
    {
        return $this->httpOnly;
    }

    public function setSecure($secure)
    {
        $this->secure = $secure;
        return $this;
    }

    public function getSecure()
    {
        return $this->secure;
    }
}