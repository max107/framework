<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 22:00
 */

declare(strict_types = 1);

namespace Mindy\Session\Adapter;

class RedisSessionAdapter extends NativeSessionAdapter
{
    public function __construct()
    {
        parent::__construct();

        $this->setIniOptions([
            'save_handler' => 'redis',
            'save_path' => "tcp://127.0.0.1:6379"
        ]);
    }
}