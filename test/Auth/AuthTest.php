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
use Mindy\Auth\Strategy\BaseStrategy;
use Mindy\Auth\UserInterface;
use Mindy\Auth\PasswordHasher\NullPasswordHasher;
use Mindy\Auth\Strategy\AuthStrategyInterface;
use Mindy\Auth\Strategy\LocalStrategy;
use Mindy\Auth\UserProvider\MemoryUserProvider;
use Mindy\Base\Application;
use Mindy\Base\Mindy;
use Mindy\Creator\Creator;
use Mindy\Http\Cookie;
use Mindy\Http\Http;
use Mindy\Session\Handler\MemorySessionHandler;
use Mindy\Session\Session;
use Mindy\Tests\Base\TestApplication;

class UserInterfaceExample implements UserInterface
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
    public static function create(array $attributes) : UserInterface
    {
        return new self($attributes);
    }
}

class NullStrategyInterface extends BaseStrategy implements AuthStrategyInterface
{
    protected $user;

    private $_auth;

    /**
     * @param UserInterface $user
     * @param array $attributes
     * @return bool
     */
    public function process(UserInterface $user, array $attributes)
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
     * @return UserInterface
     */
    public function getUser() : UserInterface
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
                    'userClass' => UserInterfaceExample::class,
                    'strategies' => [
                        'local' => NullStrategyInterface::class
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
                    'session' => new Session(['handler' => new MemorySessionHandler])
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
        $auth = Creator::createObject([
            'class' => '\Mindy\Auth\AuthProvider',
            'userProvider' => ['class' => MemoryUserProvider::class],
            'userClass' => UserInterfaceExample::class,
            'strategies' => [
                'local' => ['class' => NullStrategyInterface::class]
            ]
        ]);
        $this->assertInstanceOf(AuthProvider::class, $auth);

        $this->assertInstanceOf(Application::class, $this->app);
        $this->assertInstanceOf(AuthProvider::class, $this->app->auth);
        $this->assertInstanceOf(NullStrategyInterface::class, $this->app->auth->getStrategy('local'));
        $this->assertInstanceOf(Http::class, $this->app->http);
        $this->assertInstanceOf(Session::class, $this->app->http->session);
        $this->assertInstanceOf(MemorySessionHandler::class, $this->app->http->session->getHandler());
    }

    public function testIsGuest()
    {
        $auth = $this->app->auth;
        $user = $auth->getUser();
        $auth->setUser($user);
        $this->assertInstanceOf(UserInterface::class, $auth->getUser());

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
        $this->assertInstanceOf(MemorySessionHandler::class, $session->getHandler());

        $auth = $this->app->auth;
        $user = $auth->getUser();
        $this->assertInstanceOf(UserInterface::class, $user);

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
            'userClass' => UserInterfaceExample::class,
            'userProvider' => [
                'class' => MemoryUserProvider::class,
                'users' => [
                    ['username' => 'foo', 'password' => 123, 'id' => 1, 'hash_type' => 'null', 'is_active' => true],
                    ['username' => 'bar', 'password' => 321, 'id' => 2, 'hash_type' => 'null', 'is_active' => true]
                ]
            ],
            'passwordHashers' => [
                'null' => new NullPasswordHasher()
            ]
        ]);

        $userProvider = $authProvider->getUserProvider();

        $this->assertNull($userProvider->get(['username' => 'foo', 'password' => 321]));
        $user = $userProvider->get(['username' => 'foo', 'password' => 123]);
        $user = $authProvider->createUser($user);
        $this->assertNotNull($user);
        $this->assertInstanceOf(UserInterface::class, $user);

        $strategy = new LocalStrategy($authProvider);

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