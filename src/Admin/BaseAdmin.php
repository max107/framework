<?php
/**
 * Created by IntelliJ IDEA.
 * User: max
 * Date: 28/04/16
 * Time: 13:22
 */

namespace Mindy\Admin;

use Exception;
use function Mindy\app;
use Mindy\Base\ModuleInterface;
use Mindy\Controller\BaseController;
use Mindy\Helper\Traits\RenderTrait;
use Mindy\Orm\ModelInterface;
use ReflectionClass;

abstract class BaseAdmin extends BaseController
{
    use RenderTrait;

    /**
     * Default admin template paths for easy override
     * @var array
     */
    public $paths = [
        '{module}/admin/{admin}/{view}',
        'admin/{module}/{admin}/{view}',
        'admin/admin/{view}',
        'admin/{view}'
    ];
    /**
     * @var array
     */
    public $permissions = [
        'create' => true,
        'update' => true,
        'info' => true,
        'remove' => true
    ];
    /**
     * @var \Mindy\Base\Module|ModuleInterface
     */
    private $_module;

    /**
     * @return \Mindy\Base\Module|ModuleInterface
     */
    protected function getModule()
    {
        if ($this->_module === null) {
            $reflect = new ReflectionClass(get_class($this));
            $namespace = $reflect->getNamespaceName();
            $segments = explode('\\', $namespace);
            $this->_module = app()->getModule($segments[1]);
        }
        return $this->_module;
    }

    /**
     * @param $view
     * @param array $data
     * @return string
     */
    public function render(string $view, array $data = [])
    {
        return $this->renderTemplate($view, array_merge([
            'debug' => MINDY_DEBUG,
            'app' => app(),
            'module' => $this->getModule(),
            'admin' => $this
        ], $data));
    }

    /**
     * Init function called after construct class
     */
    public function init()
    {

    }

    /**
     * @param $code
     * @return bool
     */
    public function can($code)
    {
        $defaultPermissions = [
            'create' => true,
            'update' => true,
            'info' => true,
            'remove' => true
        ];
        $permissions = array_merge($defaultPermissions, $this->permissions);
        return isset($permissions[$code]) && $permissions[$code];
    }

    protected function normalizeString($str)
    {
        return trim(strtolower(preg_replace('/(?<![A-Z])[A-Z]/', '_\0', $str)), '_');
    }

    /**
     * @param $view
     * @param bool $throw
     * @return null|string
     * @throws Exception
     */
    public function findTemplate($view, $throw = true)
    {
        $moduleName = strtolower($this->getModule()->getId());
        $paths = array_map(function ($path) use ($moduleName, $view) {
            return strtr($path, [
                '{module}' => $moduleName,
                '{admin}' => strtolower($this->normalizeString(str_replace('Admin', '', $this->classNameShort()))),
                '{view}' => $view
            ]);
        }, $this->paths);

        $finder = app()->finder;
        foreach ($paths as $path) {
            if ($finder->find($path)) {
                return $path;
            }
        }

        if ($throw) {
            throw new Exception('Template not found: ' . $view . '. Paths: ' . implode(' ', $paths));
        }

        return null;
    }

    /**
     * @param $route
     * @param array $params
     * @return string
     */
    public function reverse($route, array $params = [])
    {
        return app()->urlManager->reverse($route, $params);
    }

    public function getOrderUrl($column)
    {
        $request = $this->getRequest();
        $data = $request->get->all();
        if (isset($data['order']) && $data['order'] == $column) {
            $column = '-' . $column;
        }
        $queryString = http_build_query(array_merge($data, ['order' => $column]));
        return strtok($request->getPath(), '?') . '?' . $queryString;
    }

    /**
     * @return string
     */
    public function classNameShort() : string
    {
        $classMap = explode('\\', get_called_class());
        return end($classMap);
    }

    /**
     * @param $name
     * @return string
     */
    public static function normalizeName($name) : string
    {
        return trim(strtolower(preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)), '_ ');
    }

    /**
     * @return string model class name
     */
    abstract public function getModelClass();

    /**
     * @param ModelInterface|null $instance
     * @return array
     */
    public function getAdminNames(ModelInterface $instance = null)
    {
        $classMap = explode('\\', $this->getModelClass());
        $name = self::normalizeName(end($classMap));

        $id = $this->getModule()->getId();
        return [
            app()->t('modules.' . $id, ucfirst($name . 's')),
            app()->t('modules.' . $id, 'Create ' . $name),
            app()->t('modules.' . $id, 'Update ' . $name . ': %name%', ['name' => (string)$instance]),
        ];
    }

    /**
     * Shortcut for generate admin urls
     * @param $action
     * @param array $params
     * @return string
     */
    public function getAdminUrl($action, array $params = [])
    {
        $url = $this->reverse('admin:action', [
            'module' => $this->getModule()->getId(),
            'admin' => $this->classNameShort(),
            'action' => $action
        ]);
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}