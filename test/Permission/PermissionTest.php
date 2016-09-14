<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 18:29
 */

namespace Mindy\Permissions;

use Mindy\Auth\GroupInterface;
use Mindy\Auth\UserInterface;

class GroupInterfacePerm implements GroupInterface
{
    protected $attrs = [];

    public function __construct(array $attributes = [])
    {
        $this->attrs = $attributes;
    }

    public function __get($name)
    {
        return $this->attrs[$name];
    }
}

class UserInterfacePerm implements UserInterface
{
    protected $attrs = [];

    public function __construct(array $attributes = [])
    {
        $this->attrs = $attributes;
    }

    public function __get($name)
    {
        return $this->attrs[$name];
    }

    public function __set($name, $value)
    {
        $this->attrs[$name] = $value;
    }

    /**
     * @return bool
     */
    public function isGuest() : bool
    {
        if (isset($this->attrs['id'])) {
            return $this->attrs['id'] === null;
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSafeAttributes() : array
    {
        return $this->attrs;
    }

    /**
     * @param array $attributes
     * @return mixed
     */
    public static function create(array $attributes) : UserInterface
    {
        return new self($attributes);
    }
}

class PermissionTest extends \PHPUnit_Framework_TestCase
{
    public function testPermissions()
    {
        $permissions = [
            ['code' => 'foo', 'users' => [1], 'groups' => [2]],
            ['code' => 'foo_biz_rule', 'users' => [1], 'groups' => [2], 'biz_rule' => 'a == b']
        ];

        $permissions = new PermissionManager([
            'permissions' => $permissions
        ]);

        $user = new UserInterfacePerm([
            'id' => 1,
            'is_superuser' => false,
            'groups' => [
                new GroupInterfacePerm(['id' => 2])
            ]
        ]);

        $this->assertTrue($permissions->can($user, 'foo'));
        $this->assertTrue($permissions->can($user, 'foo', ['a' => 1, 'b' => 1]));
        $this->assertTrue($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 1]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 2]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule'));

        $user = new UserInterfacePerm([
            'id' => 2,
            'is_superuser' => false,
            'groups' => [
                new GroupInterfacePerm(['id' => 2])
            ]
        ]);
        $this->assertTrue($permissions->can($user, 'foo'));
        $this->assertTrue($permissions->can($user, 'foo', ['a' => 1, 'b' => 1]));
        $this->assertTrue($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 1]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 2]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule'));

        $user = new UserInterfacePerm([
            'id' => 3,
            'is_superuser' => false,
            'groups' => [
                new GroupInterfacePerm(['id' => 3])
            ]
        ]);
        $this->assertFalse($permissions->can($user, 'foo'));
        $this->assertFalse($permissions->can($user, 'foo', ['a' => 1, 'b' => 1]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 1]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule', ['a' => 1, 'b' => 2]));
        $this->assertFalse($permissions->can($user, 'foo_biz_rule'));
    }

    public function testIsGlobal()
    {
        $perm = new Permission([
            'is_global' => true
        ]);
        $this->assertTrue($perm->getIsGlobal());
    }

    public function testBizRule()
    {
        $perm = new Permission([
            'biz_rule' => 'a == b'
        ]);
        $this->assertTrue($perm->evaluateBizRule(['a' => 1, 'b' => 1]));
        $this->assertFalse($perm->evaluateBizRule(['a' => 1, 'b' => 2]));
    }
}
