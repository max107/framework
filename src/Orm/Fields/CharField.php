<?php

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class CharField
 * @package Mindy\Orm
 */
class CharField extends Field
{
    /**
     * @var int
     */
    public $length = 255;

    /**
     * @return string
     */
    public function getSqlType()
    {
        return Type::getType(Type::STRING);
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDbValue($value)
    {
        $this->value = (string)$value;
        return $this;
    }

    /**
     * @return string
     */
    public function getDbValue()
    {
        return (string)parent::getDbValue();
    }
}
