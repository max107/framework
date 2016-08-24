<?php

namespace Mindy\Helper;

/**
 * Class Text
 * @package Mindy\Helper
 */
class Text
{
    /**
     * Returns given word as CamelCased
     * Converts a word like "send_email" to "SendEmail". It
     * will remove non alphanumeric character from the word, so
     * "who's online" will be converted to "WhoSOnline"
     * @see variablize()
     * @param string $word the word to CamelCase
     * @return string
     */
    public static function toCamelCase($word)
    {
        return lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^A-Za-z0-9]+/', ' ', $word))));
    }

    /**
     * Converts any "CamelCased" into an "underscored_word".
     * @param string $words the word(s) to underscore
     * @return string
     */
    public static function toUnderscore($words)
    {
        return strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $words));
    }
}
