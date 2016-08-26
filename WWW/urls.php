<?php

use function GuzzleHttp\Psr7\stream_for;
use Mindy\Base\Mindy;

class ExampleController extends \Mindy\Controller\BaseController
{
    public function actionIndex()
    {
        echo 'controller index';
    }
}

return [
    '/controller/' => [
        'restful' => \WWW\Controllers\MainController::class
    ],
    '/user/{name:c}?' => [
        'name' => 'view_user',
        'callback' => function ($name = null) {
            echo $name;
            die();
            
            $response = Mindy::app()->request->getResponse();
            return $response
                ->withStatus(200)
                ->withBody(stream_for($name))
                ->withCookie(['name' => 'name', 'value' => 'value']);
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