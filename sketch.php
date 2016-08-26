<?php

$router = new stdClass();
$router->group('/blog')->attach();

$patterns = [
    '/' => ['Controller', 'action'],
    '/blog' => new RouteGroup([
        [
            '/post/{id}' => [
                'callback' => ['BlogController', 'view'],
                'method' => 'get',
                'params' => [
                    'csrf' => false
                ]
            ],
        ],
        [
            '/post/{id}' => [
                'callback' => ['BlogController', 'view'],
                'method' => 'post',
                'params' => [
                    'csrf' => false
                ]
            ]
        ]
    ])
];

$router->attach($patterns);