<?php

return [
    'basePath' => __DIR__,
    'modules' => [
        'Core' => [
            'class' => \Modules\Core\CoreModule::class
        ]
    ],
    'components' => [
        'http' => [
            'class' => '\Mindy\Http\Http',
            'middleware' => [
                'csrf' => [
                    'class' => \Mindy\Middleware\CsrfMiddleware::class
                ],
                'response_time' => [
                    'class' => \Mindy\Middleware\ResponseTimeMiddleware::class
                ]
            ],
        ],
        'urlManager' => [
            'class' => \Mindy\Router\UrlManager::class,
            'patterns' => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'urls.php')
        ]
    ]
];