<?php

namespace Mindy\Base;

use Mindy\Helper\Alias;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Class Module
 * @package Mindy\Base
 */
class Module extends BaseModule
{
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * @return array
     */
    public function getMenu()
    {
        return [];
    }

    /**
     * Return array of mail templates and his variables
     * @return array
     */
    public function getMailTemplates()
    {
        return [];
    }


    /**
     * @return Finder|\Symfony\Component\Finder\SplFileInfo[]
     */
    public function getConsoleCommands()
    {
        $finder = new Finder();
        return $finder
            ->files()
            ->ignoreUnreadableDirs()
            ->in($this->getBasePath() . DIRECTORY_SEPARATOR . 'Commands')
            ->name('*Command.php');
    }


    /**
     * @return \Mindy\Orm\Model[]
     */
    public function getModels()
    {
        $modelsPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Models';
        if (is_dir($modelsPath) === false) {
            return [];
        }

        $finder = new Finder();
        $files = $finder->files()->ignoreUnreadableDirs()->in($modelsPath)->name('*.php');

        $classes = [];
        foreach ($files as $fileInfo) {
            $fileName = str_replace('.' . $fileInfo->getExtension(), '', $fileInfo->getFilename());
            $classes[] = sprintf('Modules\%s\Models\%s', $this->getId(), $fileName);
        }

        $models = [];
        foreach ($classes as $cls) {
            if (is_a($cls, '\Mindy\Orm\Base') === false) {
                continue;
            }

            $reflectClass = new ReflectionClass($cls);
            if ($reflectClass->isAbstract()) {
                continue;
            }

            if (call_user_func([$cls, 'tableName'])) {
                $models[$cls] = new $cls;
            }
        }

        return $models;
    }
}
