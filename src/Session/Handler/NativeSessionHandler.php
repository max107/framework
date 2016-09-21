<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 07.08.16
 * Time: 19:58
 */

declare(strict_types = 1);

namespace Mindy\Session\Handler;

class NativeSessionHandler extends AbstractSessionHandler
{
    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->setIniOptions(['cache_limiter' => "public"]);
    }
}