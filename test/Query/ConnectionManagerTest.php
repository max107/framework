<?php
use Mindy\Helper\Creator;
use Mindy\Query\Connection;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 30/06/16
 * Time: 10:37
 */

class ConnectionManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreator()
    {
        $configs = [
            [
                'class' => \Mindy\Query\ConnectionManager::class,
                ['default' => ['class' => Connection::class]]
            ],
            [
                'class' => \Mindy\Query\ConnectionManager::class,
                'databases' => ['default' => ['class' => Connection::class]]
            ],
            [
                'class' => \Mindy\Query\ConnectionManager::class,
                'databases' => ['default' => Connection::class]
            ]
        ];
        foreach ($configs as $config) {
            $this->assertTrue(Creator::createObject($config)->hasDb('default'));
        }
    }

    public function testInit()
    {
        $connections = [
            'default' => [
                'class' => Connection::class
            ]
        ];

        $cm = new \Mindy\Query\ConnectionManager(['databases' => $connections]);
        $this->assertNotNull($cm->getDb('default'));
        $this->assertTrue($cm->getDb('default') instanceof Connection);
        $this->assertTrue($cm->getDb() instanceof Connection);
        $connection = $cm->getDb();
        $this->assertTrue($cm->getDb($connection) instanceof Connection);
    }

    public function testMissing()
    {
        $connections = [
            'sqlite' => [
                'class' => Connection::class
            ]
        ];
        $cm = new \Mindy\Query\ConnectionManager(['databases' => $connections]);
        $this->assertFalse($cm->hasDb('default'));
    }
}