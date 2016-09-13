<?php

namespace Mindy\Tests\Form;

use Mindy\Form\Fields\CharField;
use Mindy\Form\Widget\LicenseWidget;
use Mindy\Form\Widget\RatingWidget;
use PHPUnit_Framework_TestCase;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:46
 */
class WidgetTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $widget = new LicenseWidget(['content' => 'foo bar']);
        $this->assertEquals('foo bar', $widget->render());
    }

    public function testField()
    {
        $field = new CharField([
            'name' => 'foo',
            'widget' => new LicenseWidget(['content' => 'foo bar'])
        ]);
        $this->assertEquals("foo bar <input type='text' value='' id='foo' name=''/>", $field->renderInput());
    }

    public function testRating()
    {
        $field = new CharField([
            'name' => 'foo',
            'widget' => new RatingWidget(['content' => 'foo bar'])
        ]);
        $this->assertContains('raty', $field->renderInput());
    }
}