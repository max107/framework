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
 * @date 20/10/14.10.2014 16:34
 */

namespace Mindy\Tests\Form;

use Mindy\Form\Fields\CharField;
use Mindy\Form\Fields\Field;
use Mindy\Form\Fields\TextField;
use Mindy\Form\Form;

class TestForm extends Form
{
    public function getFields()
    {
        return [
            'name' => CharField::class,
            'content' => [
                'class' => TextField::class
            ]
        ];
    }

    public function render($template = null)
    {
        if (empty($template)) {
            $template = $this->template;
        }
        return [
            'template' => $template,
            'fields' => $this->getRenderFields(),
            'form' => $this,
            'errors' => $this->getErrors()
        ];
    }
}

class FormTest extends \PHPUnit_Framework_TestCase
{
    public function testInit()
    {
        $form = new TestForm;
        $this->assertEquals(2, count($form->getFieldsInit()));
    }

    public function testSetAttributes()
    {
        $form = new TestForm([
            'attributes' => ['name' => 'foo']
        ]);
        $this->assertEquals(['name' => 'foo', 'content' => null], $form->getAttributes());

        $form = new TestForm();
        $form->setAttributes(['name' => 'foo']);
        $this->assertEquals(['name' => 'foo', 'content' => null], $form->getAttributes());
    }

    public function testIterate()
    {
        $form = new TestForm;
        foreach ($form as $field) {
            $this->assertInstanceOf(Field::class, $field);
        }
    }

    public function testArrayAccess()
    {
        $form = new TestForm();
        $form->setAttributes(['name' => 'foo']);
        $this->assertEquals('foo', $form['name']->getValue());

        $form = new TestForm(['attributes' => ['name' => 'foo']]);
        $this->assertEquals('foo', $form['name']->getValue());

        $form['name'] = 'bar';
        $this->assertEquals('bar', $form['name']->getValue());

        $this->setExpectedException('Exception');
        $form['bar'];
    }

    public function testExclude()
    {
        $form = new TestForm([
            'exclude' => ['content']
        ]);
        $this->assertEquals(1, count($form->getFieldsInit()));

        $form = new TestForm();
        $this->assertEquals(2, count($form->getFieldsInit()));
    }

    public function testTemplate()
    {
        $form = new TestForm([
            'template' => 'dummy.html'
        ]);
        $this->assertEquals('dummy.html', $form->template);
        $form = new TestForm();
        $this->assertEquals('dummy.html', $form->render('dummy.html')['template']);
    }

    public function testId()
    {
        foreach (range(0, 5) as $i) {
            $form = new TestForm();
            $this->assertEquals($i, $form->getId());
        }
    }

    public function testSetRenderFields()
    {
        $form = new TestForm();
        $form->setRenderFields(['name']);
        $data = $form->render();
        $this->assertTrue(array_key_exists('name', $data['fields']));
        $this->assertFalse(array_key_exists('content', $data['fields']));

        $form->setRenderFields();
        $this->assertEquals(['name', 'content'], array_keys($form->getRenderFields()));
    }

    public function testPopulate()
    {
        $form = new TestForm(['exclude' => ['content']]);
        $form->populate(['TestForm' => ['name' => 'foo']]);
        $this->assertEquals(['name' => 'foo'], $form->getAttributes());
    }
}