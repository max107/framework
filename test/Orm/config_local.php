<?php

return [
    'mysql' => [
        'url' => 'mysql://root@127.0.0.1/test?charset=utf8',
        'driver' => 'pdo_mysql'
    ],
    'pgsql' => [
        'dsn' => 'pgsql://root@localhost:5432/test',
        'driver' => 'pdo_pgsql'
    ],
    'sqlite' => [
        'memory' => true,
        'driverClass' => '\Mindy\QueryBuilder\Driver\SqliteDriver',
    ]
];
