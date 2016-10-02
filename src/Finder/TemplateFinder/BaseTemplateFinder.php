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
 * Class BaseTemplateFinder
 * @package Mindy\Finder\TemplateFinder
 */
abstract class BaseTemplateFinder implements TemplateFinderInterface
{
    /**
     * @param $templatePath
     * @return null|string absolute path of template if founded
     */
    abstract public function find($templatePath);

    /**
     * @return array of available template paths
     */
    abstract public function getPaths();
}
