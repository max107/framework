<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 20:38
 */

declare(strict_types = 1);

namespace Mindy;

use Mindy\Base\Application;
use Mindy\Base\Mindy;

/**
 * @return \Mindy\Base\Application|null
 */
function app()
{
    return Mindy::app();
}
