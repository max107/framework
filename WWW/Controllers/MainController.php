<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 25/08/16
 * Time: 23:25
 */

namespace WWW\Controllers;

use function Mindy\app;
use Mindy\Controller\BaseController;

class MainController extends BaseController
{
    public function getIndex($fistName = '?', $lastName = '?')
    {
        $session = app()->http->session;
        $session->set('qwe', '123');
    }
}