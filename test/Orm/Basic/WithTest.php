<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/07/16
 * Time: 15:43
 */

namespace Mindy\Tests\Orm\Basic;

use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\ForeignField;
use Mindy\Orm\Model;
use Mindy\Tests\Orm\Models\Customer;
use Mindy\Tests\Orm\Models\ModelTyre;
use Mindy\Tests\Orm\Models\Tyre;
use Mindy\Tests\Orm\Models\User;
use Mindy\Tests\Orm\OrmDatabaseTestCase;

class User1 extends Model
{
    public static function tableName()
    {
        return "{{user1}}";
    }
}

class Issue extends Model
{
    public static function getFields()
    {
        return [
            'author' => [
                'class' => ForeignField::class,
                'modelClass' => User1::class
            ],
            'user' => [
                'class' => ForeignField::class,
                'modelClass' => User1::class
            ],
            'created_at' => [
                'class' => DateTimeField::class,
                'autoNowAdd' => true
            ]
        ];
    }

    public static function tableName()
    {
        return "{{issue}}";
    }
}

abstract class WithTest extends OrmDatabaseTestCase
{
    public function getModels()
    {
        return [
            new User,
            new Customer,
            new Tyre,
            new ModelTyre,
            new User1,
            new Issue
        ];
    }

    public function testWith()
    {
        $user = new User([
            'username' => 'foo'
        ]);
        $user->save();

        (new User([
            'username' => 'bar'
        ]))->save();

        (new Customer([
            'user' => $user,
            'address' => 'address'
        ]))->save();

        $tyre = new Tyre();
        $tyre->save();

        $modelTyre = new ModelTyre([
            'tyre' => $tyre,
            'name' => 'Nordman 4'
        ]);
        $modelTyre->save();

        $modelTyre = new ModelTyre([
            'tyre' => $tyre,
            'name' => 'Nordman 3'
        ]);
        $modelTyre->save();

        $modelTyre = new ModelTyre([
            'tyre' => $tyre,
            'name' => 'Nordman 2'
        ]);
        $modelTyre->save();

        $filter = ['model_tyre__name' => 'Nordman 4'];
        $qs = Tyre::objects()->getQuerySet()->filter($filter);
        $data = $qs->with(['model_tyre'])->asArray()->all();
        $this->assertEquals([
            [
                'model_tyre' => [
                    'id' => '1',
                    'name' => 'Nordman 4',
                    'tyre_id' => '1'
                ],
                'id' => '1'
            ]
        ], $data);

        $qs = User::objects()->with(['addresses'])->asArray();
        $this->assertEquals([
            [
                'addresses' => [
                    'id' => 1,
                    'user_id' => 1,
                    'address' => 'address'
                ],
                'id' => '1',
                'username' => 'foo',
                'password' => null
            ],
            [
                'addresses' => [
                    'id' => null,
                    'user_id' => null,
                    'address' => ''
                ],
                'id' => '2',
                'username' => 'bar',
                'password' => null
            ]
        ], $qs->all());

        $qs = User::objects()->filter(['addresses__address' => 'address'])->with(['addresses'])->asArray();
        $this->assertEquals([
            [
                'addresses' => [
                    'id' => 1,
                    'user_id' => 1,
                    'address' => 'address'
                ],
                'id' => '1',
                'username' => 'foo',
                'password' => ''
            ]
        ], $qs->all());
    }

    public function testIssue()
    {
        $u = new User1();
        $u->save();
        $this->assertEquals(1, $u->pk);

        $i = new Issue([
            'user' => $u,
            'author' => $u,
        ]);
        $i->save();
        $this->assertEquals(1, $i->pk);
        $this->assertEquals(1, User1::objects()->count());
        $this->assertEquals(1, Issue::objects()->count());

        $qs = Issue::objects()->with(['user', 'author'])->order(['-created_at'])->asArray();
//        $this->assertEquals(implode(' ', [
//            'SELECT "orm_user_3"."id" AS "user__id", "orm_user_2"."id" AS "user__id", "orm_issue_1".*, "orm_issue_1"."created_at"',
//            'FROM "orm_issue" "orm_issue_1"',
//            'LEFT OUTER JOIN "orm_user" "orm_user_2" ON "orm_issue_1"."user_id" = "orm_user_2"."id"',
//            'LEFT OUTER JOIN "orm_user" "orm_user_3" ON "orm_issue_1"."author_id" = "orm_user_3"."id"',
//            'GROUP BY "orm_issue_1"."created_at", "orm_issue_1"."id", "orm_issue_1"."author_id", "orm_issue_1"."user_id", "orm_issue_1"."created_at"',
//            'ORDER BY "orm_issue_1"."created_at" DESC'
//        ]), $qs->allSql());
        $this->assertEquals(1, $qs->count());
        $item = $qs->get();
        unset($item['created_at']);
        $this->assertEquals([
            'author' => [
                'id' => 1
            ],
            'user' => [
                'id' => 1
            ],
            'id' => 1,
            'author_id' => 1,
            'user_id' => 1,
        ], $item);
    }
}