<?php

return [
    'basePath' => __DIR__,
    'middleware' => [
        'csrf' => ['class' => \Mindy\Middleware\CsrfMiddleware::class]
    ],
    'modules' => [
        'Core' => [
            'class' => \Modules\Core\CoreModule::class
        ]
    ],
    'components' => [
        'urlManager' => [
            'class' => \Mindy\Router\UrlManager::class,
            'patterns' => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'urls.php')
        ]
    ]
];