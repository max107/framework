<?php

declare(strict_types = 1);

namespace Mindy\Orm;

use Mindy\Helper\Creator;
use Mindy\Orm\Fields\AutoField;
use Mindy\Orm\Fields\Field;
use Mindy\Orm\Fields\FileField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\HasManyField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Fields\OneToOneField;
use Mindy\Orm\Fields\RelatedField;

/**
 * Class MetaData
 * @package Mindy\Orm
 */
class MetaData
{
    /**
     * @var MetaData[]
     */
    private static $instances = [];
    /**
     * @var string
     */
    protected $modelClassName;
    /**
     * @var array
     */
    protected $allFields = [];
    /**
     * @var array
     */
    protected $localFields = [];
    /**
     * @var array
     */
    protected $localFileFields = [];
    /**
     * @var array
     */
    protected $extFields = [];
    /**
     * @var array
     */
    protected $foreignFields = [];
    /**
     * @var array
     */
    protected $oneToOneFields = [];
    /**
     * @var array
     */
    protected $manyToManyFields = [];
    /**
     * @var array
     */
    protected $hasManyFields = [];
    /**
     * @var array
     */
    protected $attributes = null;
    /**
     * @var array
     */
    protected $primaryKeys = null;

    /**
     * MetaData constructor.
     * @param string $className
     */
    final public function __construct(string $className)
    {
        $this->init($className);
    }

    /**
     * @param string $className
     */
    public function init(string $className)
    {
        $missingPrimaryField = true;
        $fkFields = [];
        $m2mFields = [];

        foreach (call_user_func([$className, 'getFields']) as $name => $config) {

            if (is_string($config)) {
                $config = ['class' => $config];
            }

            if (is_array($config)) {
                $field = Creator::createObject($config);
            } else {
                $field = $config;
            }

            $field->setName($name);
            $field->setModelClass($className);

            if ($field->primary && ($field instanceof OneToOneField) === false) {
                $missingPrimaryKey = false;
                $primaryFields[] = $name;
            }

            if ($field instanceof FileField) {
                $this->localFileFields[$name] = $field;

            } else if ($field instanceof RelatedField) {
                /* @var $field \Mindy\Orm\Fields\RelatedField */
                if ($field instanceof ManyToManyField) {
                    /* @var $field \Mindy\Orm\Fields\ManyToManyField */
                    $this->manyToManyFields[$name] = $field;
                    $m2mFields[$name] = $field;
                } else if ($field instanceof HasManyField) {
                    /* @var $field \Mindy\Orm\Fields\HasManyField */
                    $this->hasManyFields[$name] = $field;
                } else if ($field instanceof OneToOneField) {
                    /* @var $field \Mindy\Orm\Fields\OneToOneField */
                    if ($field->reversed) {
                        $this->oneToOneFields[$name . '_id'] = $name;
                    } else {
                        $missingPrimaryField = false;
                        $this->primaryKeys[] = $name . '_' . $field->getForeignPrimaryKey();
                        $this->oneToOneFields[$name . '_' . $field->getForeignPrimaryKey()] = $name;
                    }
                } else if ($field instanceof ForeignField) {
                    /* @var $field \Mindy\Orm\Fields\ForeignField */
                    $fkFields[$name] = $field;
                }
            } else {
                $this->localFields[$name] = $field;
            }
            $this->allFields[$name] = $field;
        }

        if ($missingPrimaryField) {
            $pkName = 'id';
            /* @var $autoField \Mindy\Orm\Fields\AutoField */
            $autoField = new AutoField;
            $autoField->setName($pkName);
            $autoField->setModelClass($className);

            $this->allFields = array_merge([$pkName => $autoField], $this->allFields);
            $this->localFields = array_merge([$pkName => $autoField], $this->localFields);
            $this->primaryKeys[] = $pkName;
        }

        foreach ($fkFields as $name => $field) {
            // ForeignKey in self model
            if ($field->modelClass == $className) {
                $this->foreignFields[$name . '_' . $this->getPkName()] = $name;
            } else {
                $this->foreignFields[$name . '_' . $field->getForeignPrimaryKey()] = $name;
            }
        }
    }

    public function getPkName()
    {
        return implode('_', $this->primaryKey());
    }

    public function hasRelatedField($name)
    {
        return $this->hasManyToManyField($name) || $this->hasHasManyField($name) || $this->hasForeignField($name);
    }

    /**
     * @param $name
     * @return \Mindy\Orm\Fields\HasManyField|\Mindy\Orm\Fields\ManyToManyField|\Mindy\Orm\Fields\ForeignField|null
     */
    public function getRelatedField($name)
    {
        if ($this->hasManyToManyField($name)) {
            return $this->getManyToManyField($name);
        } else if ($this->hasHasManyField($name)) {
            return $this->getHasManyField($name);
        } else if ($this->hasForeignField($name)) {
            return $this->getForeignField($name);
        }
        return null;
    }

    /**
     * TODO refactoring
     * @return array
     */
    public function getRelatedFields()
    {
        return array_keys(array_merge(array_merge($this->hasManyFields, $this->manyToManyFields), array_flip($this->foreignFields)));
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasFileField($name)
    {
        return array_key_exists($name, $this->localFileFields);
    }

    /**
     * @param $name
     * @return \Mindy\Orm\Fields\FileField
     */
    public function getFileField($name)
    {
        $field = $this->localFileFields[$name];
        $field->cleanValue();
        return $field;
    }

    public function hasForeignKey($name)
    {
        return array_key_exists($name, $this->foreignFields);
    }

    public function hasHasManyField($name)
    {
        return array_key_exists($name, $this->hasManyFields);
    }

    public function hasManyToManyField($name)
    {
        return array_key_exists($name, $this->manyToManyFields);
    }

    public function hasOneToOneField($name)
    {
        if (array_key_exists($name, $this->oneToOneFields)) {
            $name = $this->oneToOneFields[$name];
        }
        if ($this->hasField($name)) {
            return $this->getField($name) instanceof OneToOneField;
        }
        return false;
    }

    public function getForeignKey($name)
    {
        return $this->foreignFields[$name];
    }

    public function primaryKey()
    {
        if (is_array($this->primaryKeys)) {
            return $this->primaryKeys;
        } else if (is_null($this->primaryKeys)) {
            $this->primaryKeys = [];
            foreach ($this->allFields as $name => $field) {
                if ($field instanceof ManyToManyField || $field instanceof HasManyField) {
                    continue;
                }

                if ($field->primary) {
                    $this->primaryKeys[] = $field instanceof RelatedField ? $name . '_id' : $name;
                }
            }
            return $this->primaryKeys;
        }

        return [];
    }

    /**
     * @param $className
     * @return MetaData
     */
    public static function getInstance($className)
    {
        if (array_key_exists($className, self::$instances) === false) {
            self::$instances[$className] = new self($className);
        }

        return self::$instances[$className];
    }

    /**
     * @return array
     * @throws \Mindy\Exception\InvalidConfigException
     */
    public function getAttributes()
    {
        if ($this->attributes === null) {
            /** @var \Mindy\Orm\Model $className */
//            $className = $this->modelClassName;
//            $this->attributes = array_keys($className::getTableSchema()->columns);
            $attributes = [];
            foreach ($this->getFieldsInit() as $name => $field) {
                if ($field instanceof ManyToManyField || $field instanceof HasManyField) {
                    continue;
                }

                /** @var $field \Mindy\Orm\Fields\Field */
                if ($field instanceof OneToOneField) {
                    /** @var $field \Mindy\Orm\Fields\OneToOneField */
                    if ($field->reversed) {
                        $attributes[] = $name . '_id';
                    } else {
                        $attributes[] = $name . '_' . $field->getForeignPrimaryKey();
                    }
                } else if ($field instanceof ForeignField) {
                    /** @var $field \Mindy\Orm\Fields\ForeignField */
                    $attributes[] = $name . '_' . $field->getForeignPrimaryKey();
                } else {
                    $attributes[] = $name;
                }
            }
            $this->attributes = $attributes;
        }
        return $this->attributes;
    }

    public function getFieldsInit()
    {
        return $this->allFields;
    }

    /**
     * @param $name
     * @return \Mindy\Orm\Fields\Field
     */
    public function getField($name)
    {
        if ($name === 'pk') {
            $name = $this->getPkName();
        }
        $field = $this->allFields[$name];
        $field->cleanValue();
        return $field;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name)
    {
        if ($name === 'pk') {
            $name = $this->getPkName();
        }
        return array_key_exists($name, $this->allFields);
    }

    public function hasForeignField($name)
    {
        if (array_key_exists($name, $this->foreignFields)) {
            $name = $this->foreignFields[$name];
        }
        if ($this->hasField($name)) {
            return $this->getField($name) instanceof ForeignField;
        }
        return false;
    }

    public function getForeignField($name)
    {
        if (array_key_exists($name, $this->foreignFields)) {
            $name = $this->foreignFields[$name];
        }
        return $this->getField($name);
    }

    public function getOneToOneField($name)
    {
        if (array_key_exists($name, $this->oneToOneFields)) {
            $name = $this->oneToOneFields[$name];
        }
        return $this->getField($name);
    }

    /**
     * @return array|ForeignField[]
     */
    public function getForeignFields()
    {
        return $this->foreignFields;
    }

    /**
     * @return array|OneToOneField[]
     */
    public function getOneToOneFields()
    {
        return $this->oneToOneFields;
    }

    /**
     * @return array|ManyToManyField[]
     */
    public function getManyFields()
    {
        return $this->manyToManyFields;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getManyToManyField($name)
    {
        return $this->get($this->manyToManyFields, $name);
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getHasManyField($name)
    {
        return $this->get($this->hasManyFields, $name);
    }

    /**
     * @param array $storage
     * @param $key
     * @return mixed|null
     */
    private function get(array $storage, $key)
    {
        return isset($storage[$key]) ? $storage[$key] : null;
    }
}
