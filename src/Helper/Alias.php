<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 12/05/14.05.2014 18:25
 */

declare(strict_types=1);

namespace Mindy\Helper;

use InvalidArgumentException;

/**
 * Class Alias
 * @package Mindy\Helper
 */
class Alias
{
    /**
     * @var array
     */
    private static $_aliases = [];

    /**
     * @param array $aliases
     */
    public static function fromMap(array $aliases)
    {
        foreach ($aliases as $name => $alias) {
            if ($path = self::get($alias)) {
                self::set($name, $path);
            } else {
                self::set($name, $alias);
            }
        }
    }

    /**
     * @return array
     */
    public static function all()
    {
        return self::$_aliases;
    }

    /**
     * Translates an alias into a file path.
     * Note, this method does not ensure the existence of the resulting file path.
     * It only checks if the root alias is valid or not.
     * @param string $alias alias (e.g. system.web.CController)
     * @throws \InvalidArgumentException
     * @return mixed file path corresponding to the alias, false if the alias is invalid.
     */
    public static function get(string $alias)
    {
        $parts = explode('.', $alias);
        $root = array_shift($parts);
        if (isset(self::$_aliases[$root])) {
            $path = count($parts) > 0 ? DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $parts) : '';
            return self::$_aliases[$root] . $path;
        }
        return null;
    }

    /**
     * Create a path alias.
     * Note, this method neither checks the existence of the path nor normalizes the path.
     * @param string $alias alias to the path
     * @param string $path the path corresponding to the alias. If this is null, the corresponding
     * path alias will be removed.
     */
    public static function set(string $alias, string $path)
    {
        self::$_aliases[$alias] = rtrim($path, '\\/');
    }

    /**
     * @param string $alias
     */
    public static function remove(string $alias)
    {
        unset(self::$_aliases[$alias]);
    }

    /**
     * Remove all aliases
     */
    public static function clear()
    {
        self::$_aliases = [];
    }

    /**
     * @param $aliases
     */
    public static function replace($aliases)
    {
        self::$_aliases = $aliases;
    }
}
