<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/09/16
 * Time: 12:01
 */

namespace Mindy\Tests\Permission;

use Mindy\Auth\UserInterface;

class RuleUser implements UserInterface
{
    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = array_merge(['id' => null], $attributes);
    }

    public function __get($name)
    {
        return $this->attributes[$name] ?? null;
    }

    /**
     * @return bool
     */
    public function isGuest() : bool
    {
        return empty($this->attributes['id']);
    }

    /**
     * @return array
     */
    public function getSafeAttributes() : array
    {
        return [];
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public static function create(array $attributes) : UserInterface
    {
        return new self($attributes);
    }
}