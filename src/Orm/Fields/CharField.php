<?php

namespace Mindy\Orm\Fields;

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
        return Type::STRING;
    }

    public function setDbValue($value)
    {
        $this->value = (string)$value;
        return $this;
    }

    public function getDbPrepValue()
    {
        if ($this->value === null && !$this->null && $this->default) {
            return $this->default;
        }
        return $this->value;
    }
}
