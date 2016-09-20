<?php

namespace Mindy\Base;

use function Mindy\app;
use Mindy\Router\ReverseAwareInterface;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Class Module
 * @package Mindy\Base
 */
class Module extends BaseModule implements ReverseAwareInterface
{
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * @return \Mindy\Orm\Model[]
     */
    public function getModels() : array
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

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return '';
    }

    /**
     * @param $route
     * @param null $data
     * @return string
     */
    public function reverse($route, $data = null) : string
    {
        return app()->urlManager->reverse($route, $data);
    }

    /**
     * @return array
     */
    public function getAdminMenu() : array
    {
        return [];
    }
}
