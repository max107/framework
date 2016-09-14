<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 14/09/16
 * Time: 16:55
 */

namespace Mindy\Permissions\PermissionProvider;

/**
 * Class FilePermissionProvider
 * @package Mindy\Permissions\PermissionProvider
 */
class FilePermissionProvider extends AbstractPermissionProvider
{
    /**
     * @var string
     */
    protected $path;

    /**
     * FilePermissionProvider constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path;
    }

    /*
     * @return array
     */
    public function load() : array
    {
        return require $this->path;
    }
}