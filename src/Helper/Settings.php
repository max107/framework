<?php

namespace Mindy\Helper;

/**
 * Class Settings
 * @package Mindy\Utils
 */
class Settings
{
    public static function override(array $original, array $settings)
    {
        foreach ($settings as $key => $item) {
            foreach ($item as $k => $value) {
                $original[$key][$k] = $value;
            }
        }
        return $original;
    }
}
