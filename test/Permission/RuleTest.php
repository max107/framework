<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/09/16
 * Time: 12:00
 */

namespace Mindy\Tests\Permission;

use Mindy\Permissions\Rule;

class RuleTest extends \PHPUnit_Framework_TestCase
{
    public function testUser()
    {
        $this->assertFalse((new RuleUser(['id' => 1]))->isGuest());
        $this->assertTrue((new RuleUser(['id' => null]))->isGuest());
    }

    public function testUsers()
    {
        $rule = new Rule([
            'users' => '@'
        ]);
        $this->assertFalse($rule->can(new RuleUser(['id' => null])));
        $this->assertTrue($rule->can(new RuleUser(['id' => 1])));

        $rule = new Rule();
        $this->assertTrue($rule->can(new RuleUser(['id' => null])));
        $this->assertTrue($rule->can(new RuleUser(['id' => 1])));

        $rule = new Rule(['users' => '*']);
        $this->assertTrue($rule->can(new RuleUser(['id' => null])));
        $this->assertTrue($rule->can(new RuleUser(['id' => 1])));

        $rule = new Rule(['users' => ['admin', 'manager']]);
        $this->assertTrue($rule->can(new RuleUser(['id' => null, 'username' => 'admin'])));
        $this->assertTrue($rule->can(new RuleUser(['id' => 1, 'username' => 'manager'])));
        $this->assertFalse($rule->can(new RuleUser(['id' => 1, 'username' => 'mike'])));

        $rule = new Rule(['users' => ['?']]);
        $this->assertTrue($rule->can(new RuleUser(['id' => null])));
        $this->assertFalse($rule->can(new RuleUser(['id' => 1])));
    }

    public function testGroups()
    {
        $rule = new Rule(['groups' => 'admins']);

        $group1 = new \stdClass();
        $group1->name = 'managers';

        $group2 = new \stdClass();
        $group2->name = 'admins';

        $admin = new RuleUser(['id' => 1, 'groups' => [
            $group1,
            $group2
        ]]);
        $manager = new RuleUser(['id' => 1, 'groups' => [
            $group1,
        ]]);

        $this->assertTrue($rule->can($admin));
        $this->assertFalse($rule->can($manager));
    }

    public function testExpression()
    {
        $rule = new Rule(['expression' => '1==2']);
        $this->assertFalse($rule->can(new RuleUser(['id' => null])));
        $this->assertFalse($rule->can(new RuleUser(['id' => 1])));

        $rule = new Rule(['expression' => 'user.id == 3']);
        $this->assertFalse($rule->can(new RuleUser(['id' => null])));
        $this->assertFalse($rule->can(new RuleUser(['id' => 1])));

        $rule = new Rule(['users' => '@', 'expression' => 'user.id < 2']);
        $this->assertFalse($rule->can(new RuleUser(['id' => 3])));
        $this->assertTrue($rule->can(new RuleUser(['id' => 1])));
    }

    public function testIp()
    {
        $rule = new Rule(['ips' => [
            '127.0.0.1',
            '192.168.0.1'
        ]]);
        $user = new RuleUser(['id' => null]);
        $this->assertFalse($rule->can($user, '123.123.123.123'));
        $this->assertTrue($rule->can($user, '127.0.0.1'));
    }
}