<?php

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Types\Type;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class DateTimeField
 * @package Mindy\Orm
 */
class DateTimeField extends DateField
{
    public function getValue()
    {
        /** @var \Mindy\QueryBuilder\BaseAdapter $db */
        $db = QueryBuilder::getInstance($this->getModel()->getConnection())->getAdapter();
        if ($this->autoNowAdd && $this->getModel()->getIsNewRecord() || $this->autoNow) {
            return $db->getDateTime();
        }

        if (is_numeric($this->value)) {
            return $db->getDateTime($this->value);
        }

        return $this->value;
    }

    /**
     * @return string
     */
    public function getSqlType()
    {
        return Type::DATETIME;
    }

    public function getFormField($form, $fieldClass = '\Mindy\Form\Fields\DateTimeField', array $extra = [])
    {
        return parent::getFormField($form, $fieldClass, $extra);
    }
}
