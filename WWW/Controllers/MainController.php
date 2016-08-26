<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 25/08/16
 * Time: 23:25
 */

namespace WWW\Controllers;

use Mindy\Controller\BaseController;
use Mindy\Http\Response\JsonResponse;

class MainController extends BaseController
{
    public function getIndex($fistName = '?', $lastName = '?')
    {
        return new JsonResponse(200, [], ['foo' => 'bar']);
    }
}