<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 16:43
 */

namespace Mindy\Http\Response;

use Mindy\Helper\Xml;

class XmlResponse extends Response
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
            $body = Xml::encode('response', $body);
        }
        parent::__construct($status, array_merge(['Content-Type' => 'text/xml'], $headers), $body, $version, $reason);
    }
}