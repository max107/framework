<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 20:32
 */

declare(strict_types = 1);

namespace Mindy\Session\Adapter;

class MemcachedSessionAdapter extends NativeSessionAdapter
{
    public function __construct()
    {
        parent::__construct();

        $this->setIniOptions([
            'save_handler' => 'memcached',
            'save_path' => "tcp://127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15"
        ]);
    }
}