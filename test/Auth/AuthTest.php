<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:07
 */

namespace Mindy\Tests\Auth;

use Mindy\Auth\AuthProvider;
use Mindy\Auth\IAuthProvider;
use Mindy\Auth\IUser;
use Mindy\Auth\Strategy\IAuthStrategy;
use Mindy\Base\Application;
use Mindy\Base\Mindy;
use Mindy\Http\Cookie;
use Mindy\Http\Http;
use Mindy\Session\Adapter\MemorySessionAdapter;
use Mindy\Session\Session;

class UserExample implements IUser
{
    protected $attrs = [];

    public function __construct(array $attributes = [])
    {
        $this->attrs = $attributes;
    }

    public function getSafeAttributes() : array
    {
        $attrs = $this->attrs;
        unset($attrs['password']);
        return $attrs;
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

class NullStrategy implements IAuthStrategy
{
    protected $user;

    private $_auth;

    /**
     * @param IUser $user
     * @param array $attributes
     * @return bool
     */
    public function process(IUser $user, array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $user->{$key} = $value;
        }

        $status = $attributes['username'] == 'foo' && $attributes['password'] == 'bar';
        if ($status) {
            $user->id = 1;

            $this->user = $user;
        }
        return $status;
    }

    /**
     * @return array
     */
    public function getErrors() : array
    {
        if ($this->user === null) {
            return [
                'User not found'
            ];
        }

        return [];
    }

    /**
     * @param IAuthProvider $provider
     * @return mixed
     */
    public function setAuthProvider(IAuthProvider $provider)
    {
        $this->_auth = $provider;
        return $this;
    }

    /**
     * @return IAuthProvider
     */
    public function getAuthProvider() : IAuthProvider
    {
        return $this->_auth;
    }

    /**
     * @return IUser
     */
    public function getUser() : IUser
    {
        return $this->user;
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
    protected $app;

    public function setUp()
    {
        $this->app = Mindy::getInstance([
            'basePath' => __DIR__,
            'name' => 'auth_test',
            'components' => [
                'auth' => [
                    'class' => '\Mindy\Auth\AuthProvider',
                    'userClass' => UserExample::class,
                    'strategies' => [
                        'local' => new NullStrategy
                    ]
                ],
                'http' => [
                    'class' => '\Mindy\Http\Http',
                    'session' => [
                        'class' => '\Mindy\Session\Session',
                        'handler' => [
                            'class' => '\Mindy\Session\Adapter\MemorySessionAdapter'
                        ]
                    ]
                ],
            ]
        ]);
    }

    public function tearDown()
    {
        Mindy::setApplication(null);
    }

    public function testInit()
    {
        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertInstanceOf(AuthProvider::class, $this->app->auth);
        $this->assertInstanceOf(NullStrategy::class, $this->app->auth->getStrategy('local'));
        $this->assertInstanceOf(Http::class, $this->app->http);
        $this->assertInstanceOf(Session::class, $this->app->http->session);
        $this->assertInstanceOf(MemorySessionAdapter::class, $this->app->http->session->getHandler());
    }

    public function testIsGuest()
    {
        $auth = $this->app->auth;
        $user = $auth->getUser();
        $auth->setUser($user);
        $this->assertInstanceOf(IUser::class, $auth->getUser());

        $this->assertTrue($user->isGuest());
        $this->assertFalse($auth->login($user));
        $user->id = 1;
        $this->assertFalse($user->isGuest());
        $this->assertTrue($auth->login($user));

        $this->assertTrue($auth->logout());
    }

    public function testLogin()
    {
        $auth = $this->app->auth;
        $user = $auth->getUser();
        $this->assertInstanceOf(IUser::class, $user);

        $this->assertTrue($user->isGuest());
        $this->assertFalse($auth->login($user));

        $this->assertEquals([
            'User not found'
        ], $auth->authenticate('local', [
            'username' => 'foo',
            'password' => '123'
        ]));

        $this->assertEquals([], $auth->authenticate('local', [
            'username' => 'foo',
            'password' => 'bar'
        ]));

        $session = $this->app->http->session;
        $this->assertEquals([
            '__user' => ['username' => 'foo', 'id' => 1]
        ], $session->all());

        $cookie = $this->app->http->getResponse()->getCookie('__user');
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals(['username' => 'foo', 'id' => 1], unserialize($cookie->getValue()));
    }
}