<?php

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 03/01/14.01.2014 22:10
 */

namespace Mindy\Orm;

use ArrayAccess;
use Doctrine\DBAL\Connection;
use Exception;
use function Mindy\app;
use Mindy\Base\Mindy;
use Mindy\Exception\InvalidParamException;
use Mindy\Helper\Json;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\Fields\AutoField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Fields\JsonField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\QueryBuilder\QueryBuilder;
use Serializable;

/**
 * Class Base
 * @package Mindy\Orm
 * @property boolean $isNewRecord Whether the record is new and should be inserted when calling [[save()]].
 */
abstract class Base implements ArrayAccess, Serializable
{
    use Accessors, Configurator;

    /**
     * The insert operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_INSERT = 0x01;
    /**
     * The update operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_UPDATE = 0x02;
    /**
     * The delete operation. This is mainly used when overriding [[transactions()]] to specify which operations are transactional.
     */
    const OP_DELETE = 0x04;
    /**
     * All three operations: insert, update, delete.
     * This is a shortcut of the expression: OP_INSERT | OP_UPDATE | OP_DELETE.
     */
    const OP_ALL = 0x07;
    /**
     * @var array
     */
    protected $errors = [];
    /**
     * @var array attribute values indexed by attribute names
     */
    private $_attributes = [];
    /**
     * @var array|null old attribute values indexed by attribute names.
     * This is `null` if the record [[isNewRecord|is new]].
     */
    private $_oldAttributes;

    private $_related = [];

    private static $_cache;

    protected $_attributesSchema = null;
    /**
     * @var \Mindy\Event\EventManager
     */
    private $_eventManager;
    /**
     * @var \Mindy\Query\Connection
     */
    private $_db;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
        self::getMeta();
        $this->init();
    }

    public function __toString()
    {
        return $this->classNameShort();
    }

    protected function getEventManager()
    {
        if ($this->_eventManager === null) {
            if (class_exists('\Mindy\Base\Mindy') && \Mindy\Base\Mindy::app()) {
                $this->_eventManager = \Mindy\Base\Mindy::app()->getComponent('signal');
            } else {
                $this->_eventManager = new DummyObject();
            }
        }
        return $this->_eventManager;
    }

    public static function getCache()
    {
        if (self::$_cache === null) {
            if (class_exists('\Mindy\Base\Mindy')) {
                self::$_cache = \Mindy\Base\Mindy::app()->getComponent('cache');
            } else {
                self::$_cache = new \Mindy\Cache\DummyCache;
            }
        }
        return self::$_cache;
    }

    /**
     * @param $owner Model
     * @param $isNew
     */
    public function beforeSave($owner, $isNew)
    {
    }

    /**
     * @param $owner Model
     * @param $isNew
     */
    public function afterSave($owner, $isNew)
    {
        self::getCache()->set($owner->className() . '_' . $owner->primaryKeyName(), $owner);
    }

    /**
     * @param $owner Model
     */
    public function beforeDelete($owner)
    {
    }

    /**
     * @param $owner Model
     */
    public function afterDelete($owner)
    {
        self::getCache()->delete($owner->className() . '_' . $owner->primaryKeyName());
    }

    /**
     * @param $owner Model
     */
    public function beforeValidate($owner)
    {
    }

    /**
     * @param $owner Model
     */
    public function afterValidate($owner)
    {
    }

    public function init()
    {
    }

    /**
     * Example usage:
     * return [
     *     'name' => new CharField(['length' => 250, 'default' => '']),
     *     'email' => new EmailField(),
     * ]
     * @return array
     */
    public static function getFields()
    {
        return [];
    }

    /**
     * PHP getter magic method.
     * This method is overridden so that attributes and related objects can be accessed like properties.
     *
     * @param string $name property name
     * @throws \Exception
     * @return mixed property value
     * @see getAttribute()
     */
    public function __get($name)
    {
        return $this->__getInternalOrm($name);
    }

    public function __getInternalOrm($name)
    {
        if ($name == 'pk') {
            $name = $this->primaryKey();
            $name = array_shift($name);
        }

        $meta = static::getMeta();

        if ($meta->hasFileField($name)) {
            $fileField = $this->getField($name);
            $fileField->setModel($this);
            $fileField->setDbValue($this->getAttribute($name));
            return $fileField;
        } else if ($meta->hasOneToOneField($name) && $this->hasAttribute($name) === false) {
            /* @var $field \Mindy\Orm\Fields\OneToOneField */
            $field = $meta->getField($name)->setModel($this);
            return $field->getValue();
        } else if ($meta->hasForeignField($name) && $this->hasAttribute($name) === false) {
            $value = $this->getAttribute($name . '_id');
            if (is_null($value)) {
                return $value;
            } else {
                /* @var $field \Mindy\Orm\Fields\ForeignField */
                $field = $meta->getForeignField($name)->setModel($this)->setValue($value);
                return $field->getValue();
            }
        } else if ($meta->hasManyToManyField($name) || $meta->hasHasManyField($name)) {
            /* @var $field \Mindy\Orm\Fields\ManyToManyField|\Mindy\Orm\Fields\HasManyField */
            $field = $meta->getField($name);
            return $field->setModel($this)->getManager();
        } else if ($meta->hasField($name) && is_a($this->getField($name), JsonField::class)) {
            $field = $this->getField($name)->setModel($this);
            $field->setDbValue($this->getAttribute($name));
            return $field->getValue();
        } else if (isset($this->_attributes[$name]) || array_key_exists($name, $this->_attributes)) {
            return $this->_attributes[$name];
        } else if ($this->hasAttribute($name)) {
            return $this->hasField($name) ? $this->getField($name)->default : null;
        }

        return $this->__getInternal($name);
    }

    /**
     * PHP setter magic method.
     * This method is overridden so that AR attributes can be accessed like properties.
     * @param string $name property name
     * @param mixed $value property value
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        if ($name == 'pk') {
            $name = $this->primaryKey();
            $name = array_shift($name);
        }

        $meta = static::getMeta();

        if ($meta->hasForeignField($name) && !$this->hasAttribute($name)) {
            $name .= '_id';
            if ($value instanceof Base) {
                $value = $value->pk;
            }
        }

        if ($meta->hasOneToOneField($name)) {
            if (strpos($name, '_id') === false) {
                $name .= '_id';
            }
            if ($value instanceof Base) {
                $value = $value->pk;
            }
            $this->_related[$name] = $value;
        } else if ($meta->hasHasManyField($name) || $meta->hasManyToManyField($name)) {
            $this->_related[$name] = $value;
        } elseif ($this->hasAttribute($name)) {
            if ($meta->hasFileField($name)) {
                $field = $meta->getFileField($name);
                $field->setDbValue($this->getAttribute($name));
                $field->setModel($this);
                $field->setValue($value);
                $value = $field->getDbPrepValue();
            }

            $this->setAttribute($name, $value);
        } else {
            throw new Exception("Setting unknown property " . get_class($this) . "::" . $name);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named attribute is null or not.
     * @param string $name the property name or the event name
     * @return boolean whether the property value is null
     */
    public function __isset($name)
    {
        try {
            return $this->__get($name) !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sets a component property to be null.
     * This method overrides the parent implementation by clearing
     * the specified attribute value.
     * @param string $name the property name or the event name
     */
    public function __unset($name)
    {
        if ($this->hasAttribute($name)) {
            unset($this->_attributes[$name]);
        } elseif (array_key_exists($name, $this->_related)) {
            unset($this->_related[$name]);
        } elseif (array_key_exists($name, $this->_related)) {
            unset($this->_related);
        }
    }

    /**
     * TODO wtf, refactoring
     * Returns a value indicating whether the current record is new.
     * @return boolean whether the record is new and should be inserted when calling [[save()]].
     */
    public function getIsNewRecord()
    {
        return $this->_oldAttributes === null;
    }

    /**
     * Sets the value indicating whether the record is new.
     * @param boolean $value whether the record is new and should be inserted when calling [[save()]].
     * @see getIsNewRecord()
     */
    public function setIsNewRecord($value)
    {
        $this->_oldAttributes = $value ? null : $this->_attributes;
    }

    /**
     * Returns a value indicating whether the given set of attributes represents the primary key for this model
     * @param array $keys the set of attributes to check
     * @return boolean whether the given set of attributes represents the primary key for this model
     */
    public static function isPrimaryKey($keys)
    {
        $pks = static::primaryKey();
        if (count($keys) === count($pks)) {
            return count(array_intersect($keys, $pks)) === count($pks);
        } else {
            return false;
        }
    }

    /**
     * Returns a value indicating whether the model has an attribute with the specified name.
     * @param string $name the name of the attribute
     * @return boolean whether the model has an attribute with the specified name.
     */
    public function hasAttribute($name)
    {
        return isset($this->_attributes[$name]) || in_array($name, $this->attributes());
    }

    /**
     * Returns the named attribute value.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * @param string $name the attribute name
     * @return mixed the attribute value. Null if the attribute is not set or does not exist.
     * @see hasAttribute()
     */
    public function getAttribute($name)
    {
        if (isset($this->_attributes[$name])) {
            return $this->_attributes[$name];
        } else if (isset($this->_related[$name])) {
            return $this->_related[$name];
        }

        return null;
    }

    /**
     * Sets the named attribute value.
     * @param string $name the attribute name
     * @param mixed $value the attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            if ($this->isPrimaryKey([$name])) {
                $this->setIsNewRecord(true);
            }

            $this->_attributes[$name] = $value;
        } else {
            throw new InvalidParamException(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }

    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if ($this->hasField($name) || $this->getMeta()->hasForeignKey($name)) {
                $this->$name = $value;
            } else if ($this->hasAttribute($name)) {
                $this->setAttribute($name, $value);
            }
        }
        return $this;
    }

    protected function setDbAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            if ($this->hasAttribute($name)) {
                $this->setAttribute($name, $value);
            }
        }
        return $this;
    }

    /**
     * Returns the list of all attribute names of the model.
     * The default implementation will return all column names of the table associated with this AR class.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return $this->getMeta()->getAttributes();
    }

    /**
     * Returns the primary key name(s) for this AR class.
     * The default implementation will return the primary key(s) as declared
     * in the DB table that is associated with this AR class.
     *
     * If the DB table does not declare any primary key, you should override
     * this method to return the attributes that you want to use as primary keys
     * for this AR class.
     *
     * Note that an array should be returned even for a table with single primary key.
     *
     * @return string[] the primary keys of the associated database table.
     */
    public static function primaryKey()
    {
        // return static::getTableSchema()->primaryKey;
        return static::getMeta()->primaryKey();
    }

    public static function primaryKeyName()
    {
        return implode('_', self::primaryKey());
    }

    /**
     * @return MetaData
     */
    public static function getMeta()
    {
        return MetaData::getInstance(get_called_class());
    }

    /**
     * Return initialized fields
     * @return \Mindy\Orm\Fields\Field[]
     */
    public function getFieldsInit()
    {
        return static::getMeta()->getFieldsInit();
    }

    /**
     * Returns the primary key value(s).
     * @param boolean $asArray whether to return the primary key value as an array. If true,
     * the return value will be an array with column names as keys and column values as values.
     * Note that for composite primary keys, an array will always be returned regardless of this parameter value.
     * @property mixed The primary key value. An array (column name => column value) is returned if
     * the primary key is composite. A string is returned otherwise (null will be returned if
     * the key value is null).
     * @return mixed the primary key value. An array (column name => column value) is returned if the primary key
     * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
     * the key value is null).
     */
    public function getPrimaryKey($asArray = false)
    {
        $keys = $this->primaryKey();
        if (count($keys) === 1 && !$asArray) {
            return isset($this->_attributes[$keys[0]]) ? $this->_attributes[$keys[0]] : null;
        } else {
            $values = [];
            foreach ($keys as $name) {
                $values[$name] = isset($this->_attributes[$name]) ? $this->_attributes[$name] : null;
            }

            return $values;
        }
    }

    /**
     * Return table name based on this class name.
     * Override this method for custom table name.
     * @return string
     */
    public static function tableName()
    {
        $className = get_called_class();
        $normalizeClass = rtrim(str_replace('\\', '/', $className), '/\\');
        if (($pos = strrpos($normalizeClass, '/')) !== false) {
            $class = substr($normalizeClass, $pos + 1);
        } else {
            $class = $normalizeClass;
        }

        return strtr("{{%{tableName}}}", ['{tableName}' => self::normalizeTableName($class)]);
    }

    public static function normalizeTableName($name)
    {
        return trim(strtolower(preg_replace('/(?<![A-Z])[A-Z]/', '_\0', $name)), '_');
    }

    /**
     * @param $db
     * @return $this
     */
    public function using($db)
    {
        if (($db instanceof Connection) === false) {
            // TODO refact, detach from app()
            $db = app()->db->getConnection($db);
        }
        $this->_db = $db;
        return $this;
    }

    /**
     * @return \Doctrine\Dbal\Connection|null
     */
    public function getConnection()
    {
        if ($this->_db === null && app()) {
            $this->_db = app()->db->getConnection();
        }
        return $this->_db;
    }

    /**
     * Saves the current record.
     *
     * This method will call [[insert()]] when [[isNewRecord]] is true, or [[update()]]
     * when [[isNewRecord]] is false.
     *
     * For example, to save a customer record:
     *
     * ~~~
     * $customer = new Customer;  // or $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->save();
     * ~~~
     *
     *
     * @param array $fields list of attribute names that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the saving succeeds
     */
    public function save(array $fields = [])
    {
        return $this->getIsNewRecord() ? $this->insert($fields) : $this->update($fields);
    }

    /**
     * @param array $fields
     * @return bool
     * @throws Exception
     */
    public function insert(array $fields = [])
    {
        $connection = static::getConnection();

        $this->onBeforeInsertInternal();

        $connection->beginTransaction();
        try {
            $result = $this->insertInternal($fields);
            if (!$result) {
                $connection->rollBack();
            } else {
                $connection->commit();
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->updateRelated();
        $this->onAfterInsertInternal();

        return $result;
    }

    protected function onBeforeInsertInternal()
    {
        $meta = static::getMeta();
        foreach ($this->getFieldsInit() as $name => $field) {
            if ($meta->hasHasManyField($name) || $meta->hasManyToManyField($name) || $meta->hasOneToOneField($name)) {
                continue;
            } else if ($meta->hasForeignField($name)) {
                $foreighField = $meta->getForeignField($name);
                $name .= "_" . MetaData::getInstance($foreighField->modelClass)->getPkName();
            }

            $field->setModel($this);
            $field->setValue($this->getAttribute($name));
            $field->onBeforeInsert();
        }

        $this->getEventManager()->send($this, 'beforeSave', $this, true);
    }

    protected function onBeforeUpdateInternal()
    {
        $meta = static::getMeta();
        foreach ($this->getFieldsInit() as $name => $field) {
            if ($this->getPkName() == $name || $meta->hasHasManyField($name) || $meta->hasManyToManyField($name) || $meta->hasOneToOneField($name)) {
                continue;
            }
            $field->setModel($this)->setValue($this->getAttribute($name));
            $field->onBeforeUpdate();
        }

        $signal = $this->getEventManager();
        $signal->send($this, 'beforeSave', $this, false);
    }

    protected function onBeforeDeleteInternal()
    {
        $meta = static::getMeta();

        foreach ($this->getFieldsInit() as $name => $field) {
            if ($this->getPkName() == $name || $meta->hasManyToManyField($name)) {
                continue;
            }
            $field->setModel($this);
            if (!$meta->hasHasManyField($name)) {
                $field->setValue($this->getAttribute($name));
            }
            $field->onBeforeDelete();
        }

        $signal = $this->getEventManager();
        $signal->send($this, 'beforeDelete', $this);
    }

    protected function onAfterInsertInternal()
    {
        $meta = static::getMeta();
        foreach ($this->getFieldsInit() as $name => $field) {
            if ($this->getPkName() == $name || $meta->hasHasManyField($name) || $meta->hasManyToManyField($name) || $meta->hasOneToOneField($name)) {
                continue;
            } else if ($meta->hasForeignField($name)) {
                $foreighField = $meta->getForeignField($name);
                $name .= "_" . MetaData::getInstance($foreighField->modelClass)->getPkName();
            }
            $field->setModel($this);
            $field->setValue($this->getAttribute($name));
            $field->onAfterInsert();
        }

        $signal = $this->getEventManager();
        $signal->send($this, 'afterSave', $this, true);
    }

    protected function onAfterUpdateInternal()
    {
        $meta = static::getMeta();
        foreach ($this->getFieldsInit() as $name => $field) {
            if ($this->getPkName() == $name || $meta->hasHasManyField($name) || $meta->hasManyToManyField($name) || $meta->hasOneToOneField($name)) {
                continue;
            }
            $field->setModel($this);
            $field->setValue($this->getAttribute($name));
            $field->onAfterUpdate();
        }

        $signal = $this->getEventManager();
        $signal->send($this, 'afterSave', $this, false);
    }

    protected function onAfterDeleteInternal()
    {
        $meta = static::getMeta();
        foreach ($this->getFieldsInit() as $name => $field) {
            if ($this->getPkName() == $name || $meta->hasHasManyField($name) || $meta->hasManyToManyField($name) || $meta->hasOneToOneField($name)) {
                continue;
            }
            $field->setModel($this);
            $field->setValue($this->getAttribute($name));
            $field->onAfterDelete();
        }

        $signal = $this->getEventManager();
        $signal->send($this, 'afterDelete', $this);
    }

    public function updateRelated()
    {
        $meta = static::getMeta();
        foreach ($this->_related as $name => $value) {
            if ($value instanceof Manager) {
                continue;
            }

            if ($meta->hasHasManyField($name)) {
                continue;
            }

            if ($meta->hasManyToManyField($name)) {
                /* @var $field \Mindy\Orm\Fields\HasManyField|\Mindy\Orm\Fields\ManyToManyField */
                $field = $meta->getField($name);
                $field->setModel($this);

                if (empty($value)) {
                    if ($field instanceof ManyToManyField) {
                        $field->getManager()->clean();
                    }
                } else {
                    $field->setValue($value);
                }
            }

            if ($meta->hasOneToOneField($name)) {
                $meta->getOneToOneField($name)->setModel($this)->setValue($value);
            }
        }
        $this->_related = [];
    }

    protected function getDbPrepValues($values)
    {
        $meta = static::getMeta();
        $prepValues = [];
        foreach ($values as $name => $value) {
            if ($meta->hasForeignField($name)) {
                /** @var \Mindy\Orm\Fields\ForeignField $field */
                $field = $meta->getForeignField($name);
                $field->setModel($this)->setValue($value);
                $prepValues[$name] = $field->getDbPrepValue();
            } else if ($meta->hasOneToOneField($name)) {
                /** @var \Mindy\Orm\Fields\ForeignField $field */
                $field = $meta->getOneToOneField($name);
                $field->setModel($this)->setValue($value);
                $prepValues[$name] = $field->getDbPrepValue();
            } else if ($this->hasField($name)) {
                /** @var \Mindy\Orm\Fields\Field $field */
                $field = $this->getField($name);
                $prepValues[$name] = $field->getDbPrepValue();
            } else {
                $prepValues[$name] = $value;
            }
        }
        return $prepValues;
    }

    /**
     * Inserts an ActiveRecord into DB without considering transaction.
     * @param array $fields list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return boolean whether the record is inserted successfully.
     */
    protected function insertInternal(array $fields = [])
    {
        $values = $this->getDirtyAttributes($fields);
//        if (empty($values)) {
//            foreach ($this->getPrimaryKey(true) as $key => $value) {
//                $values[$key] = $value;
//            }
//        }

        $incValues = $values;
        $primaryKeyName = self::primaryKeyName();
        if (array_key_exists($primaryKeyName, $incValues) === false) {
            $incValues[$primaryKeyName] = null;
        }
        $dbValues = $this->getDbPrepValues($incValues);
        $connection = static::getConnection();
        $adapter = QueryBuilder::getInstance($connection)->getAdapter();

        if (!$connection->insert($adapter->getRawTableName($this->tableName()), $dbValues)) {
            return false;
        }

        $primaryKeyName = self::primaryKeyName();
//        debug($connection->lastInsertId());
        if (array_key_exists($primaryKeyName, $values) === false && isset($tableSchema)) {
            $id = $connection->getLastInsertID($tableSchema->sequenceName);
            $this->setAttribute($primaryKeyName, $id);
            $values[$primaryKeyName] = $id;
        }

        // Issue https://github.com/MindyPHP/Mindy/issues/15
        /*
        $table = $this->getTableSchema();
        if ($table->sequenceName !== null) {
            foreach ($table->primaryKey as $name) {
                if ($this->getAttribute($name) === null) {
                    $id = $db->getLastInsertID($table->sequenceName);
                    $this->setAttribute($name, $id);
                    $values[$name] = $id;
                    break;
                }
            }
        }
        */

        $this->setAttributes($values);
        $this->setOldAttributes($values);

        return true;
    }

    /**
     * Saves the changes to this active record into the associated database table.
     *
     * This method performs the following steps in order:
     *
     * 1. call [[beforeValidate()]] when `$runValidation` is true. If validation
     *    fails, it will skip the rest of the steps;
     * 2. call [[afterValidate()]] when `$runValidation` is true.
     * 3. call [[beforeSave()]]. If the method returns false, it will skip the
     *    rest of the steps;
     * 4. save the record into database. If this fails, it will skip the rest of the steps;
     * 5. call [[afterSave()]];
     *
     * In the above step 1, 2, 3 and 5, events [[EVENT_BEFORE_VALIDATE]],
     * [[EVENT_BEFORE_UPDATE]], [[EVENT_AFTER_UPDATE]] and [[EVENT_AFTER_VALIDATE]]
     * will be raised by the corresponding methods.
     *
     * Only the [[dirtyAttributes|changed attribute values]] will be saved into database.
     *
     * For example, to update a customer record:
     *
     * ~~~
     * $customer = Customer::findOne($id);
     * $customer->name = $name;
     * $customer->email = $email;
     * $customer->update();
     * ~~~
     *
     * Note that it is possible the update does not affect any row in the table.
     * In this case, this method will return 0. For this reason, you should use the following
     * code to check if update() is successful or not:
     *
     * ~~~
     * if ($this->update() !== false) {
     *     // update successful
     * } else {
     *     // update failed
     * }
     * ~~~
     *
     * @param array $fields list of attributes that need to be saved. Defaults to null,
     * meaning all attributes that are loaded from DB will be saved.
     * @return integer|boolean the number of rows affected, or false if validation fails
     * or [[beforeSave()]] stops the updating process.
     * @throws \Exception in case update failed.
     */
    public function update(array $fields = [])
    {
        $db = static::getDb();

        $this->onBeforeUpdateInternal();

        $transaction = $db->beginTransaction();
        try {
            $result = $this->updateInternal($fields);
            if ($result) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $this->updateRelated();
        $dirty = $this->getDirtyAttributes($fields);
        $this->onAfterUpdateInternal();
        $this->setOldAttributes($dirty);

        return $result;
    }

    /**
     * @see update()
     * @throws Exception
     */
    protected function updateInternal(array $fields = [])
    {
        $values = $this->getDirtyAttributes($fields);
        if (empty($values)) {
            return true;
        }

        // Work incorrecly, see https://github.com/studio107/Mindy_Orm/issues/64
        // $condition = $this->getOldPrimaryKey(true);
        $condition = $this->getPrimaryKey(true);

        $lock = $this->optimisticLock();
        if ($lock !== null) {
            if (!isset($values[$lock])) {
                $values[$lock] = $this->$lock + 1;
            }
            $condition[$lock] = $this->$lock;
        }
        // We do not check the return value of updateAll() because it's possible
        // that the UPDATE statement doesn't change anything and thus returns 0.
        $dbValues = $this->getDbPrepValues($values);
        $rows = $this->objects()->filter($condition)->update($dbValues);

        if ($lock !== null && !$rows) {
            throw new Exception('The object being updated is outdated.');
        }

        foreach ($values as $name => $value) {
            $this->_attributes[$name] = $value;
        }

        return $rows >= 0;
    }

    /**
     * Returns the old primary key value(s).
     * This refers to the primary key value that is populated into the record
     * after executing a find method (e.g. find(), findOne()).
     * The value remains unchanged even if the primary key attribute is manually assigned with a different value.
     * @param boolean $asArray whether to return the primary key value as an array. If true,
     * the return value will be an array with column name as key and column value as value.
     * If this is false (default), a scalar value will be returned for non-composite primary key.
     * @property mixed The old primary key value. An array (column name => column value) is
     * returned if the primary key is composite. A string is returned otherwise (null will be
     * returned if the key value is null).
     * @return mixed the old primary key value. An array (column name => column value) is returned if the primary key
     * is composite or `$asArray` is true. A string is returned otherwise (null will be returned if
     * the key value is null).
     */
    public function getOldPrimaryKey($asArray = false)
    {
        $keys = $this->primaryKey();
        if (count($keys) === 1 && !$asArray) {
            return isset($this->_oldAttributes[$keys[0]]) ? $this->_oldAttributes[$keys[0]] : null;
        } else {
            $values = [];
            foreach ($keys as $name) {
                $values[$name] = isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
            }

            return $values;
        }
    }

    /**
     * Returns the name of the column that stores the lock version for implementing optimistic locking.
     *
     * Optimistic locking allows multiple users to access the same record for edits and avoids
     * potential conflicts. In case when a user attempts to save the record upon some staled data
     * (because another user has modified the data), a [[StaleObjectException]] exception will be thrown,
     * and the update or deletion is skipped.
     *
     * Optimistic locking is only supported by [[update()]] and [[delete()]].
     *
     * To use Optimistic locking:
     *
     * 1. Create a column to store the version number of each row. The column type should be `BIGINT DEFAULT 0`.
     *    Override this method to return the name of this column.
     * 2. In the Web form that collects the user input, add a hidden field that stores
     *    the lock version of the recording being updated.
     * 3. In the controller action that does the data updating, try to catch the [[StaleObjectException]]
     *    and implement necessary business logic (e.g. merging the changes, prompting stated data)
     *    to resolve the conflict.
     *
     * @return string the column name that stores the lock version of a table row.
     * If null is returned (default implemented), optimistic locking will not be supported.
     */
    public function optimisticLock()
    {
        return null;
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($model[$offset])`.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return $this->$offset !== null;
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     * @param mixed $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($model[$offset])`.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

    /**
     * Returns the old attribute values.
     * @return array the old attribute values (name-value pairs)
     */
    public function getOldAttributes()
    {
        return $this->_oldAttributes === null ? [] : $this->_oldAttributes;
    }

    /**
     * Sets the old attribute values.
     * All existing old attribute values will be discarded.
     * @param array|null $values old attribute values to be set.
     * If set to `null` this record is considered to be [[isNewRecord|new]].
     */
    public function setOldAttributes($values)
    {
        $this->_oldAttributes = $values;
    }

    /**
     * Returns the old value of the named attribute.
     * If this record is the result of a query and the attribute is not loaded,
     * null will be returned.
     * @param string $name the attribute name
     * @return mixed the old attribute value. Null if the attribute is not loaded before
     * or does not exist.
     * @see hasAttribute()
     */
    public function getOldAttribute($name)
    {
        return isset($this->_oldAttributes[$name]) ? $this->_oldAttributes[$name] : null;
    }

    /**
     * Sets the old value of the named attribute.
     * @param string $name the attribute name
     * @param mixed $value the old attribute value.
     * @throws InvalidParamException if the named attribute does not exist.
     * @see hasAttribute()
     */
    public function setOldAttribute($name, $value)
    {
        if (isset($this->_oldAttributes[$name]) || $this->hasAttribute($name)) {
            $this->_oldAttributes[$name] = $value;
        } else {
            throw new InvalidParamException(get_class($this) . ' has no attribute named "' . $name . '".');
        }
    }

    /**
     * Marks an attribute dirty.
     * This method may be called to force updating a record when calling [[update()]],
     * even if there is no change being made to the record.
     * @param string $name the attribute name
     */
    public function markAttributeDirty($name)
    {
        unset($this->_oldAttributes[$name]);
    }

    /**
     * Returns a value indicating whether the named attribute has been changed.
     * @param string $name the name of the attribute
     * @return boolean whether the attribute has been changed
     */
    public function isAttributeChanged($name)
    {
        if (isset($this->_attributes[$name], $this->_oldAttributes[$name])) {
            return $this->_attributes[$name] !== $this->_oldAttributes[$name];
        } else {
            return isset($this->_attributes[$name]) || isset($this->_oldAttributes[$name]);
        }
    }

    /**
     * Returns the attribute values that have been modified since they are loaded or saved most recently.
     * @param string[]|null $fields the names of the attributes whose values may be returned if they are
     * changed recently. If null, [[attributes()]] will be used.
     * @return array the changed attribute values (name-value pairs)
     */
    public function getDirtyAttributes(array $fields = [])
    {
        if ($fields === []) {
            $fields = $this->attributes();
        }
        $fields = array_flip($fields);
        $attributes = [];
        if ($this->_oldAttributes === null) {
            foreach ($this->_attributes as $name => $value) {
                if (isset($fields[$name])) {
                    $attributes[$name] = $value;
                }
            }
        } else {
            foreach ($this->_attributes as $name => $value) {
                $nameFk = null;
                if ($this->getMeta()->hasForeignField($name)) {
                    $nameFk = $this->getMeta()->getForeignKey($name);
                }
                if (
                    (
                        isset($fields[$name]) ||
                        $nameFk && isset($fields[$nameFk])
                    ) &&
                    (
                        !array_key_exists($name, $this->_oldAttributes) ||
                        $value !== $this->_oldAttributes[$name]
                    )
                ) {
                    $attributes[$name] = $value;
                }
            }
        }

        return $attributes;
    }

    public static function __callStatic($method, $args)
    {
        $manager = $method . 'Manager';
        $className = get_called_class();
        if (method_exists($className, $manager) && is_callable([$className, $manager])) {
            return call_user_func_array([$className, $manager], $args);
        } elseif (method_exists($className, $method) && is_callable([$className, $method])) {
            return call_user_func_array([$className, $method], $args);
        } else {
            throw new Exception("Call unknown method {$method}");
        }
    }

    public function __call($method, $args)
    {
        $manager = $method . 'Manager';
        if (method_exists($this, $manager)) {
            return call_user_func_array([$this, $manager], array_merge([$this], $args));
        } elseif (method_exists($this, $method)) {
            return call_user_func_array([$this, $method], $args);
        } else {
            throw new Exception("Call unknown method {$method}");
        }
    }

    public static function objectsManager($instance = null)
    {
        $className = get_called_class();
        return new Manager($instance ? $instance : new $className);
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        $errors = [];
        $meta = self::getMeta();

        /* @var $field \Mindy\Orm\Fields\Field */
        foreach ($this->getFieldsInit() as $name => $field) {
            if (
                $field instanceof AutoField ||
                $meta->hasManyToManyField($name) ||
                $meta->hasHasManyField($name)
            ) {
                continue;
            }

            $value = $this->getAttribute($name);
            // @TODO: fix me. This must be related from foreign field
            if (is_a($field, ForeignField::class) && !$value) {
                $value = $this->getAttribute($name . '_id');
            }
            $field->setModel($this);
            $field->setValue($value);
            if ($field->isValid() === false) {
                $errors[$name] = $field->getErrors();
            }
        }

        $this->setErrors($errors);
        return count($errors) == 0;
    }

    /**
     * @param array $errors
     * @return $this
     */
    protected function setErrors(array $errors)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasField($name)
    {
        $meta = static::getMeta();
        if ($name === 'pk') {
            $name = $meta->getPkName();
        }
        return $meta->hasField($name);
    }

    /**
     * @param $name
     * @param bool $throw
     * @throws \Exception
     * @return \Mindy\Orm\Fields\Field|null
     */
    public function getField($name, $throw = true)
    {
        $meta = self::getMeta();
        if ($meta->hasField($name)) {
            $field = $meta->getField($name);
            if ($meta->hasForeignField($name)) {
                $value = $this->getAttribute($name . '_id');
            } else if ($meta->hasOneToOneField($name)) {
                $value = $this->getAttribute($name . '_id');
            } else {
                $value = $this->getAttribute($name);
            }
            $field->setModel($this);
            if ($value !== null) {
                $field->setDbValue($value);
            }
            return $field;
        }

        if ($throw) {
            throw new Exception('Field "' . $name . '" not found in model: ' . get_class($this));
        } else {
            return null;
        }
    }

    /**
     * @return \Mindy\Orm\Fields\ManyToManyField[]
     */
    public function getManyFields()
    {
        return static::getMeta()->getManyFields();
    }

    public function delete()
    {
        $this->onBeforeDeleteInternal();
        $result = $this->objects()->delete(['pk' => $this->pk]);
        if ($result > 0) {
            $this->onAfterDeleteInternal();
        }
        return $result;
    }

    /**
     * Get primary key name
     * @return string|null
     */
    public static function getPkName()
    {
        return self::getMeta()->getPkName();
    }

    /**
     * Converts the object into an array.
     * @return array the array representation of this object
     */
    public function toArray()
    {
        $arr = [];
        $attributes = $this->attributes();
        foreach ($attributes as $attrName) {
            if ($this->getMeta()->hasForeignKey($attrName)) {
                $name = substr($attrName, 0, strpos($attrName, '_id'));
            } else {
                $name = $attrName;
            }
            $field = $this->getField($name);
            $arr[$attrName] = $field->toArray();
            if ($field->hasChoices()) {
                $arr["{$attrName}__text"] = $this->getField($name)->toText();
            }
        }
        return $arr;
    }

    public function toJson()
    {
        return Json::encode($this->toArray());
    }

    /**
     * TODO move to manager
     * Creates an active record object using a row of data.
     * This method is called by [[ActiveQuery]] to populate the query results
     * into Active Records. It is not meant to be used to create new records.
     * @param array $row attribute values (name => value)
     * @return \Mindy\Orm\Model the newly created active record.
     */
    public static function create(array $row)
    {
        /** @var Model $record */
        $className = self::className();
        $record = new $className;
        $record->setDbAttributes($row);
        $record->setOldAttributes($row);
        return $record;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize($this->_attributes);
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        $attributes = unserialize($serialized);
        $this->setDbAttributes($attributes);
        $this->setOldAttributes($attributes);
    }
}
