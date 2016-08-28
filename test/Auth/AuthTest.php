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

    public function tearDown()
    {
        Mindy::setApplication(null);
    }

    public function testLogin()
    {
        $auth = new MockAuthProvider;
        $user = new UserExample();
        $auth->setUser($user);
        $this->assertInstanceOf(IUser::class, $auth->getUser());

        $this->assertTrue($user->isGuest());
        $this->assertFalse($auth->login($user));
        $user->id = 1;
        $this->assertFalse($user->isGuest());
        $this->assertTrue($auth->login($user));

        $this->assertTrue($auth->logout());
    }
}