<?php

declare(strict_types = 1);

namespace Mindy\Controller;

use Exception;
use function GuzzleHttp\Psr7\stream_for;
use Mindy\Base\Application;
use Mindy\Base\Mindy;
use Mindy\Base\Module;
use Mindy\Controller\Action\ClosureAction;
use Mindy\Controller\Action\IAction;
use Mindy\Controller\Action\InlineAction;
use Mindy\Helper\HttpError;
use Mindy\Http\Response\Response;
use Mindy\Exception\HttpException;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use ReflectionClass;

/**
 * Class BaseController
 * @package Mindy\Controller
 */
class BaseController
{
    use Configurator, Accessors;

    private $_module;

    /**
     * @return Application
     * @throws \Mindy\Exception\Exception
     */
    protected function getApp() : Application
    {
        return Mindy::app();
    }

    /**
     * @param $code
     * @param null $message
     * @throws HttpException
     */
    public function error($code, $message = null)
    {
        $body = stream_for($message === null ? HttpError::errorMessage($code) : $message);
        $response = (new Response($code))->withBody($body);
        $this->getRequest()->send($response);
    }

    protected function getRequest()
    {
        return Mindy::app()->http;
    }

    /**
     * return a list of external action classes
     * ['viewUser' => ['class' => ViewUserAction::class, 'foo' => 'bar'];
     * @return array
     */
    public function actions()
    {
        return [];
    }

    protected function beforeAction($actionID, $params = [])
    {

    }

    protected function afterAction($actionID, $params = [], $out)
    {

    }

    /**
     * @param $actionID
     * @param array $params
     * @return null|\Psr\Http\Message\ResponseInterface|string|void
     * @throws HttpException
     */
    public function run($actionID, $params = [])
    {
        $action = $this->createAction($actionID);
        if ($action) {
            $this->beforeAction(lcfirst($actionID), $params);
            $out = $action->runInternal($params);
            if ($out === false) {
                throw new HttpException(400, HttpError::errorMessage(400) . ': ' . $action->getId());
            }
            $this->afterAction(lcfirst($actionID), $params, $out);
            return $out;
        }

        throw new HttpException(404, 'The system is unable to find the requested action "' . $actionID . '"');
    }

    /**
     * @param $id
     * @return bool
     */
    public function hasAction($id)
    {
        return $this->hasInlineAction($id) || $this->hasExternalAction($id);
    }

    /**
     * @param $id
     * @return bool
     */
    protected function hasExternalAction($id)
    {
        return array_key_exists($id, $this->actions());
    }

    /**
     * @param $id
     * @return bool
     */
    protected function hasInlineAction($id)
    {
        // we have actions method
        return method_exists($this, 'action' . $id) && strcasecmp($id, 's');
    }

    /**
     * Creates the action instance based on the action name.
     * The action can be either an inline action or an object.
     * The latter is created by looking up the action map specified in {@link actions}.
     * @param string $actionID ID of the action.
     * @return IAction
     * @throws Exception
     * @see actions
     */
    public function createAction($actionID)
    {
        if ($this->hasInlineAction(ucfirst($actionID))) {
            return new InlineAction($this, ucfirst($actionID));
        } else if ($this->hasExternalAction($actionID)) {
            $actions = $this->actions();
            if ($actions[$actionID] instanceof \Closure) {
                return new ClosureAction($actions[$actionID]);
            } else {
                $config = is_array($actions[$actionID]) ? $actions[$actionID] : ['class' => $actions[$actionID]];
                return Creator::createObject($config, $this, $actionID);
            }
        } else {
            return null;
        }
    }

    /**
     * @return \Mindy\Base\Module the module that this controller belongs to. It returns null
     * if the controller does not belong to any module
     */
    protected function getModule()
    {
        if ($this->_module === null) {
            $reflect = new ReflectionClass(get_class($this));
            $namespace = $reflect->getNamespaceName();
            $segments = explode('\\', $namespace);
            $this->_module = $this->getApp()->getModule($segments[1]);
        }
        return $this->_module;
    }

    /**
     * Forward controller action to another controller action
     * @param $controllerClass
     * @param $action
     * @param $params
     * @param $module
     */
    public function forward($controllerClass, $action, $params, $module)
    {
        if (($module instanceof Module) == false) {
            $module = $this->getApp()->getModule($module);
        }
        /** @var \Mindy\Controller\BaseController $controller */
        $controller = Creator::createObject($controllerClass, time(), $module, $this->getRequest());
        $controller->run($action, $params);
    }
}
