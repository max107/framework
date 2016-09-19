<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 19/09/16
 * Time: 15:10
 */

require(__DIR__ . '/../vendor/autoload.php');

use Mindy\Base\Mindy;
use Mindy\QueryBuilder\ConnectionManager;

$databases = [
    'default' => [
        'user' => '',
        'password' => '',
        'host' => 'localhost',
        'memory' => true,
        'driverClass' => '\Mindy\QueryBuilder\Driver\SqliteDriver',
    ]
];

$app = Mindy::getInstance([
    'name' => 'my-app',
    'basePath' => __DIR__,
    'components' => [
        'db' => function () use ($databases) {
            return new ConnectionManager($databases, 'default');
        }
    ],
    'modules' => [
        'Test' => [
            'class' => '\Modules\Test\TestModule'
        ]
    ]
]);

$app->run();