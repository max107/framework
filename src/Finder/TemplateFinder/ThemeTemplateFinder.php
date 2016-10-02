<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 17/03/16
 * Time: 13:53
 */

namespace Mindy\Finder\TemplateFinder;

use Closure;

/**
 * Class ThemeTemplateFinder
 * @package Mindy\Finder
 */
class ThemeTemplateFinder extends TemplateFinder
{
    public $basePath;
    /**
     * @var string
     */
    public $theme = 'default';

    /**
     * ThemeTemplateFinder constructor.
     * @param string $basePath
     * @param $theme
     */
    public function __construct(string $basePath, $theme)
    {
        parent::__construct($basePath);
        $this->theme = $theme instanceof Closure ? $theme->__invoke() : $theme;
    }

    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    public function find($templatePath)
    {
        $path = join(DIRECTORY_SEPARATOR, [$this->basePath, 'themes', $this->theme, $this->templatesDir, $templatePath]);
        if (is_file($path)) {
            return $path;
        }

        return null;
    }

    /**
     * @return array of available template paths
     */
    public function getPaths()
    {
        return [
            join(DIRECTORY_SEPARATOR, [$this->basePath, 'themes', $this->getTheme(), $this->templatesDir])
        ];
    }

    /**
     * @return string
     */
    public function getTheme()
    {
        $theme = $this->theme;
        if ($theme instanceof Closure) {
            $theme = $theme->__invoke();
        }
        return $theme;
    }
}
