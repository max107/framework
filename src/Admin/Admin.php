<?php
/**
 * Created by IntelliJ IDEA.
 * User: max
 * Date: 28/04/16
 * Time: 14:32
 */

namespace Mindy\Admin;

use Exception;
use function Mindy\app;
use Mindy\Base\Mindy;
use Mindy\Form\Form;
use Mindy\Form\ModelForm;
use Mindy\Helper\Alias;
use Mindy\Creator\Creator;
use Mindy\Http\Response\RedirectResponse;
use Mindy\Orm\Fields\BooleanField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Manager;
use Mindy\Orm\Model;
use Mindy\Orm\ModelInterface;
use Mindy\Orm\TreeModel;
use Mindy\Pagination\Pagination;
use Mindy\QueryBuilder\Q\QOr;
use function Mindy\trans;
use PHPExcel;
use PHPExcel_Cell;
use PHPExcel_Cell_DataType;
use PHPExcel_IOFactory;
use PHPExcel_Style_Fill;
use Psr\Http\Message\ResponseInterface;

abstract class Admin extends BaseAdmin
{
    /**
     * @var array
     */
    public $columns = [];
    /**
     * @var array Pager config
     */
    public $pager = [
        'pageSize' => 100
    ];
    /**
     * @var array of the column names
     */
    public $searchFields = [];
    /**
     * @var array
     */
    public $defaultOrder = ['-pk'];
    /**
     * @var string name of the column
     */
    public $sortingColumn;
    /**
     * @var string name of column for make it link
     */
    public $treeLinkColumn;

    /**
     * Initialize admin class
     */
    public function init()
    {
        if ($this->getModel() instanceof TreeModel) {
            $columns = [];
            if ($this->defaultOrder) {
                $columns = is_array($this->defaultOrder) ? $this->defaultOrder : [$this->defaultOrder];
            }
            $this->defaultOrder = array_merge(['root', 'lft'], $columns);
        }
    }

    /**
     * @return \Mindy\Orm\Model|ModelInterface
     */
    public function getModel()
    {
        return Creator::createObject($this->getModelClass());
    }

    /**
     * Fetch sorting|ordering from $_GET
     * @param array $params
     * @return array
     */
    public function getOrderColumn(array $params = [])
    {
        if (isset($params['order'])) {
            $column = $params['order'];
            if (substr($column, 0, 1) === '-') {
                $column = ltrim($column, '-');
                $sort = "-";
            } else {
                $sort = "";
            }
            return [$sort . $column];
        } else if (!empty($this->defaultOrder)) {
            return $this->defaultOrder;
        }

        return [];
    }

    /**
     * @param \Mindy\Orm\QuerySet|\Mindy\Orm\Manager $qs
     * @param array $params
     * @param array $fields
     */
    public function applySearchToQuerySet($qs, array $params = [], array $fields = [])
    {
        if (isset($params['search']) && !empty($fields)) {
            $filters = [];
            foreach ($fields as $field) {
                $lookup = 'icontains';
                $fieldName = $field;
                if (strpos($field, '=') === 0) {
                    $fieldName = substr($field, 1);
                    $lookup = 'exact';
                }

                $filters[] = [$fieldName . '__' . $lookup => $params['search']];
            }
            $qs->filter([new QOr($filters)]);
        }
    }

    /**
     * Get qs from model.
     * @param Model $model
     * @return \Mindy\Orm\Manager|\Mindy\Orm\TreeManager
     */
    public function getQuerySet(Model $model)
    {
        return $model->objects();
    }

    /**
     * @param $modelClass
     * @param array $data
     * @throws Exception
     */
    public function sortingFlat($modelClass, array $data)
    {
        /** @var \Mindy\Orm\Model $modelClass */
        if (isset($data['models'])) {
            $models = $data['models'];
        } else {
            throw new Exception("Failed to receive models");
        }

        /**
         * Pager-independent sorting
         */
        $oldPositions = $modelClass::objects()->filter(['pk__in' => $models])->valuesList([$this->sortingColumn], true);
        asort($oldPositions);
        foreach ($models as $pk) {
            $modelClass::objects()->filter(['pk' => $pk])->update([
                $this->sortingColumn => array_shift($oldPositions)
            ]);
        }
    }

    /**
     * @param $modelClass
     * @param array $data
     * @throws Exception
     */
    public function sortingNested($modelClass, array $data)
    {
        /** @var \Mindy\Orm\TreeModel $modelClass */
        if (!isset($data['pk'])) {
            throw new Exception("Failed to receive primary key");
        }

        /** @var \Mindy\Orm\TreeModel $model */
        $model = $modelClass::objects()->filter(['pk' => $data['pk']])->get();
        if (!$model) {
            throw new Exception("Model not found");
        }

        if ($model->getIsRoot()) {
            $models = $data['models'];

            $roots = $modelClass::objects()->roots()->filter(['pk__in' => $models])->all();
            $newPositions = array_flip($models);

            foreach ($roots as $root) {
                $descendants = $root->objects()->descendants()->filter([
                    'level__gt' => 1
                ])->valuesList(['pk'], true);

                if (count($descendants) > 0) {
                    $modelClass::objects()->filter([
                        'pk__in' => $descendants
                    ])->update(['root' => $newPositions[$root->pk]]);
                }
            }

            foreach ($newPositions as $pk => $position) {
                $modelClass::objects()->filter([
                    'pk' => $pk
                ])->update(['root' => $position]);
            }
        } else {
            $target = null;
            if (isset($data['insertBefore'])) {
                $target = $modelClass::objects()->get(['pk' => $data['insertBefore']]);
                if (!$target) {
                    throw new Exception("Target not found");
                }
                $model->moveBefore($target);
            } else if (isset($data['insertAfter'])) {
                $target = $modelClass::objects()->get(['pk' => $data['insertAfter']]);
                if (!$target) {
                    throw new Exception("Target not found");
                }
                $model->moveAfter($target);
            } else {
                throw new Exception("Missing required parameter insertAfter or insertBefore");
            }
        }
    }

    /**
     * @param null $pk
     * @throws Exception
     * @throws \Mindy\Exception\Exception
     */
    public function actionSorting($pk = null)
    {
        /* @var $qs \Mindy\Orm\QuerySet */
        /** @var \Mindy\Orm\Model $modelClass */
        $modelClass = $this->getModel();

        $tree = $modelClass instanceof TreeModel;
        if ($tree) {
            $this->sortingNested($modelClass, $_POST);
        } else {
            $this->sortingFlat($modelClass, $_POST);
        }

        $model = $this->getModel();
        $qs = $this->prepareQuerySet($model, $pk);
        $pager = new Pagination($qs, $this->pager);
        $columns = $this->getColumns();

        $html = $this->render($this->findTemplate('_table.html'), [
            'models' => $pager->paginate(),
            'pager' => $pager,
            'tree' => $tree,
            'sortingColumn' => $this->sortingColumn || $tree,
            'columns' => empty($columns) ? array_keys($model->getFields()) : $columns
        ]);

        $this->sendResponse(app()->http->html($html));
    }

    /**
     * Prepare query set.
     * Apply sorting, search, etc...
     * @param Model $model
     * @param null $pk
     * @return \Mindy\Orm\QuerySet|\Mindy\Orm\Manager|\Mindy\Orm\TreeManager
     */
    protected function prepareQuerySet(Model $model, $pk = null)
    {
        if ($model instanceof TreeModel) {
            if ($pk) {
                $qs = $this->getQuerySet($model)->filter(['pk' => $pk]);
                $model = $qs->get();
                if ($model === null) {
                    $this->error(404);
                }
                $qs = $this->getQuerySet($model)->children();
            } else {
                $qs = $this->getQuerySet($model)->roots();
            }
        } else {
            $qs = $this->getQuerySet($model);
        }

        $order = $this->getOrderColumn($_GET);
        if ($order) {
            $qs->order($order);
        }

        if (isset($_GET['search'])) {
            $this->applySearchToQuerySet($qs, $_GET, $this->searchFields);
        }

        return $qs;
    }

    /**
     * Generic action for render objects table with
     * sorting and search
     * @throws \Exception
     */
    public function actionList()
    {
        $model = $this->getModel();
        $tree = $model instanceof TreeModel;
        $instance = null;
        if ($tree) {
            $pk = $this->getRequest()->get->get('pk');
            if (!empty($pk)) {
                $instance = $model->objects()->get(['pk' => $pk]);
            }
        }

        $qs = $this->prepareQuerySet($model, isset($_GET['pk']) ? $_GET['pk'] : null);
        $pager = new Pagination($qs, $this->pager);
        $columns = $this->getColumns();
        $html = $this->render($this->findTemplate('list.html'), [
            'breadcrumbs' => $this->fetchBreadcrumbs($model, 'list'),
            'model' => $model,
            'instance' => $instance,
            'tree' => $tree,
            'treeLinkColumn' => $this->treeLinkColumn,
            'models' => $pager->paginate(),
            'pager' => $pager,
            'sortingColumn' => $this->sortingColumn || $tree,
            'columns' => empty($columns) ? array_keys($model->getFields()) : $columns
        ]);

        $this->sendResponse(app()->http->html($html));
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Render table cell. Used in template list.html
     * @param $column
     * @param Model $model
     * @return string
     */
    public function renderCell($column, Model $model)
    {
        $value = $this->getColumnValue($column, $model);
        $template = $this->findTemplate('columns/_' . $column . '.html', false);
        if ($template) {
            return $this->render($template, [
                'model' => $model,
                'column' => $column,
                'value' => $value
            ]);
        } else {
            if ($value instanceof Manager) {
                return get_class($value);
            } else {
                return $value;
            }
        }
    }

    /**
     * @return string model class name
     */
    public function getCreateForm()
    {
        return ModelForm::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdateForm()
    {
        return $this->getCreateForm();
    }

    public function getInitialAttributes()
    {
        return [];
    }

    public function getSaveParams()
    {
        return [];
    }

    public function actionCreate()
    {
        $model = $this->getModel();
        
        $form = Creator::createObject([
            'class' => $this->getCreateForm(),
            'model' => $model
        ]);
        $form->setAttributes($this->getInitialAttributes());

        $http = app()->http;
        if ($http->getIsPost()) {
            $form->fillFromRequest($http->getRequest());

            if ($form->isValid()) {
                if ($form->save()) {
                    $this->afterCreate($form);
                    if ($http->post->get('popup')) {
                        echo $this->render($this->findTemplate('_popup_close.html'), [
                            'popup_id' => $http->post->get('popup_id'),
                            'instance' => $form->getModel()
                        ]);
                        return;
                    }

                    $http->flash->success('Данные успешно сохранены');

                    $next = $this->getNextRoute($http->post->all(), $form->getModel());
                    if ($next) {
                        $http->redirect($next);
                    } else {
                        $http->refresh();
                    }
                } else {
                    $http->flash->error('При сохранении данных произошла ошибка, пожалуйста попробуйте выполнить сохранение позже или обратитесь к разработчику проекта, или вашему системному администратору');
                }
            } else {
                $http->flash->warning('Пожалуйста укажите корректные данные');
            }
        }

        $html = $this->render($this->findTemplate('create.html'), [
            'form' => $form,
            'model' => $model,
            'breadcrumbs' => $this->fetchBreadcrumbs($model, 'create')
        ]);

        $this->sendResponse(app()->http->html($html));
    }

    /**
     * @param ModelInterface $model
     * @param $action
     * @return array
     */
    public function getCustomBreadrumbs(ModelInterface $model, string $action)
    {
        if ($model instanceof TreeModel) {
            $pk = $this->getRequest()->get->get('pk');
            if (!empty($pk)) {
                /** @var null|TreeModel $instance */
                $instance = $this->getModel()->objects()->get(['pk' => $pk]);
                if ($instance) {
                    return $this->getParentBreadcrumbs($instance);
                }
            }
        }

        return [];
    }

    /**
     * @param Model $model
     * @param $action
     * @return array
     */
    public function fetchBreadcrumbs(ModelInterface $model, $action)
    {
        list($list, $create, $update) = $this->getAdminNames($model);
        $breadcrumbs = [
            ['name' => $list, 'url' => $this->getAdminUrl('list')]
        ];
        $custom = $this->getCustomBreadrumbs($model, $action);
        if (!empty($custom)) {
            // Fetch user custom breadcrumbs
            $breadcrumbs = array_merge($breadcrumbs, $custom);
        }

        switch ($action) {
            case 'create':
                $breadcrumbs[] = ['name' => $create];
                break;
            case 'update':
                $breadcrumbs[] = ['name' => $update];
                break;
            case 'list':
                break;
            case 'info':
                $breadcrumbs[] = [
                    'name' => trans('modules.Admin.main', 'Information about: %name%', ['%name%' => (string)$model]),
                    'url' => $this->getAdminUrl('list')
                ];
                break;
            default:
                break;
        }

        return $breadcrumbs;
    }

    public function actionUpdate($pk)
    {
        $model = Creator::createObject($this->getModelClass());
        $instance = $model->objects()->get(['pk' => $pk]);
        if ($instance === null) {
            $this->error(404);
        }

        /** @var \Mindy\Form\ModelForm $form */
        $form = Creator::createObject(['class' => $this->getCreateForm()]);
        $form->setModel($instance);

        $http = app()->http;
        if ($http->getIsPost()) {
            $form->fillFromRequest($http->getRequest());

            if ($form->isValid()) {
                if ($form->save()) {
                    $this->afterUpdate($form);
                    $http->flash->success('Данные успешно сохранены');

                    $next = $this->getNextRoute($http->post->all(), $form->getModel());
                    if ($next) {
                        $http->redirect($next);
                    } else {
                        $http->refresh();
                    }
                } else {
                    $http->flash->error('При сохранении данных произошла ошибка, пожалуйста попробуйте выполнить сохранение позже или обратитесь к разработчику проекта, или вашему системному администратору');
                }
            } else {
                $http->flash->warning('Пожалуйста укажите корректные данные');
            }
        }

        $html = $this->render($this->findTemplate('update.html'), [
            'form' => $form,
            'instance' => $instance,
            'model' => $model,
            'breadcrumbs' => $this->fetchBreadcrumbs($instance, 'update')
        ]);

        $this->sendResponse(app()->http->html($html));
    }

    /**
     * @param ResponseInterface|\Mindy\Http\Response\Response $response
     */
    protected function sendResponse(ResponseInterface $response)
    {
        app()->http->send($response->withCachePrevention());
    }

    /**
     * For override after update
     * @param ModelForm|Form $form
     */
    public function afterUpdate($form)
    {

    }

    /**
     * For override after create
     * @param ModelForm|Form $form
     */
    public function afterCreate($form)
    {

    }

    /**
     * For override after create
     * @param Model $model
     */
    public function afterRemove(Model $model)
    {

    }

    public function getVerboseNames()
    {
        return [];
    }

    /**
     * @param $column
     * @return mixed
     */
    public function getVerboseName($column)
    {
        $model = Creator::createObject($this->getModelClass());
        $names = $this->getVerboseNames();
        if (isset($names[$column])) {
            return $names[$column];

        } else if ($model->hasField($column)) {
            return $model->getField($column)->getVerboseName();

        } else {
            return $column;
        }
    }

    /**
     * @param $pk
     * @throws \Exception
     */
    public function actionInfo($pk)
    {
        $html = $this->render($this->findTemplate('info.html'), $this->processInfo($pk));
        $this->sendResponse(app()->http->html($html));
    }

    /**
     * @param $pk
     * @throws \Exception
     */
    public function actionPrint($pk)
    {
        $html = $this->render($this->findTemplate('info_print.html'), $this->processInfo($pk));
        $this->sendResponse(app()->http->html($html));
    }

    /**
     * @param $pk
     * @return array
     * @throws \Mindy\Exception\HttpException
     */
    protected function processInfo($pk)
    {
        $model = Creator::createObject($this->getModelClass());
        $instance = $model->objects()->get(['pk' => $pk]);
        if ($instance === null) {
            $this->error(404);
        }

        $fields = [];
        foreach ($this->getInfoFields($model) as $fieldName) {
            if ($fieldName === 'pk') {
                $fieldName = $model::getPkName();
            }
            $fields[$fieldName] = $model->getField($fieldName);
        }

        return [
            'model' => $instance,
            'fields' => $fields,
            'breadcrumbs' => $this->fetchBreadcrumbs($instance, 'info')
        ];
    }

    /**
     * @param ModelInterface $model
     * @return array
     */
    public function getInfoFields(ModelInterface $model)
    {
        return $model->getMeta()->getAttributes();
    }

    /**
     * Array of action => name, where actions is an
     * action in this admin class
     * @return array
     */
    public function getActions()
    {
        return $this->can('remove') ? [
            'batchRemove' => trans('framework.admin', 'Remove'),
        ] : [];
    }

    public function actionBatchRemove()
    {
        $models = $this->getRequest()->post->get('models');

        if (!empty($models)) {
            $this->getModel()->objects()->filter(['pk__in' => $models])->delete();
        }
    }

    /**
     * @param $column
     * @param $model \Mindy\Orm\Model
     * @return mixed
     */
    public function getColumnValue($column, $model)
    {
        list($column, $model) = $this->getChainedModel($column, $model);
        if ($model === null) {
            return null;
        }

        $column = $model->convertToPrimaryKeyName($column);
        $booleanHtml = '<i class="icon check checkmark"/>';
        if ($model->hasField($column)) {
            $value = $model->__get($column);
            if ($model->getField($column) instanceof BooleanField) {
                return $value ? $booleanHtml : '';
            } else {
                return $value;
            }
        } else {
            $method = 'get' . ucfirst($column);
            if (method_exists($model, $method)) {
                return $model->{$method}();
            }
        }
        return null;
    }

    /**
     * @param $column
     * @param $model
     * @return array
     */
    public function getChainedModel($column, $model)
    {
        if (strpos($column, '__') !== false) {
            $exploded = explode('__', $column);
            $last = count($exploded) - 1;
            $column = null;
            foreach ($exploded as $key => $name) {
                if ($model instanceof Model) {
                    $value = $model->{$name};
                    $column = $name;
                    if ($key != $last && $value) {
                        $model = $value;
                    }
                } else {
                    $model = null;
                    break;
                }
            }
        }
        return [$column, $model];
    }

    /**
     * @param $pk
     */
    public function actionRemove($pk)
    {
        /* @var $cls \Mindy\Orm\Model */
        $cls = $this->getModelClass();
        $instance = $cls::objects()->get(['pk' => $pk]);
        $redirectUrl = $this->getAdminUrl('list');

        $request = $this->getRequest();
        $referer = $request->getRequest()->getHeaderLine('Referer');

        if ($instance) {
            $instance->delete();
            $this->afterRemove($instance);
        }

        $response = new RedirectResponse(302, [], empty($referer) ? $redirectUrl : $referer);
        $this->sendResponse($response);
    }

    /**
     * Example usage:
     *
     * switch ($action) {
     *      case "save_create":
     *          return ['parent' => 'parent_id'];
     *      case "save":
     *          return ['parent' => 'pk'];
     *      default:
     *          return [];
     * }
     *
     * @param $action
     * @return array
     */
    public function getRedirectParams($action)
    {
        return [];
    }

    /**
     * Collect correct array for redirect
     * @param array $attributes
     * @param $action
     * @return array
     */
    protected function fetchRedirectParams(array $attributes, $action)
    {
        $redirectParams = [];
        $saveParams = $this->getRedirectParams($action);
        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $saveParams)) {
                $redirectParams[$saveParams[$key]] = $value;
            }
        }
        return $redirectParams;
    }

    /**
     * @param array $data
     * @param ModelInterface $model
     * @return string url for redirect
     */
    public function getNextRoute(array $data, ModelInterface $model)
    {
        if (array_key_exists('save_continue', $data)) {
            return $this->getAdminUrl('update', array_merge($this->fetchRedirectParams($model->getAttributes(), 'save_continue'), ['pk' => $model->pk]));
        } else if (array_key_exists('save_create', $data)) {
            return $this->getAdminUrl('create', $this->fetchRedirectParams($model->getAttributes(), 'save_create'));
        } else {
            return $this->getAdminUrl('list', $this->fetchRedirectParams($model->getAttributes(), 'save'));
        }
    }

    /**
     * @param $model
     * @return array
     */
    public function getParentBreadcrumbs(TreeModel $model)
    {
        $parents = [];

        if ($model->pk) {
            $parents = $model->objects()->ancestors()->order(['lft'])->all();
            $parents[] = $model;
        }

        $breadcrumbs = [];
        foreach ($parents as $parent) {
            $breadcrumbs[] = [
                'url' => $this->getAdminUrl('list', ['pk' => $parent->pk]),
                'name' => (string)$parent,
                'items' => []
            ];
        }
        return $breadcrumbs;
    }

    public function getAbsoluteUrl(Model $model)
    {
        if (method_exists($model, 'getAbsoluteUrl')) {
            return $model->getAbsoluteUrl();
        }

        return null;
    }

    public function actionExport()
    {
        $fileName = 'export.xlsx';

        $model = $this->getModel();
        $header = [];
        $fields = [];
        foreach ($model->getFieldsInit() as $name => $field) {
            if ($field instanceof ManyToManyField || $field instanceof HasManyField) {
                continue;
            }

            $fields[] = $name;
            $header[] = $field->getVerboseName($model);
        }
        $rows = $this->getQuerySet($model)->asArray()->valuesList($fields);
        $path = Alias::get('App.runtime') . DIRECTORY_SEPARATOR . $fileName;

        $chars = range(65, 90);

        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();

        $sheet->fromArray($header, NULL);
        $first = PHPExcel_Cell::stringFromColumnIndex(0);
        $last = PHPExcel_Cell::stringFromColumnIndex(count($header) - 1);
        $sheet->getStyle($first . '1:' . $last . '1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'f1f1f1']
            ]
        ]);

        foreach ($rows as $i => $row) {
            $rowInt = 0;
            foreach ($row as $value) {
                $sheet
                    ->getCell(chr($chars[$rowInt]) . ((int)$i + 2))
                    ->setValue($value)
                    ->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
                $rowInt++;
            }
        }

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save($path);

        $content = file_get_contents($path);
        unlink($path);

        $request = $this->getRequest();
        $request->http->sendFile($fileName, $content, null, true);

        echo '<script type="text/javascript">window.close();</script>';
    }
}