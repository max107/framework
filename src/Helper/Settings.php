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
                if (is_array($value)) {
                    $original[$key][$k] = array_merge($original[$key][$k], $value);
                } else {
                    $original[$key][$k] = $value;
                }
            }
        }
        return $original;
    }
}
