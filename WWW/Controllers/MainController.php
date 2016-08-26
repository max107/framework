<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 25/08/16
 * Time: 23:25
 */

namespace WWW\Controllers;

use function GuzzleHttp\Psr7\stream_for;
use Mindy\Controller\BaseController;
use Mindy\Http\Response;

class MainController extends BaseController
{
    public function getIndex($firstName = '?', $lastName = '?')
    {
        $response = (new Response())
            ->withBody(stream_for('Hello world: ' . $firstName . ' - ' . $lastName));
        $this->getRequest()->send($response);
    }
}