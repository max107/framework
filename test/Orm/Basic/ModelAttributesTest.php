<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/07/16
 * Time: 13:56
 */

namespace Mindy\Tests\Orm\Basic;

use Mindy\Tests\Orm\OrmDatabaseTestCase;
use Mindy\Tests\Orm\Models\User;

class ModelAttributesTest extends OrmDatabaseTestCase
{
    public $driver = 'sqlite';

    protected function getModels()
    {
        return [new User];
    }

    public function testDirtyAttributes()
    {
        $user = new User();
        $user->username = '123';
        $user->password = '123';
        $this->assertEquals(['username', 'password'], $user->getDirtyAttributes());
        $this->assertEquals(['username' => null, 'password' => null], $user->getOldAttributes());

        $user->username = '321';
        $user->password = '321';
        $this->assertTrue($user->save());
        $this->assertEquals([], $user->getOldAttributes());
    }

    public function testOldAttributes()
    {
        $user = new User();

        $user->username = 'foo';
        $this->assertNull($user->getOldAttribute('username'));

        $user->username = 'bar';
        $this->assertEquals('foo', $user->getOldAttribute('username'));

        $this->assertTrue($user->save());

        $this->assertEquals('bar', $user->username);
        $this->assertNull($user->getOldAttribute('username'));
    }
}