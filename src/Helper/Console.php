<?php

namespace Mindy\Helper;

/**
 * Class Console
 * @package Mindy\Helper
 */
class Console
{
    const FOREGROUND_BLACK = '0;30';
    const FOREGROUND_DARK_GRAY = '1;30';
    const FOREGROUND_BLUE = '0;34';
    const FOREGROUND_LIGHT_BLUE = '1;34';
    const FOREGROUND_GREEN = '0;32';
    const FOREGROUND_LIGHT_GREEN = '1;32';
    const FOREGROUND_CYAN = '0;36';
    const FOREGROUND_LIGHT_CYAN = '1;36';
    const FOREGROUND_RED = '0;31';
    const FOREGROUND_LIGHT_RED = '1;31';
    const FOREGROUND_PURPLE = '0;35';
    const FOREGROUND_LIGHT_PURPLE = '1;35';
    const FOREGROUND_BROWN = '0;33';
    const FOREGROUND_YELLOW = '1;33';
    const FOREGROUND_LIGHT_GRAY = '0;37';
    const FOREGROUND_WHITE = '1;37';

    const BACKGROUND_BLACK = '40';
    const BACKGROUND_RED = '41';
    const BACKGROUND_GREEN = '42';
    const BACKGROUND_YELLOW = '43';
    const BACKGROUND_BLUE = '44';
    const BACKGROUND_MAGENTA = '45';
    const BACKGROUND_CYAN = '46';
    const BACKGROUND_LIGHT_GRAY = '47';

    /**
     * @return bool
     */
    public static function isCli()
    {
        return php_sapi_name() === 'cli';
    }

    /**
     * Returns colored string
     * @param $string
     * @param null $foregroundColor
     * @param null $backgroundColor
     * @return string colored string
     */
    public static function color($string, $foregroundColor = null, $backgroundColor = null)
    {
        $coloredString = "";
        // Check if given foreground color found
        if ($foregroundColor) {
            $coloredString .= "\033[" . $foregroundColor . "m";
        }
        if ($backgroundColor) {
            $coloredString .= "\033[" . $backgroundColor . "m";
        }
        return $coloredString . $string . "\033[0m";
    }

    /**
     * Asks user to confirm by typing y or n.
     *
     * @param string $message to echo out before waiting for user input
     * @param boolean $default this value is returned if no selection is made. This parameter has been available since version 1.1.11.
     * @return boolean whether user confirmed
     *
     * @since 1.1.9
     */
    public static function confirm($message, $default = false)
    {
        echo $message . ' (yes|no) [' . ($default ? 'yes' : 'no') . ']:';

        $input = trim(fgets(STDIN));
        return empty($input) ? $default : !strncasecmp($input, 'y', 1);
    }

    /**
     * Reads input via the readline PHP extension if that's available, or fgets() if readline is not installed.
     *
     * @param string $message to echo out before waiting for user input
     * @param string $default the default string to be returned when user does not write anything.
     * Defaults to null, means that default string is disabled. This parameter is available since version 1.1.11.
     * @return mixed line read as a string, or false if input has been closed
     *
     * @since 1.1.9
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
