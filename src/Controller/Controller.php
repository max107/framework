<?php

namespace Mindy\Controllers;

use function Mindy\app;
use Mindy\Controller\BaseController;
use Mindy\Helper\Traits\RenderTrait;

/**
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 02/04/14.04.2014 16:47
 */
class Controller extends BaseController
{
    use RenderTrait;

    public function render($view, array $data = [])
    {
        return $this->renderTemplate($view, array_merge([
            'debug' => MINDY_DEBUG,
            'this' => $this,
            'app' => app()
        ], $data));
    }
}
