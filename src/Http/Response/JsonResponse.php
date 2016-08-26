<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 16:43
 */

namespace Mindy\Http\Response;

use Mindy\Helper\Json;

class JsonResponse extends Response
{
    public function __construct(
        $status = 200,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    )
    {
        if (is_string($body) === false) {
            $body = Json::encode($body);
        }
        parent::__construct($status, array_merge(['Content-Type' => 'application/json'], $headers), $body, $version, $reason);
    }
}