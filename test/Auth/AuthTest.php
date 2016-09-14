<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 28/08/16
 * Time: 15:07
 */

namespace Mindy\Tests\Auth;

use Mindy\Auth\AuthProvider;
use Mindy\Auth\AuthProviderInterface;
use Mindy\Auth\IUser;
use Mindy\Auth\PasswordHasher\NullPasswordHasher;
use Mindy\Auth\Strategy\AuthStrategyInterface;
use Mindy\Auth\Strategy\LocalStrategy;
use Mindy\Auth\UserProvider\MemoryUserProvider;
use Mindy\Base\Application;
use Mindy\Base\Mindy;
use Mindy\Http\Cookie;
use Mindy\Http\Http;
use Mindy\Session\Adapter\MemorySessionAdapter;
use Mindy\Session\Session;
use Mindy\Tests\Base\TestApplication;

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
        unset($attrs['hash_type']);
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

    /**
     * @param array $attributes
     * @return mixed
     */
    public static function create(array $attributes) : IUser
    {
        return new self($attributes);
    }
}

class NullStrategyInterface implements AuthStrategyInterface
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
     * @param AuthProviderInterface $provider
     * @return mixed
     */
    public function setAuthProvider(AuthProviderInterface $provider)
    {
        $this->_auth = $provider;
        return $this;
    }

    /**
     * @return AuthProviderInterface
     */
    public function getAuthProvider() : AuthProviderInterface
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
        $this->app = new TestApplication([
            'basePath' => __DIR__,
            'name' => 'auth_test',
            'components' => [
                'auth' => [
                    'class' => '\Mindy\Auth\AuthProvider',
                    'userClass' => UserExample::class,
                    'strategies' => [
                        'local' => new NullStrategyInterface
                    ]
                ],
                /*
                'http' => function () {
                    $session = new Session();
                    $session->setHandler(new MemorySessionAdapter());

                    $http = new Http([
                        'session' => $session
                    ]);
                    return $http;
                },
                */
                'http' => [
                    'class' => '\Mindy\Http\Http',
                    'session' => new Session(['handler' => new MemorySessionAdapter])
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
        $this->assertInstanceOf(NullStrategyInterface::class, $this->app->auth->getStrategy('local'));
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
        $session = $this->app->http->session;
        $this->assertInstanceOf(MemorySessionAdapter::class, $session->getHandler());

        $auth = $this->app->auth;
        $user = $auth->getUser();
        $this->assertInstanceOf(IUser::class, $user);

        $this->assertTrue($user->isGuest());

        $this->assertEquals([
            'User not found'
        ], $auth->authenticate('local', [
            'username' => 'foo',
            'password' => '123'
        ]));

        $user->id = 1;
        $user->username = 'foo';
        $user->password = 'bar';

        $this->assertEquals([], $auth->authenticate('local', [
            'username' => 'foo',
            'password' => 'bar'
        ]));

        $this->assertEquals([
            'username' => 'foo',
            'id' => 1
        ], $session->get('__user'));

        $cookie = $this->app->http->getResponse()->getCookie('__user');
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertEquals(['username' => 'foo', 'id' => 1], unserialize($cookie->getValue()));
    }

    public function testLocalStrategy()
    {
        $authProvider = new AuthProvider([
            'userClass' => UserExample::class,
            'passwordHashers' => [
                'null' => new NullPasswordHasher()
            ]
        ]);

        $userProvider = new MemoryUserProvider($authProvider);
        $userProvider->setUsers([
            ['username' => 'foo', 'password' => 123, 'id' => 1, 'hash_type' => 'null', 'is_active' => true],
            ['username' => 'bar', 'password' => 321, 'id' => 2, 'hash_type' => 'null', 'is_active' => true]
        ]);
        $this->assertNull($userProvider->get(['username' => 'foo', 'password' => 321]));
        $user = $userProvider->get(['username' => 'foo', 'password' => 123]);
        $this->assertNotNull($user);

        $strategy = new LocalStrategy($authProvider, $userProvider);

        $this->assertTrue($strategy->process($user, ['username' => 'foo', 'password' => 123]));
        $authUser = $strategy->getUser();
        $this->assertEquals([
            'username' => 'foo',
            'id' => 1,
            'is_active' => true,
        ], $authUser->getSafeAttributes());

        $this->assertFalse($strategy->process($user, ['username' => 'foo', 'password' => 321]));
        $errors = $strategy->getErrors();
        $this->assertEquals(['password' => ['Wrong password']], $errors);
    }
}