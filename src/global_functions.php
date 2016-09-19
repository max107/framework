<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 20:38
 */

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
