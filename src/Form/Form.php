<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 16:20
 */

declare(strict_types = 1);

namespace Mindy\Form;

use Exception;

class Form extends BaseForm
{
    /**
     * @var string
     */
    public $errorsTemplate = '<li><p>{label}</p><ul>{errors}</ul></li>';

    /**
     * @return array
     */
    public function getFieldsets() : array
    {
        return [];
    }

    /**
     * @param array $fields
     * @return string
     */
    protected function renderInputs(array $fields)
    {
        $inputs = '';
        foreach ($fields as $name) {
            $inputs .= $this->fields[$name]->render($this);
        }
        return $inputs;
    }

    /**
     * @return string
     */
    public function renderErrors() : string
    {
        if (empty($this->getErrors())) {
            return '';
        } else {
            $errorsHtml = '';
            foreach ($this->getErrors() as $name => $errors) {
                $errorsHtml .= strtr($this->errorsTemplate, [
                    '{label}' => $this->fields[$name]->getLabel(),
                    '{errors}' => implode(' ', array_map(function ($error) {
                        return '<li>' . $error . '</li>';
                    }, $errors))
                ]);
            }

            return '<ul>' . $errorsHtml . '</ul>';
        }
    }

    /**
     * @return string
     */
    public function render() : string
    {
        $fieldsets = $this->getFieldsets();
        if (empty($fieldsets)) {
            return strtr('{errors}{inputs}', [
                '{inputs}' => $this->renderInputs(array_keys($this->fields)),
                '{errors}' => $this->renderErrors()
            ]);
        } else {
            $html = '';
            foreach ($fieldsets as $legend => $fields) {
                $html .= strtr('<fieldset><legend>{legend}</legend>{errors}{inputs}</fieldset>', [
                    '{legend}' => $legend,
                    '{inputs}' => $this->renderInputs($fields),
                    '{errors}' => $this->renderErrors()
                ]);
            }
            return $html;
        }
    }

    /**
     * Please avoid this method for render form
     * @codeCoverageIgnore
     * @return string
     */
    public function __toString()
    {
        try {
            return (string)$this->render();
        } catch (Exception $e) {
            return dump($e);
        }
    }
}