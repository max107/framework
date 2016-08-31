<?php

namespace Mindy\Form\Fields;

use Mindy\Helper\JavaScript;
use Mindy\Helper\JavaScriptExpression;
use Mindy\Locale\Translate;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;

/**
 * Class Select2Field
 * @package Mindy\Form
 */
class Select2Field extends DropDownField
{
    public $options = [];

    public $pageSize = 10;

    public $modelField = 'name';

    public $placeholder = 'Please select value';
    /**
     * @var bool
     */
    public $sorting = false;
    /**
     * @var \Closure
     */
    public $fetchData = null;

    public function render()
    {
        $label = $this->renderLabel();

        $hint = $this->hint ? $this->renderHint() : '';
        $errors = $this->renderErrors();
        $name = $this->getHtmlName();

        $model = $this->getForm()->getModel();
        $modelField = $model->getField($this->name);
        $multiple = $modelField instanceof ManyToManyField || $modelField instanceof HasManyField;

        $options = [
            'width' => 'resolve',
            'allowClear' => true,
            'blurOnChange' => true,
            'openOnEnter' => false,
            'multiple' => $multiple,
            'placeholder' => Translate::getInstance()->t('form', $this->placeholder),
            'minimumInputLength' => 2,
            'ajax' => [
                'url' => "",
                'dataType' => 'json',
                'quietMillis' => 250,
                'data' => new JavaScriptExpression('function (term, page) {
                    return {
                        select2: term,
                        page: page,
                        field: "' . $this->getName() . '",
                        pageSize: "' . $this->pageSize . '",
                        modelField: "' . $this->modelField . '"
                    };
                }'),
                'results' => new JavaScriptExpression('function (data, page) {
                    var more = (page * 30) < data.total_count;
                    return {
                        results: data.items,
                        more: more
                    };
                }'),
            ],
            'escapeMarkup' => new JavaScriptExpression('function (m) {
                return m;
            }')
        ];

        if ($this->fetchData instanceof \Closure) {
            $fetchData = $this->fetchData;
            $data = $fetchData($this->getForm()->getInstance());
        } else {
            $data = [];
            if (($instance = $this->getForm()->getInstance()) !== null) {
                $field = $instance->getField($this->name);
                if ($field instanceof ForeignField) {
                    $item = $field->getManager()->get();
                    if ($item) {
                        $data = [
                            'id' => $item->pk,
                            'text' => (string)$item,
                            'pk' => $item->pk
                        ];
                    }
                } else {
                    foreach ($field->getManager()->all() as $item) {
                        $data[] = [
                            'id' => $item->pk,
                            'text' => (string)$item,
                            'pk' => $item->pk
                        ];
                    };
                }
            }
        }

        $select2 = '$("#' . $this->getHtmlId() . '").select2(' . JavaScript::encode($options) . ')';

        if (!empty($data)) {
            $select2 .= '.select2("data", ' . JavaScript::encode($data) . ');';
        }

        if ($this->sorting) {
            $sortingOptions = JavaScript::encode([
                'url' => '',
                'method' => 'post',
                'dataType' => 'json'
            ]);
            $select2options = [
                'containment' => 'parent',
                'start' => new JavaScriptExpression('function() {
                    $("#' . $this->getHtmlId() . '").select2("onSortStart");
                }'),
                'update' => new JavaScriptExpression('function() {
                    $("#' . $this->getHtmlId() . '").select2("onSortEnd");
                    var objects = $("#' . $this->getHtmlId() . '").select2("data");
                    var ids = [];
                    for (var i in objects) {
                        ids.push(objects[i].pk);
                    }
                    $.ajax(_.extend(' . $sortingOptions . ', {data: {sort: ids}}));
                }'),
            ];
            $select2 .= ';$("#' . $this->getHtmlId() . '").prev(".select2-container").find("ul.select2-choices").sortable(' . JavaScript::encode($select2options) . ');';
        }

        $out = implode("\n", [
            $label,
            "<input type='hidden' id='{$this->getHtmlId()}' name='{$name}' value='' />",
            $hint,
            $errors,
            '<script type="text/javascript">' . $select2 . '</script>'
        ]);

        return $out;
    }
}
