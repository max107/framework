<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 18:39
 */

namespace Mindy\Http\Response;

class RedirectResponse extends Response
{
    public function __construct(
        $status = 302,
        array $headers = [],
        $body = null,
        $version = '1.1',
        $reason = null
    )
    {
        parent::__construct($status, array_merge(['Location' => (string)$body], $headers), null, $version, $reason);
    }
}