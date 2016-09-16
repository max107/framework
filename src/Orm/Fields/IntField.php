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
        if ($this->primary) {
            $options['autoincrement'] = true;
        } else {
            $options['unsigned'] = $this->unsigned;
        }
        return $options;
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setValue($value)
    {
        parent::setValue($this->null ? $value : (int)$value);
    }

    /**
     * @return int|null
     */
    public function getDbValue()
    {
        $value = parent::getDbValue();
        if ($this->null === true) {
            return $value;
        }
        return (int)$value;
    }

    public function convertToPHPValue($value)
    {
        return (int)$value;
    }

    public function convertToDatabaseValue($value)
    {
        return (int)$value;
    }
}
