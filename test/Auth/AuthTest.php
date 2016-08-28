<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:07
 */

namespace Mindy\Tests\Auth;

use Mindy\Auth\AuthProvider;
use Mindy\Auth\IUser;
use Mindy\Base\Mindy;

class UserExample implements IUser
{
    protected $attrs = [];

    public function __construct(array $attributes = [])
    {
        $this->attrs = $attributes;
    }

    /**
     * @return bool
     */
    public function isGuest() : bool
    {
        return $this->attrs['id'] === null;
    }
}

class MockAuthProvider extends AuthProvider
{
    public function logout() : bool
    {
        return true;
    }
}

class AuthTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Mindy::getInstance([
            'basePath' => __DIR__,
            'name' => 'auth_test'
        ]);
    }

    public function testLogin()
    {
        $auth = new MockAuthProvider;
        $user = new UserExample();
        $auth->setUser($user);
        $this->assertInstanceOf(IUser::class, $auth->getUser());

        $this->assertTrue($auth->login($user));

        $this->assertTrue($auth->logout());
    }
}