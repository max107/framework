<?php

namespace Mindy\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 16:20
 */

function isEmptyResponse(ResponseInterface $response)
{
    if (method_exists($response, 'isEmpty')) {
        return $response->isEmpty();
    }
    return in_array($response->getStatusCode(), [204, 205, 304]);
}

function sendResponse(ResponseInterface $response)
{
    // Send response
    if (!headers_sent()) {
        // Status
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
        // Headers
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
    }

    // Body
    if (!isEmptyResponse($response)) {
        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        $chunkSize = $this->getSettings()['responseChunkSize'];
        $contentLength = $response->getHeaderLine('Content-Length');
        if (!$contentLength) {
            $contentLength = $body->getSize();
        }
        if (isset($contentLength)) {
            $amountToRead = $contentLength;
            while ($amountToRead > 0 && !$body->eof()) {
                $data = $body->read(min($chunkSize, $amountToRead));
                echo $data;

                $amountToRead -= strlen($data);

                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        } else {
            while (!$body->eof()) {
                echo $body->read($chunkSize);
                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }
            }
        }
    }
}