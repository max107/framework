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
 * @date 03/01/14.01.2014 23:20
 */

namespace Mindy\Tests\Orm;

use Doctrine\DBAL\Connection;
use League\Flysystem\Adapter\Local;
use Mindy\Base\Mindy;
use Mindy\Orm\Sync;
use Mindy\QueryBuilder\ConnectionManager;
use Mindy\QueryBuilder\QueryBuilder;

class OrmDatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    public $settings = [];
    /**
     * @var string
     */
    public $driver = 'sqlite';
    /**
     * @var ConnectionManager
     */
    protected $manager;
    /**
     * @var Connection
     */
    protected $connection;

    public function setUp()
    {
        if (extension_loaded('pdo_' . $this->driver) === false) {
            $this->markTestSkipped('pdo_' . $this->driver . ' ext required');
        }

        Mindy::setApplication(null);
        $this->app = Mindy::getInstance([
            'basePath' => __DIR__ . '/app/protected',
            'webPath' => __DIR__ . '/app',
            'components' => [
                /*
                'db' => [
                    'class' => \Mindy\Query\ConnectionManager::class,
                    'databases' => require(__DIR__ . (@getenv('TRAVIS') ? '/config_travis.php' : '/config_local.php'))
                ],
                */
                'db' => function () {
                    $databases = require(__DIR__ . (@getenv('TRAVIS') ? '/config_travis.php' : '/config_local.php'));
                    return new ConnectionManager($databases, $this->driver);
                },
                'storage' => [
                    'class' => '\Mindy\Storage\Storage',
                    'adapters' => [
                        'default' => new Local(__DIR__)
                    ]
                ],
            ]
        ]);

        $this->initModels($this->getModels(), $this->getConnection());
    }

    protected function assertSql($expected, $actual)
    {
        $sql = QueryBuilder::getInstance($this->getConnection())->getAdapter()->quoteSql(str_replace([" \n", "\n"], " ", $expected));
        $this->assertEquals($sql, trim($actual));
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return Mindy::app()->db->getConnection($this->driver);
    }

    protected function getModels()
    {
        return [];
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->dropModels($this->getModels(), $this->getConnection());
        Mindy::setApplication(null);
        $this->app = null;
    }

    public function initModels(array $models, Connection $connection)
    {
        $sync = new Sync($models, $connection);
        $sync->delete();
        $sync->create();
    }

    public function dropModels(array $models, Connection $connection)
    {
        $sync = new Sync($models, $connection);
        $sync->delete();
    }

    public function getConnectionType()
    {
        $params = explode(':', $this->connection->dsn);
        return array_pop($params);
    }
}
