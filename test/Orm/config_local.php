<?php

return [
    'mysql' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'mysql:host=localhost;dbname=test_orm',
        'username' => 'root',
        'charset' => 'utf8',
    ],
    'sqlite' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'sqlite:' . __DIR__ . '/app/sqlite.db',
//        'dsn' => 'sqlite::memory:',
    ],
    'pgsql' => [
        'class' => '\Mindy\Query\Connection',
        'dsn' => 'pgsql:host=localhost;dbname=test_orm',
        'username' => 'root'
    ]
];
