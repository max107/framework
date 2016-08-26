<?php

$autoloader = include(__DIR__ . '/../vendor/autoload.php');

// Using the createServer factory, providing it with the various superglobals:
$server = Zend\Diactoros\Server::createServer(
    function ($request, $response, $done) {
        $response->getBody()->write("Hello world!");
    },
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

$server->listen();