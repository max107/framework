<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 20:38
 */

use Mindy\Helper\Dumper;

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

function d()
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
    Dumper::dump($data);
    die();
}

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