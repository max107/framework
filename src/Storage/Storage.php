<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 24/03/16
 * Time: 20:46
 */

namespace Mindy\Storage;

use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\MountManager;
use LogicException;

class Storage extends MountManager
{
    /**
     * @var string
     */
    public $baseUrl = '/media/';
    /**
     * @var string
     */
    public $defaultFileSystem = 'default';


    /**
     * Initialize adapters
     */
    public function __construct(array $config = [])
    {
        $filesystems = $config['filesystems'];
        unset($config['filesystems']);
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }

        $data = [];
        foreach ($filesystems as $prefix => $adapter) {
            $data[$prefix] = new Filesystem($adapter);
        }
        parent::__construct($data);
    }

    /**
     * Mount filesystems.
     * @param string $prefix
     * @param FilesystemInterface $filesystem
     * @return $this
     * @throws Exception
     */
    public function mountFilesystem($prefix, FilesystemInterface $filesystem)
    {
        if (!is_string($prefix)) {
            $prefix = $this->defaultFileSystem;
        }

        $filesystem->addPlugin(new CloudPlugin($this->baseUrl));
        $this->filesystems[$prefix] = $filesystem;
        return $this;
    }

    /**
     * Get the filesystem with the corresponding prefix.
     * @param string $prefix
     * @throws LogicException
     * @return FilesystemInterface
     */
    public function getFilesystem($prefix = null)
    {
        if (!is_string($prefix)) {
            $prefix = $this->defaultFileSystem;
        }
        if (!isset($this->filesystems[$prefix])) {
            throw new LogicException('No filesystem mounted with prefix ' . $prefix);
        }
        return $this->filesystems[$prefix];
    }

    /**
     * Retrieves the url address of file
     * @param $name
     * @return string
     */
    public function url($name)
    {
        return $this->getFilesystem()->url($name);
    }
}