<?php

namespace Mindy\Http;

class Cookie
{
    private $name;
    private $value;
    private $expires = 0;
    private $maxAge = 0;
    private $path;
    private $domain;
    private $secure = false;
    private $httpOnly = false;

    public function __construct($name, $value = null, array $options = [])
    {
        $this->name = $name;
        $this->value = $value;
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function __toString()
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
}