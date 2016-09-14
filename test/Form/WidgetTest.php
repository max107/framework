<?php

namespace Mindy\Tests\Form;

use Mindy\Form\Fields\TextField;
use Mindy\Form\Form;
use Mindy\Form\Widget\LicenseWidget;
use Mindy\Tests\Form\Forms\FooForm;
use PHPUnit_Framework_TestCase;

/**
 * Created by PhpStorm.
 * User: max
 * Date: 21/07/16
 * Time: 11:46
 */
class WidgetTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Form::$ids = [];
    }

    public function tearDown()
    {
        Form::$ids = [];
    }

    public function testSimple()
    {
        $form = new FooForm();
        $field = new TextField([
            'name' => 'foo'
        ]);
        $field->setForm($form);
        $widget = new LicenseWidget(['content' => 'foo bar']);
        $this->assertContains('foo bar', $widget->render($field));
        $this->assertContains("<label for='FooForm_1_foo'>", $widget->render($field));
    }
}