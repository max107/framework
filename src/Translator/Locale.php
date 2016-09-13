<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 14:20
 */

namespace Mindy\Translator;

use Mindy\Helper\Creator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;

class Locale
{
    /**
     * @var string
     */
    public $modulesPath = '';
    /**
     * @var string
     */
    public $locale = 'en_US';
    /**
     * @var Translator
     */
    protected $translator;
    /**
     * @var array|[]LoaderInterface
     */
    protected $loaders;

    /**
     * Locale constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        if (isset($config['modulesPath'])) {
            $this->modulesPath = $config['modulesPath'];
        } else {
            throw new \Exception('Missing modulesPath');
        }

        if (isset($config['locale'])) {
            $this->locale = $config['locale'];
        }

        if (!isset($config['loaders'])) {
            $config['loaders'] = ['php' => new PhpFileLoader];
        }

        $loaders = [];
        foreach ($config['loaders'] as $prefix => $config) {
            if (($config instanceof LoaderInterface) === false) {
                $config = Creator::createObject($config);
            }

            $loaders[$prefix] = $config;
        }

        $this->loaders = $loaders;
        $this->translator = $this->createTranslator($loaders);
    }

    /**
     * @param array $loaders
     * @return Translator
     */
    protected function createTranslator(array $loaders) : Translator
    {
        $translator = new Translator($this->locale, new MessageSelector());
        foreach ($loaders as $prefix => $loader) {
            $translator->addLoader($prefix, $loader);
        }
        $this->load($translator, $this->locale);
        return $translator;
    }

    /**
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return mixed
     */
    public function getModulesPath()
    {
        return $this->modulesPath;
    }

    /**
     * @param Translator $translator
     * @param string $language
     */
    protected function load(Translator $translator, string $language)
    {
        $modulesPath = $this->getModulesPath();

        $path = strtr('{modules}/*/messages/{language}/', [
            '{modules}' => $modulesPath,
            '{language}' => $language
        ]);

        $finder = (new Finder())
            ->files()
            ->ignoreUnreadableDirs()
            ->in($path);

        foreach ($this->loaders as $prefix => $loader) {
            $clone = clone $finder;
            foreach ($clone->name('*.' . $prefix) as $fileInfo) {
                $filePath = $fileInfo->getRealPath();
                $raw = substr($filePath, strlen($modulesPath) + 1);
                $moduleId = substr($raw, 0, strpos($raw, '/'));
                $translator->addResource($prefix, $filePath, $language, 'modules.' . $moduleId);
            }
        }
    }

    /**
     * @param $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function t($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @param $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->getTranslator()->trans($id, $this->formatParameters($parameters), $domain, $locale);
    }

    /**
     * @param $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
    {
        return $this->getTranslator()->transChoice($id, $number, $this->formatParameters($parameters), $domain, $locale);
    }

    /**
     * @param array $params
     * @return array
     */
    protected function formatParameters(array $params = []) : array
    {
        $newParams = [];
        foreach ($params as $key => $value) {
            if (strpos($key, '%') === false) {
                $newParams['%' . $key . '%'] = $value;
            } else {
                $newParams[$key] = $value;
            }
        }
        return $newParams;
    }
}