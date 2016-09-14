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
 * @date 21/10/14.10.2014 13:55
 */

namespace Mindy\Tests\Validation;

use Mindy\Interfaces\Arrayable;
use Mindy\Validation\Json;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

class ValidatorsTest extends \PHPUnit_Framework_TestCase
{
    protected function getValidator()
    {
        return Validation::createValidatorBuilder()->getValidator();
    }

    public function testJsonValidator()
    {
        $validator = $this->getValidator();
        $constraints = new Json();

        $errors = $validator->validate(1, $constraints);
        $this->assertEquals(0, count($errors));

        $errors = $validator->validate(null, $constraints);
        $this->assertEquals(0, count($errors));

        $errors = $validator->validate(new \stdClass, $constraints);
        $errorsRaw = [];
        foreach ($errors as $error) {
            $errorsRaw[] = $error->getMessage();
        }
        $this->assertEquals(['Not json serialize object: object'], $errorsRaw);

        $obj = new Arr;
        $obj->data = [1, 2, 3];
        $errors = $validator->validate($obj, $constraints);
        $this->assertEquals(0, count($errors));
    }
}

class Arr implements Arrayable
{
    public $data = [];

    public function toArray()
    {
        return $this->data;
    }
}
