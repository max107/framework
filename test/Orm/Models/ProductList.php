<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Falaleev Maxim
 * @email max@studio107.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 04/03/14.03.2014 01:17
 */

namespace Mindy\Tests\Orm\Models;

use Mindy\Orm\Fields\CharField;
use Mindy\Orm\Fields\DateTimeField;
use Mindy\Orm\Fields\ManyToManyField;
use Mindy\Orm\Model;

/**
 * Class ProductList
 * @package Mindy\Tests\Orm\Models
 * @property string name
 * @property \Mindy\Orm\ManyToManyManager products
 */
class ProductList extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class
            ],
            'products' => [
                'class' => ManyToManyField::class,
                'modelClass' => Product::class,
                'throughLink' => ['product_list_id', 'product_id']
            ],
            'date_action' => [
                'class' => DateTimeField::class,
                'required' => false,
                'null' => true
            ]
        ];
    }
}
