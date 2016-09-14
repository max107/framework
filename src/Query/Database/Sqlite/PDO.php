<?php

namespace Mindy\Query\Database\Sqlite;

use Mindy\Query\Exception\Exception;

/**
 * Class PDO
 * @package Mindy\Query
 */
class PDO extends \Mindy\Query\PDO
{
    public function __construct($dsn, $username, $passwd, $options)
    {
        parent::__construct($dsn, $username, $passwd, $options);

        $regexCreated = $this->sqliteCreateFunction('regexp', function ($pattern, $value) {
            return (bool)preg_match($pattern, $value);
        }, 2);

        if ($regexCreated === false) {
            throw new Exception("Failed creating function regexp");
        }
    }
}
