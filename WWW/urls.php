<?php

class ExampleController extends \Mindy\Controller\BaseController
{
    public function actionIndex()
    {
        echo 'controller index';
    }
}

return [
    '/controller/' => [
        'restful' => \App\Controllers\MainController::class
    ],
    '/user/{name:c}?' => [
        'name' => 'view_user',
        'callback' => function ($name = null) {
            echo $name;
        },
        'params' => [
            'csrf' => false
        ]
    ],
    '/test/' => [
        'callback' => [
            ExampleController::class => 'index'
        ]
    ]
];