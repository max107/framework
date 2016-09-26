<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 13/09/16
 * Time: 20:11
 */

namespace Mindy\Form;

/**
 * Interface FieldInterface
 * @package Mindy\Form
 */
interface FieldInterface
{
    /**
     * @return bool
     */
    public function isValid() : bool;

    /**
     * @return array
     */
    public function getErrors() : array;

    /**
     * @param $value
     * @return mixed
     */
    public function setValue($value);

    /**
     * @param string $name
     */
    public function setName(string $name);

    /**
     * @param FormInterface $form
     * @return string
     */
    public function render(FormInterface $form) : string;

    /**
     * @return mixed
     */
    public function getValue();

    /**
     * @return string
     */
    public function getHtmlId() : string;

    /**
     * @return string
     */
    public function renderLabel() : string;

    /**
     * @return string
     */
    public function renderInput() : string;

    /**
     * @return bool
     */
    public function isRequired() : bool;

    /**
     * @return string
     */
    public function getHtmlName() : string;

    /**
     * @return mixed|string|null
     */
    public function getLabel();
}