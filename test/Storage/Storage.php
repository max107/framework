<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 24/03/16
 * Time: 20:46
 */

namespace Mindy\Storage;

use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;

class Storage
{
    use Configurator, Accessors;

    /**
     * @var string
     */
    public $baseUrl = '/media/';
    /**
     * @var array
     */
    public $adapters = [];
    /**
     * @var string
     */
    public $defaultAdapter = 'default';
    /**
     * @var MountManager
     */
    private $_fs;

    /**
     * Initialize adapters
     */
    public function init()
    {
        $fs = [];
        foreach ($this->adapters as $name => $adapter) {
            $fs[$name] = new Filesystem($adapter instanceof \Closure ? $adapter->__invoke() : $adapter);
        }
        $this->_fs = new MountManager($fs);
    }

    /**
     * @param null $name
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getFileSystem($name = null)
    {
        if (!$name) {
            $name = $this->defaultAdapter;
        }
        return $this->_fs->getFilesystem($name);
    }

    /**
     * Retrieves the url address of file
     * @param $name
     * @return string
     */
    public function url($name)
    {
        return $this->baseUrl . str_replace('\\', '/', $name);
    }
}