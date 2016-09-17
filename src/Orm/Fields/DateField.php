<?php

namespace Mindy\Orm\Fields;
use Doctrine\DBAL\Types\Type;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class DateField
 * @package Mindy\Orm
 */
class DateField extends Field
{
    /**
     * @var bool
     */
    public $autoNowAdd = false;
    /**
     * @var bool
     */
    public $autoNow = false;

    /**
     * @return string
     */
    public function getSqlType()
    {
        return Type::getType(Type::DATE);
    }

    public function onBeforeInsert()
    {
        if ($this->autoNowAdd) {
            $this->getModel()->setAttribute($this->name, $this->getValue());
        }
    }

    public function canBeEmpty()
    {
        return ($this->autoNowAdd || $this->autoNow) || !$this->required && $this->null || !is_null($this->default);
    }

    public function onBeforeUpdate()
    {
        if ($this->autoNow) {
            $this->getModel()->setAttribute($this->name, $this->getValue());
        }
    }

    public function getValue()
    {
        $adapter = QueryBuilder::getInstance($this->getModel()->getConnection())->getAdapter();
        if ($this->autoNowAdd && $this->getModel()->getIsNewRecord() || $this->autoNow) {
            return $adapter->getDate();
        }
        if (is_numeric($this->value)) {
            return $adapter->getDate($this->value);
        }
        return $this->value;
    }

    public function getFormField($form, $fieldClass = '\Mindy\Form\Fields\DateField', array $extra = [])
    {
        return parent::getFormField($form, $fieldClass, $extra);
    }
}
