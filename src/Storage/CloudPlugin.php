<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 12/09/16
 * Time: 15:18
 */

namespace Mindy\Storage;

use League\Flysystem\Plugin\AbstractPlugin;

class CloudPlugin extends AbstractPlugin
{
    /**
     * @var string
     */
    protected $baseUrl = '';

    /**
     * CloudPlugin constructor.
     * @param $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'url';
    }

    /**
     * @param null $path
     * @param array $params
     * @return string
     */
    public function handle($path = null, array $params = [])
    {
        $rawPath = str_replace('\\', '/', $path);
        if (method_exists($this->filesystem->getAdapter(), 'getClient')) {
            $client = $this->filesystem->getAdapter()->getClient();
            return $client->url($rawPath, $params);
        }

        return $this->baseUrl . $rawPath;
    }
}