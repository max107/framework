<?php

namespace Mindy\Base;

use function Mindy\app;
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
     * @return \Mindy\Orm\Model[]
     */
    public function getModels()
    {
        $modelsPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'Models';
        if (is_dir($modelsPath) === false) {
            return [];
        }

        $finder = new Finder();
        $files = $finder->ignoreUnreadableDirs()->files()->in($modelsPath)->name('*.php');
        if (count($files) === 0) {
            return [];
        }

        $classes = [];
        foreach ($files as $fileInfo) {
            $fileName = str_replace('.' . $fileInfo->getExtension(), '', $fileInfo->getFilename());
            $classes[] = sprintf('Modules\%s\Models\%s', $this->getId(), $fileName);
        }

        $models = [];
        foreach ($classes as $cls) {
            if (is_a($cls, '\Mindy\Orm\Model', true) === false) {
                continue;
            }

            $reflectClass = new ReflectionClass($cls);
            if ($reflectClass->isAbstract()) {
                continue;
            }

            $model = new $cls;
            if ($model instanceof \Mindy\Orm\Model && $model->tableName()) {
                $models[$cls] = $model;
            }
        }

        return $models;
    }

    public function reverse($route, $data = null)
    {
        return app()->urlManager->reverse($route, $data);
    }

    public function getAdminMenu()
    {
        return [];
    }
}
