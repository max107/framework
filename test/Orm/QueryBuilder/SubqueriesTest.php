<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 04/01/14.01.2014 00:53
 */

namespace Mindy\Tests\Orm\QueryBuilder;

use Mindy\Tests\Orm\Models\Customer;
use Mindy\Tests\Orm\Models\Group;
use Mindy\Tests\Orm\Models\Membership;
use Mindy\Tests\Orm\Models\User;
use Mindy\Tests\Orm\OrmDatabaseTestCase;

abstract class SubqueriesTest extends OrmDatabaseTestCase
{
    public function getModels()
    {
        return [new User, new Group, new Membership, new Customer];
    }

    public function tearDown()
    {

    }

    public function testSubqueryIn()
    {
        $user1 = new User(['username' => 'foo']);
        $this->assertTrue($user1->save());
        $user2 = new User(['username' => 'bar']);
        $this->assertTrue($user2->save());

        $group = new Group(['name' => 'example']);
        $this->assertTrue($group->save());
        $groupEmpty = new Group(['name' => 'empty']);
        $this->assertTrue($groupEmpty->save());

//        $user1->groups->link($group);
//        $user2->groups->link($group);
//        Also should work
        $group->users->link($user1);
        $group->users->link($user2);

        $links = Membership::objects()->asArray()->all();
        $this->assertEquals([
            ['id' => '1', 'group_id' => '1', 'user_id' => '1'],
            ['id' => '2', 'group_id' => '1', 'user_id' => '2'],
        ], $links);

        $qs = User::objects()->filter([
            'groups__pk__in' => Group::objects()->filter(['id' => 2])->select('id')
        ]);
        $this->assertSql('SELECT [[users_1]].* FROM [[users]] AS [[users_1]] LEFT JOIN [[membership]] AS [[membership_1]] ON [[membership_1]].[[user_id]]=[[users_1]].[[id]] LEFT JOIN [[group]] AS [[group_1]] ON [[group_1]].[[id]]=[[membership_1]].[[group_id]] WHERE ([[group_1]].[[id]] IN (SELECT [[group_1]].[[id]] FROM [[group]] AS [[group_1]] WHERE ([[group_1]].[[id]]=2)))', $qs->allSql());
        $this->assertEquals([], $qs->asArray()->all());

        $qs = User::objects()->filter([
            'groups__pk__in' => Group::objects()->filter(['id' => 1])->select('id')
        ])->order(['id']);
        $this->assertSql('SELECT [[users_1]].* FROM [[users]] AS [[users_1]] LEFT JOIN [[membership]] AS [[membership_1]] ON [[membership_1]].[[user_id]]=[[users_1]].[[id]] LEFT JOIN [[group]] AS [[group_1]] ON [[group_1]].[[id]]=[[membership_1]].[[group_id]] WHERE ([[group_1]].[[id]] IN (SELECT [[group_1]].[[id]] FROM [[group]] AS [[group_1]] WHERE ([[group_1]].[[id]]=1))) ORDER BY [[users_1]].[[id]] ASC', $qs->allSql());
        $users = $qs->asArray()->all();
        $this->assertEquals([
            ['id' => 1, 'username' => 'foo', 'password' => ''],
            ['id' => 2, 'username' => 'bar', 'password' => '']
        ], $users);
    }
}
