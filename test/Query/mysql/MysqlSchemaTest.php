<?php

namespace Mindy\Tests\Query;
use Mindy\QueryBuilder\Database\Mysql\Adapter;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 30/06/16
 * Time: 15:49
 */
class MysqlSchemaTest extends SchemaTest
{
    public $driverName = 'mysql';

    public function getAdapter()
    {
        return new Adapter();
    }
}