<?php

namespace Mindy\Helper;

/**
 * Class Console
 * @package Mindy\Helper
 */
class Console
{
    /**
     * @DEPRECATED
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * @DEPRECATED
     * @param $message
     * @param bool $default
     * @return bool
     */
    public static function confirm($message, $default = false)
    {
        echo $message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:';

        $input = trim(fgets(STDIN));
        return empty($input) ? $default : !strncasecmp($input, 'y', 1);
    }

    /**
     * @DEPRECATED
     * @param $message
     * @param null $default
     * @return bool|null|string
     */
    public static function prompt($message, $default = null)
    {
        if ($default !== null) {
            $message .= " [$default] ";
        } else {
            $message .= ' ';
        }

        if (extension_loaded('readline')) {
            $input = readline($message);
            if ($input !== false) {
                readline_add_history($input);
            }
        } else {
            echo $message;
            $input = fgets(STDIN);
        }

        if ($input === false) {
            return false;
        } else {
            $input = trim($input);
            return ($input === '' && $default !== null) ? $default : $input;
        }
    }
}
