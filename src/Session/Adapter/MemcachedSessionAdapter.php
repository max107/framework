<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/08/16
 * Time: 20:32
 */

declare(strict_types = 1);

namespace Mindy\Session\Adapter;

class MemcachedSessionAdapter extends BaseSessionAdapter
{
    public $servers = [
        "127.0.0.1:11211?persistent=1&weight=1&timeout=1&retry_interval=15"
    ];

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->setIniOptions([
            'save_handler' => 'memcached',
            'save_path' => $this->getServerString()
        ]);
    }

    public function getServerString()
    {
        return implode(', ', $this->servers);
    }
}