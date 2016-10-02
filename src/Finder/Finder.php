<?php
/**
 * Author: Falaleev Maxim
 * Email: max@studio107.ru
 * Company: http://en.studio107.ru
 * Date: 17/03/16
 * Time: 13:53
 */

namespace Mindy\Finder;

use Exception;
use Mindy\Finder\TemplateFinder\TemplateFinderInterface;
use Mindy\Creator\Creator;

/**
 * Class Finder
 * @package Mindy\Finder
 */
class Finder
{
    /**
     * Template finders
     * @var \Mindy\Finder\TemplateFinder\TemplateFinderInterface[]
     */
    private $_finders = [];
    /**
     * @var array of string
     */
    private $_paths = [];

    /**
     * Finder constructor.
     * @param array $finders
     */
    public function __construct(array $finders = [])
    {
        $this->setFinders($finders);
    }

    /**
     * @param array $finders
     * @throws Exception
     */
    public function setFinders(array $finders = [])
    {
        foreach ($finders as $config) {
            if (is_object($config)) {
                $finder = $config;
            } else {
                $finder = Creator::createObject($config);
            }

            if (($finder instanceof TemplateFinderInterface) === false) {
                throw new Exception("Unknown template finder");
            }

            $this->_finders[] = $finder;
        }
    }

    /**
     * @param $templatePath
     * @return mixed
     */
    public function find($templatePath)
    {
        /** @var \Mindy\Template\Finder\ITemplateFinder $finder */
        $templates = [];
        foreach ($this->_finders as $finder) {
            $template = $finder->find($templatePath);
            if ($template !== null) {
                $templates[] = $template;
            }
        }
        return array_shift($templates);
    }

    /**
     * @return array of string
     */
    public function getPaths()
    {
        if (empty($this->_paths)) {
            foreach ($this->_finders as $finder) {
                $this->_paths = array_merge($this->_paths, $finder->getPaths());
            }
        }
        return $this->_paths;
    }

    /**
     * @param array $paths
     * @param string $template
     * @return mixed|null
     */
    public function findIn(array $paths)
    {
        foreach ($paths as $path) {
            if ($this->find($path)) {
                return $path;
            }
        }

        return null;
    }
}
