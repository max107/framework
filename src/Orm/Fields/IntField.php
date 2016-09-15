<?php

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Types\Type;

/**
 * Class IntField
 * @package Mindy\Orm
 */
class IntField extends Field
{
    /**
     * @var int|string
     */
    public $length = 11;
    /**
     * @var bool
     */
    public $unsigned = false;

    public function setValue($value)
    {
        $this->value = (int)$value;
    }

    /**
     * @return string
     */
    public function getSqlType()
    {
        return Type::INTEGER;
    }

    public function getSqlOptions() : array
    {
        $options = parent::getSqlOptions();
        $options['unsigned'] = $this->unsigned;
        return $options;
    }
}
