<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 15/09/16
 * Time: 14:20
 */

namespace Mindy\QueryBuilder;

use Doctrine\DBAL\DriverManager;

class ConnectionManager
{
    protected $defaultConnection;
    protected $connections = [];

    public function __construct(array $connections, $defaultConnection, $configuration = null, $eventManager = null)
    {
        if (array_key_exists($defaultConnection, $connections) === false) {
            throw new \Exception('Please set connection with "default" key');
        }
        $this->defaultConnection = $defaultConnection;
        foreach ($connections as $name => $config) {
            $this->connections[$name] = DriverManager::getConnection($config, $configuration, $eventManager);
        }
    }

    public function getConnection($name = null)
    {
        if (empty($name)) {
            $name = $this->defaultConnection;
        }
        return $this->connections[$name];
    }
}