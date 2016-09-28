<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 14:20
 */

namespace Mindy\Translator;

use Mindy\Creator\Creator;
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
     * @var string
     */
    protected $defaultLoader = 'php';

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
        $this->loadFramework($translator, $this->locale);
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
    protected function loadFramework(Translator $translator, string $language)
    {
        if ($path = realpath(__DIR__ . '/../messages/' . $language)) {
            foreach (glob($path . '/*.' . $this->defaultLoader) as $filePath) {
                $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                $basename = basename($filePath);
                $name = substr($basename, 0, strpos($basename, $ext) - 1);
                $translator->addResource($this->defaultLoader, $filePath, $language, 'framework.' . $name);
            }
        }
    }

    /**
     * @param Translator $translator
     * @param string $language
     */
    protected function load(Translator $translator, string $language)
    {
        $modulesPath = $this->getModulesPath();
        $path = '{modules}/*/messages/{language}/*.{prefix}';
        foreach ($this->loaders as $prefix => $loader) {
            $params = [
                '{modules}' => $modulesPath,
                '{language}' => $language,
                '{prefix}' => $prefix
            ];

            foreach (glob(strtr($path, $params)) as $filePath) {
                $raw = substr($filePath, strlen($modulesPath) + 1);
                $moduleId = substr($raw, 0, strpos($raw, '/'));

                $ext = pathinfo($filePath, PATHINFO_EXTENSION);
                $basename = basename($filePath);
                $name = substr($basename, 0, strpos($basename, $ext) - 1);

                $translator->addResource($prefix, $filePath, $language, sprintf('modules.%s.%s', $moduleId, $name));
            }
        }
    }

    /**
     * @param string $message
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function t($domain, $message, array $parameters = [], $locale = null) : string
    {
        return $this->trans($message, $parameters, $domain, $locale);
    }

    /**
     * @param $id
     * @param array $parameters
     * @param null $domain
     * @param null $locale
     * @return string
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null) : string
    {
        return $this->getTranslator()->trans($id, $this->formatParameters($parameters), $domain, $locale);
    }

    /**
     * @param null $domain
     * @param $id
     * @param $number
     * @param array $parameters
     * @param null $locale
     * @return string
     */
    public function transChoice($domain, $id, $number, array $parameters = [], $locale = null)
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