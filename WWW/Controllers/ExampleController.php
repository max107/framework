<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 26/08/16
 * Time: 20:28
 */

namespace WWW\Controllers;

class ExampleController extends \Mindy\Controller\BaseController
{
    public function actionIndex()
    {
        echo 'controller index';
    }
}