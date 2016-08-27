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
        /** @var \Mindy\Session\Session $session */
        $session = app()->http->session;
        $session->start();
//        $session->set('baz', 'qwe');
        $data = $session->all();
        d(
            $data,
            ini_get('session.save_path'),
            ini_get('session.save_handler')
        );
    }
}