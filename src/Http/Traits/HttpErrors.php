<?php

namespace Mindy\Http\Traits;

use Mindy\Base\Mindy;
use Mindy\Exception\HttpException;

/**
 * Class HttpErrors
 * @package Mindy\Http
 */
trait HttpErrors
{
    public function errorMessage($code)
    {
        $t = Mindy::app()->translate;
        $codes = [
            400 => $t->t('main', 'Invalid request. Please do not repeat this request again.'),
            403 => $t->t('main', 'You are not authorized to perform this action.'),
            404 => $t->t('main', 'The requested page does not exist.'),
            500 => $t->t('main', 'Error'),
        ];
        return isset($codes[$code]) ? $codes[$code] : 'Unknown error';
    }

    /**
     * @param $code
     * @param null $message
     * @throws HttpException
     */
    public function error($code, $message = null)
    {
        throw new HttpException($code, $message === null ? $this->errorMessage($code) : $message);
    }
}