<?php

use function GuzzleHttp\Psr7\stream_for;
use function Mindy\app;

return [
    [
        'route' => '/controller/',
        'restful' => \WWW\Controllers\MainController::class
    ],
    [
        'route' => '/user/{name:c}?',
        'name' => 'view_user',
        'callback' => function ($name = null) {
            $response = app()->request->getResponse();
            return $response
                ->withStatus(200)
                ->withBody(stream_for($name));
        },
        'params' => [
            'csrf' => false
        ]
    ],
    [
        'route' => '/test/',
        'callback' => [
            \WWW\Controllers\ExampleController::class => 'index'
        ]
    ]
];