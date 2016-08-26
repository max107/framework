<?php

$autoloader = include(__DIR__ . '/../vendor/autoload.php');

$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

defined('MINDY_DEBUG') or define('MINDY_DEBUG', true);
defined('MINDY_TRACE_LEVEL') or define('MINDY_TRACE_LEVEL', 3);
defined('MINDY_ENABLE_ERROR_HANDLER') or define('MINDY_ENABLE_ERROR_HANDLER', true);
defined('MINDY_ENABLE_EXCEPTION_HANDLER') or define('MINDY_ENABLE_EXCEPTION_HANDLER', true);

//if (MINDY_DEBUG) {
//    $whoops = new \Whoops\Run;
//    $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
//    $whoops->register();
//}

$app = \Mindy\Base\Mindy::getInstance(__DIR__ . '/config.php');
$app->run();
