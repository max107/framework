<?php
/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 06/01/14.01.2014 17:18
 */

namespace Mindy\Tests\Query;

use Exception;
use Mindy\Query\ConnectionManager;
use Mindy\QueryBuilder\LookupBuilder\Legacy;
use Mindy\QueryBuilder\QueryBuilder;
use ReflectionClass;

abstract class DatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConnectionManager
     */
    protected $cm;

    protected $driverName = 'mysql';

    protected $config = [];

    private static $params = [];

    /**
     * @return array
     */
    /**
     * @return array
     */
    public function getConfig()
    {
        $reflector = new ReflectionClass(get_class($this));
        $dir = dirname($reflector->getFileName());
        $configFile = @getenv('TRAVIS') ? 'config_travis.php' : 'config.php';
        return require($dir . '/' . $configFile);
    }

    public function setUp()
    {
        parent::setUp();

        $this->config = $this->getConfig();

        $this->cm = new ConnectionManager(['default' => $this->config]);
        $pdo_database = 'pdo_' . $this->driverName;

        if (!extension_loaded('pdo') || !extension_loaded($pdo_database)) {
            $this->markTestSkipped('pdo and ' . $pdo_database . ' extension are required.');
        }
    }

    protected function tearDown()
    {
        $this->cm->getDb()->close();
    }

    /**
     * @param bool $reset whether to clean up the test database
     * @param bool $open whether to open and populate test database
     * @return \Mindy\Query\Connection
     */
    public function getDb($reset = true, $open = true)
    {
        if (!$reset) {
            return $this->cm->getDb();
        }
        return $this->prepareDatabase($this->config['fixture'], $open);
    }

    public function prepareDatabase($fixture, $open = true)
    {
        $db = $this->cm->getDb();
        if (!$open) {
            return $db;
        }
        $db->open();
        if ($fixture !== null) {
            $lines = explode(';', file_get_contents($fixture));
            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    if ($db->pdo->exec($line) === false) {
                        var_dump($db->pdo->errorInfo());
                        die(1);
                    }
                }
            }
        }
        return $db;
    }

    /**
     * Returns a test configuration param from /data/config.php
     * @param  string $name params name
     * @param  mixed $default default value to use when param is not set.
     * @return mixed  the value of the configuration param
     */
    public static function getParam($name, $default = null)
    {
        if (static::$params === null) {
            static::$params = require(__DIR__ . '/config.php');
        }
        return isset(static::$params[$name]) ? static::$params[$name] : $default;
    }

    public function getAdapter()
    {
        throw new Exception('Not implemented');
    }

    protected function getSchema()
    {
        $connection = $this->getDb();
        if (isset($connection->schemaMap[$this->driverName])) {
            $schemaClass = $connection->schemaMap[$this->driverName];
            return new $schemaClass($connection);
        }
        throw new Exception('Unknown driver');
    }

    protected function getQueryBuilder()
    {
        $adapter = $this->getAdapter();
        $lookupBuilder = new Legacy;
        $schema = $this->getSchema();
        return new QueryBuilder($adapter, $lookupBuilder, $schema);
    }
}
