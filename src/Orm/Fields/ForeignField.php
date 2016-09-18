<?php

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Exception;
use InvalidArgumentException;
use Mindy\Orm\Base;
use Mindy\Orm\ModelInterface;
use Mindy\Orm\ManagerInterface;
use Mindy\QueryBuilder\QueryBuilder;

/**
 * Class ForeignField
 * @package Mindy\Orm
 */
class ForeignField extends RelatedField
{
    public $onDelete;

    public $onUpdate;

    public $modelClass;

    public $extra = [];

    public function init()
    {
        parent::init();
        if (is_subclass_of($this->modelClass, '\Mindy\Orm\Model') === false) {
            throw new InvalidArgumentException('$modelClass must be a \Mindy\Orm\Model instance in modelClass');
        }
    }

    public function getOnDelete()
    {
        return $this->onDelete;
    }

    public function getOnUpdate()
    {
        return $this->onUpdate;
    }

    public function getForeignPrimaryKey()
    {
        return call_user_func([$this->modelClass, 'getPkName']);
    }

    public function getJoin(QueryBuilder $qb, $topAlias)
    {
        $alias = $qb->makeAliasKey($this->getRelatedModel()->tableName());
        return [
            [
                'LEFT JOIN',
                $this->getRelatedTable(false),
                [$topAlias . '.' . $this->name . '_id' => $alias . '.' . $this->getRelatedModel()->getPrimaryKeyName()],
                $alias
            ]
        ];
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return null|ModelInterface
     * @throws Exception
     */
    public function getValue()
    {
        if (empty($this->value)) {
            if ($this->null) {
                return null;
            } else {
                throw new Exception('Value is empty');
            }
        } else {
            if ($this->value instanceof ModelInterface) {
                if ($this->value->getIsNewRecord()) {
                    throw new Exception('Failed to set new model');
                } else {
                    $value = $this->value->pk;
                }
            } else {
                $value = $this->value;
            }

            $params = array_merge(['pk' => $value], $this->extra);
            return call_user_func([$this->modelClass, 'objects'])->get($params);
        }
    }

    /**
     * @param $form
     * @param string $fieldClass
     * @param array $extra
     * @return mixed|null
     */
    public function getFormField($form, $fieldClass = '\Mindy\Form\Fields\DropDownField', array $extra = [])
    {
        return parent::getFormField($form, $fieldClass, $extra);
    }

    /**
     * @param $value
     * @return \Mindy\Orm\Model|\Mindy\Orm\TreeModel|null
     * @throws Exception
     */
    protected function fetch($value)
    {
        if (empty($value)) {
            if ($this->null === true) {
                return null;
            } else {
                throw new Exception("Value in fetch method of PrimaryKeyField cannot be empty");
            }
        }

        return $this->fetchModel($value);
    }

    protected function fetchModel($value)
    {
        return $this->getManager()->get(array_merge(['pk' => $value], $this->extra));
    }

    public function toArray()
    {
        $value = $this->getValue();
        return $value instanceof Base ? $value->pk : $value;
    }

    public function getSelectJoin(QueryBuilder $qb, $topAlias)
    {
        // TODO: Implement getSelectJoin() method.
    }

    /**
     * @return string
     */
    public function getAttributeName() : string
    {
        return $this->name . '_id';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof ModelInterface) {
            return $value;
        } else if (!is_null($value)) {
            return $this->fetchModel($value);
        }
        return $value;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (is_null($value)) {
            return $value;
        }

        return $value instanceof ModelInterface ? $value->pk : $value;
    }

    /**
     * @return ManagerInterface
     */
    public function getManager() : ManagerInterface
    {
        return call_user_func([$this->modelClass, 'objects']);
    }
}
