<?php
/**
 * Created by IntelliJ IDEA.
 * User: max
 * Date: 28/04/16
 * Time: 13:22
 */

namespace Modules\Admin;

use Exception;
use function Mindy\app;
use Mindy\Controllers\Controller;

abstract class BaseAdmin extends Controller
{
    /**
     * @var string field name for block update, delete actions per object
     */
    public $lockField = 'is_locked';

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
    public function getTemplate($view, $throw = true)
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

    /**
     * @param $view
     * @param array $data
     * @return mixed
     */
    public function render($view, array $data = [])
    {
        return parent::render($view, array_merge($data, [
            'module' => $this->getModule(),
            'admin' => $this
        ]));
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
     * Shortcut for generate admin urls
     * @param $action
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