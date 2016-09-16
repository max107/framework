<?php
/**
 * Created by PhpStorm.
 * User: max
 * Date: 27/07/16
 * Time: 13:38
 */

namespace Mindy\Tests\Orm\Basic;

use Mindy\Tests\Orm\Models\Category;
use Mindy\Tests\Orm\Models\Product;
use Mindy\Tests\Orm\OrmDatabaseTestCase;

class SetterGetterTest extends OrmDatabaseTestCase
{
    public function getModels()
    {
        return [new Product, new Category];
    }

    public function testSimple()
    {
        $model = new Product();
        $model->name = 'example';
        $this->assertSame('example', $model->name);
    }

    public function testDefault()
    {
        $model = new Product();
        $this->assertSame('SIMPLE', $model->type);
        $model->type = '123';
        $this->assertSame('123', $model->type);
    }

    /**
     * @expectedException \Exception
     */
    public function testSetForeignField()
    {
        $category = new Category();
        $category->name = 'Toys';
        $this->assertSame('Toys', $category->name);

        $product = new Product();
        $this->assertNull($product->category);
        $this->assertNull($product->category_id);

        $product->category = $category;
        $this->assertNull($product->category);
        $this->assertNull($product->category_id);

        $product->category_id = 1;
        $this->assertSame(1, $product->category_id);
        $this->assertSame(1, $product->getAttribute('category_id'));
    }

    public function testSetGet()
    {
        $category = new Category(['name' => 'Toys']);
        $this->assertSame('Toys', $category->name);
        $this->assertTrue($category->save());

        $product = new Product();
        $this->assertNull($product->category);
        $this->assertNull($product->category_id);

        // Мы не храним полное состояние модели
        $product->category = $category;
        $this->assertInstanceOf(Category::class, $product->category);
        $this->assertNull($product->category_id);

        $product->category_id = 1;
        $this->assertSame(1, $product->category_id);
        $this->assertSame(1, $product->getAttribute('category_id'));
    }

    /**
     * @expectedException \Exception
     */
    public function testPropertyException()
    {
        $model = new Product();
        $model->this_property_does_not_exists = 'example';
    }

    /**
     * @expectedException \Exception
     */
    public function testGetFieldException()
    {
        $model = new Category();
        $model->getField('something');
    }

    public function testUnknownField()
    {
        $model = new Category();
        $field = $model->getField('something', false);
        $this->assertNull($field);
    }
}