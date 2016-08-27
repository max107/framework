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
    public $servers = [
        "tcp://127.0.0.1:6379"
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->setIniOptions([
            'save_handler' => 'redis',
            'save_path' => $this->getServerString()
        ]);
    }

    public function getServerString()
    {
        return implode(', ', $this->servers);
    }
}