<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 16/08/16
 * Time: 20:38
 */

declare(strict_types = 1);

namespace Mindy;

use Mindy\Base\Mindy;

/**
 * @return \Mindy\Base\Application|null
 */
function app()
{
    return Mindy::app();
}

/**
 * @param $domain
 * @param $message
 * @param array $parameters
 * @param null $locale
 * @return string
 */
function trans($domain, $message, array $parameters = [], $locale = null) : string
{
    return app()->locale->t($domain, $message, $parameters, $locale);
}

/**
 * @param $domain
 * @param $id
 * @param $number
 * @param array $parameters
 * @param null $locale
 * @return string
 */
function transChoice($domain, $id, $number, array $parameters = [], $locale = null) : string
{
    return app()->locale->transChoice($domain, $id, $number, $parameters, $locale);
}