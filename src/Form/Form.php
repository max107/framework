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
            $inputs .= $this->fields[$name]->render();
        }

        return strtr('{inputs}', [
            '{inputs}' => $inputs
        ]);
    }

    /**
     * @return string
     */
    public function renderErrors() : string
    {
        if (empty($this->getErrors())) {
            return '';
        } else {
            $errors = [];
            foreach ($this->getErrors() as $name => $errors) {
                $errors[] = strtr('<ul><li>{label}<ul>{errors}</ul></li></ul>', [
                    '{label}' => $this->fields[$name]->label,
                    '{errors}' => implode(' ', array_map(function ($error) {
                        return '<li>' . $error . '</li>';
                    }, $errors))
                ]);
            }

            return '<ul>' . implode(' ', $errors) . '</ul>';
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
                    '{inputs}' => $this->renderInputs(array_keys($this->fields)),
                    '{errors}' => $this->renderErrors()
                ]);
            }
            return $html;
        }
    }

    /**
     * Please avoid this method for render form
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