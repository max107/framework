<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/09/16
 * Time: 18:09
 */

namespace Mindy\Tests\Orm\Basic;

use Doctrine\DBAL\Driver\Connection;
use Mindy\Orm\Fields\AutoField;
use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\IntField;
use Mindy\Orm\NewOrm;
use Mindy\Tests\Orm\Models\CompositeModel;
use Mindy\Tests\Orm\Models\CustomPrimaryKeyModel;
use Mindy\Tests\Orm\Models\DefaultPrimaryKeyModel;
use Mindy\Tests\Orm\Models\DummyModel;
use Mindy\Tests\Orm\Models\NewModel;
use Mindy\Tests\Orm\Models\User;
use Mindy\Tests\Orm\OrmDatabaseTestCase;

class NewModelTest extends OrmDatabaseTestCase
{
    public function testInit()
    {
        $model = new DummyModel;
        $this->assertTrue($model->getIsNewRecord());
        $this->assertEquals([], $model->getFields());
        $this->assertEquals(['id' => null], $model->getAttributes());
        $this->assertEquals(['id'], $model->getMeta()->getAttributes());
        $this->assertEquals([], $model->getOldAttributes());
    }

    public function testCompositeKey()
    {
        $model = new CompositeModel(['user_id' => 1]);
        $this->assertEquals(['order_id', 'user_id'], $model->getPrimaryKeyName(true));
        $this->assertEquals(['order_id' => null, 'user_id' => 1], $model->getPrimaryKeyValues());
    }

    public function testSetGet()
    {
        $model = new NewModel(['username' => 'foo', 'password' => 'bar']);
        $this->assertSame('foo', $model->username);
        $this->assertSame('bar', $model->password);

        $model->username = 'mike';
        $this->assertSame('mike', $model->username);

        $model->id = 1;
        $this->assertSame(1, $model->pk);

        $model->id = '1';
        $this->assertSame('1', $model->pk);

        $model->pk = 2;
        $this->assertSame(2, $model->id);

        unset($model->pk);
        $this->assertNull($model->pk);
    }

    public function testPrimaryKey()
    {
        $custom = new CustomPrimaryKeyModel();
        $this->assertInstanceOf(IntField::class, $custom->getField('pk'));

        $custom = new DefaultPrimaryKeyModel();
        $this->assertInstanceOf(AutoField::class, $custom->getField('pk'));

        $custom->pk = 1;
        $this->assertSame(1, $custom->pk);
        $this->assertFalse(empty($custom->pk));
    }

    public function testGetHasField()
    {
        $model = new NewModel();
        $this->assertTrue($model->hasField('username'));
        $this->assertFalse($model->hasField('unknown'));
        $this->assertInstanceOf(CharField::class, $model->getField('username'));
    }

    public function testAttributes()
    {
        $model = new NewModel();

        $this->assertEquals(['id' => null, 'username' => null, 'password' => null], $model->getAttributes());
        $this->assertEquals(3, count($model->getMeta()->getAttributes()));

        $model->username = 'foo';
        $this->assertNull($model->getOldAttribute('username'));

        $model->username = 'bar';
        $this->assertEquals('foo', $model->getOldAttribute('username'));

        $this->assertTrue($model->save());

        $this->assertEquals('bar', $model->username);
        $this->assertNull($model->getOldAttribute('username'));
    }

    public function testDirtyAttributes()
    {
        $user = new NewModel();
        $user->username = '123';
        $user->password = '123';
        $this->assertEquals(['username', 'password'], $user->getDirtyAttributes());
        $this->assertEquals(['username' => null, 'password' => null], $user->getOldAttributes());

        $user->username = '321';
        $user->password = '321';
        $this->assertTrue($user->save());
        $this->assertEquals([], $user->getOldAttributes());
    }

    public function testArrayAccess()
    {
        $model = new NewModel(['username' => 'foo', 'password' => 'bar']);
        $this->assertSame('foo', $model['username']);
        $this->assertSame('bar', $model['password']);
        unset($model['username']);
        $this->assertNull($model['username']);
        $model['username'] = 'mike';
        $this->assertSame('mike', $model['username']);
    }

    public function testTableName()
    {
        $this->assertSame('new_orm', NewOrm::tableName());
        $this->assertSame('new_model', NewModel::tableName());
        $this->assertSame('composite_model', CompositeModel::tableName());
    }

    public function testConnection()
    {
        $model = new User();
        $connection = $model->getConnection();
        $this->assertInstanceOf(Connection::class, $connection);
    }

    public function testChangedAttributes()
    {
        $model = new User();
        $model->username = 'foo';
        $this->assertEquals([
            'username' => 'foo'
        ], $model->getChangedAttributes());
    }
}