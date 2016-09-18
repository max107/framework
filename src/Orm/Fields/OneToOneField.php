<?php

namespace Mindy\Orm\Fields;

use Doctrine\DBAL\Schema\Index;
use Exception;
use Mindy\Orm\ModelInterface;
use Mindy\Tests\Orm\Models\MemberProfile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class OneToOneField
 * @package Mindy\Orm
 */
class OneToOneField extends ForeignField
{
    /**
     * @var string
     */
    public $to;

    public function init()
    {
        parent::init();

        if ($this->primary) {
            $this->unique = true;
        } else {
            $this->null = true;
        }
    }

    public function getValidationConstraints() : array
    {
        $constraints = [];

        if ($this->primary) {
            $constraints = [
                new Assert\NotBlank(),
                new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) {
                    $qs = $this->getModel()->objects()->filter(['pk' => $value]);
                    if ($qs->count() > 0) {
                        $context->buildViolation('The value must be unique')->addViolation();
                    }
                })
            ];
        } else {
            $constraints = [
                new Assert\Callback(function ($value, ExecutionContextInterface $context, $payload) {
                    $qs = $this->getRelatedModel()->objects()->filter(['pk' => $value]);
                    if ($qs->count() > 0) {
                        $context->buildViolation('The value must be unique')->addViolation();
                    }
                })
            ];
        }

        return $constraints;
    }

    public function reversedTo()
    {
        return $this->to;
    }

    /*
    public function getDbPrepValue()
    {
        if ($this->primary && $this->getModel()->getConnection()->driverName == 'pgsql') {
            // Primary key всегда передается по логике Query, а для корректной работы pk в pgsql
            // необходимо передать curval($seq) или nextval($seq) или не экранированный DEFAULT.
            //
//            $sequenceName = $db->getSchema()->getTableSchema($this->getModel()->tableName())->sequenceName;
//            return new Expression("nextval('" . $sequenceName . "')");
            return new Expression("DEFAULT");
        } else {
            return parent::getDbPrepValue();
        }
    }
    */

    public function setValue($value)
    {
        if ($this->primary === false) {
            $model = $this->getModel();
            $modelClass = $this->modelClass;

            $valueRaw = $value instanceof ModelInterface ? $value->pk : $value;

            if ($value) {
                $count = call_user_func([$modelClass, 'objects'])->filter([
                    $this->reversedTo() => $model->pk
                ])->exclude([
                    $this->reversedTo() => $valueRaw
                ])->count();

                if ($count > 0) {
                    throw new Exception(get_class($this->getRelatedModel()) . ' must have unique key');
                }

                $value->pk = $model->pk;
                $value->save();
            } else {
                call_user_func([$modelClass, 'objects'])->filter([
                    $this->reversedTo() => $model->pk
                ])->delete();
            }
        } else {
            if ($value) {
                $currentValue = $this->getRelatedModel()->{$this->to};
                if ($currentValue) {
                    $relatedCount = $this->getRelatedModel()->objects()->filter([$this->to => $currentValue])->count();
                } else {
                    $relatedCount = 0;
                }
                $count = $this->getModel()->objects()->filter([$this->getName() . '_id' => $value])->count();
                if ($relatedCount > 0 && $count > 0) {
                    throw new Exception(get_class($this->getModel()) . ' failed to assign value');
                }
            }
        }
        return parent::setValue($value);
    }

    public function getValue()
    {
        if ($this->primary) {
            return $this->getModel()->pk;
        } else {
            return $this->getRelatedModel()->objects()->get([
                $this->to => $this->getModel()->pk
            ]);
        }
    }

    public function getSqlIndexes() : array
    {
        $indexes = [];
        $name = $this->primary ? $this->name . '_id' : $this->name;
        if ($this->primary) {
            $indexes[] = new Index('PRIMARY', [$name], true, true);
        } else if ($this->unique && !$this->primary) {
            $indexes[] = new Index($name . '_idx', [$name], true, false);
        }
        return $indexes;
    }

    /**
     * @return string
     */
    public function getAttributeName() : string
    {
        if ($this->primary) {
            $primaryKeyName = call_user_func([$this->modelClass, 'getPrimaryKeyName']);
            return $this->name . '_' . $primaryKeyName;
        } else {
            return $this->name . '_id';
        }
    }
}
