<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 20:38
 */

use Mindy\Helper\Dumper;
use Symfony\Component\VarDumper\VarDumper;

if (!function_exists('getallheaders')) {
    function getallheaders() : array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) <> 'HTTP_') {
                continue;
            }
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
        return $headers;
    }
}

if (!function_exists('debug')) {
    function debug()
    {
        $debug = debug_backtrace();
        $data = [
            'data' => func_get_args(),
            'debug' => [
                'file' => isset($debug[0]['file']) ? $debug[0]['file'] : null,
                'line' => isset($debug[0]['line']) ? $debug[0]['line'] : null,
            ]
        ];
        VarDumper::dump($data);
    }

    function debug_die()
    {
        debug(func_get_args());
        die();
    }
}

if (!function_exists('d')) {
    function d()
    {
        $debug = debug_backtrace();
        $data = [
            'data' => func_get_args(),
            'debug' => [
                'file' => isset($debug[0]['file']) ? $debug[0]['file'] : null,
                'line' => isset($debug[0]['line']) ? $debug[0]['line'] : null,
            ]
        ];
        Dumper::dump($data);
        die();
    }
}

if (!function_exists('dd')) {
    function dd()
    {
        $debug = debug_backtrace();
        $args = func_get_args();
        $data = [
            'data' => $args,
            'debug' => [
                'file' => isset($debug[0]['file']) ? $debug[0]['file'] : null,
                'line' => isset($debug[0]['line']) ? $debug[0]['line'] : null,
            ]
        ];
        Dumper::dump($data, 10, false);
        die();
    }
}