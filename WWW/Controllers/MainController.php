<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 25/08/16
 * Time: 23:25
 */

namespace WWW\Controllers;

use Mindy\Controller\BaseController;

class MainController extends BaseController
{
    public function getIndex($fistName = '?', $lastName = '?')
    {
        echo 123;
    }
}