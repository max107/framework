<?php

declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: max
 * Date: 05.08.16
 * Time: 19:56
 */

namespace Mindy\Http;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest as ServerRequestGuzzle;
use InvalidArgumentException;
use Mindy\Orm\Files\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

class Request extends ServerRequestGuzzle
{
    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     */
    public static function fromGlobals()
    {
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        $uri = self::getUriFromGlobals();
        $body = new LazyOpenStream('php://input', 'r+');
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $serverRequest = new self($method, $uri, getallheaders(), $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * Return an UploadedFile instance array.
     *
     * @param array $files A array which respect $_FILES structure
     * @throws InvalidArgumentException for unrecognized values
     * @return array
     */
    public static function normalizeFiles(array $files)
    {
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFileFromArray($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
                continue;
            } else {
                throw new InvalidArgumentException('Invalid value in files specification');
            }
        }

        return $normalized;
    }

    /**
     * Create and return an UploadedFile instance from a $_FILES specification.
     *
     * If the specification represents an array of values, this method will
     * delegate to normalizeNestedFileSpec() and return that return value.
     *
     * @param array $value $_FILES struct
     * @return array|UploadedFileInterface
     */
    protected static function createUploadedFileFromArray(array $value)
    {
        if (is_array($value['tmp_name'])) {
            return self::normalizeNestedFileArray($value);
        }

        return new UploadedFile(
            $value['tmp_name'],
            (int) $value['size'],
            (int) $value['error'],
            $value['name'],
            $value['type']
        );
    }

    /**
     * Normalize an array of file specifications.
     *
     * Loops through all nested files and returns a normalized array of
     * UploadedFileInterface instances.
     *
     * @param array $files
     * @return UploadedFileInterface[]
     */
    private static function normalizeNestedFileArray(array $files = [])
    {
        $normalizedFiles = [];

        foreach (array_keys($files['tmp_name']) as $key) {
            $spec = [
                'tmp_name' => $files['tmp_name'][$key],
                'size'     => $files['size'][$key],
                'error'    => $files['error'][$key],
                'name'     => $files['name'][$key],
                'type'     => $files['type'][$key],
            ];
            $normalizedFiles[$key] = self::createUploadedFileFromArray($spec);
        }

        return $normalizedFiles;
    }

    /**
     * @return bool
     */
    public function isXhr() : bool
    {
        return $this->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Returns the user IP address.
     * @return string user IP address
     */
    public function getUserHostAddress() : string
    {
        $server = $this->getServerParams();
        return isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '127.0.0.1';
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function getParsedBodyParam($name, $defaultValue = null)
    {
        $params = $this->getParsedBody();
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function getCookieParam($name, $defaultValue = null)
    {
        $params = $this->getCookieParams();
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * @param $name
     * @param null $defaultValue
     * @return mixed
     */
    public function getServerParam($name, $defaultValue = null)
    {
        $params = $this->getServerParams();
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getUploadedFile($name)
    {
        $params = $this->getUploadedFiles();
        return isset($params[$name]) ? $params[$name] : null;
    }
}
