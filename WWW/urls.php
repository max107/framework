<?php

class ExampleController extends \Mindy\Controller\BaseController
{
    public function actionIndex()
    {
        echo 'controller index';
    }
}

return [
    '/{name:c}?' => [
        'callback' => function ($name = null) {
            echo $name;
        },
        'params' => [
            'csrf' => false
        ]
    ],
    '/test/' => [
        'callback' => [ExampleController::class => 'index']
    ]
];