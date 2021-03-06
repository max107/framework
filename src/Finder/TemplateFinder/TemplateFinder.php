<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 17/03/16
 * Time: 13:53
 */

namespace Mindy\Finder\TemplateFinder;

/**
 * Class TemplateFinder
 * @package Mindy\Finder
 */
class TemplateFinder extends BaseTemplateFinder
{
    /**
     * @var string
     */
    public $basePath;
    /**
     * @var string
     */
    public $templatesDir = 'templates';

    /**
     * TemplateFinder constructor.
     * @param string $basePath
     */
    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    public function find($templatePath)
    {
        $path = implode(DIRECTORY_SEPARATOR, [$this->basePath, $this->templatesDir, $templatePath]);
        return is_file($path) ? $path : null;
    }

    /**
     * @return array of available template paths
     */
    public function getPaths()
    {
        return [
            implode(DIRECTORY_SEPARATOR, [$this->basePath, $this->templatesDir])
        ];
    }
}
