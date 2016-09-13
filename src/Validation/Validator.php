<?php

namespace Mindy\Validation;

use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * Class Validator
 * @package Mindy\Validation
 */
abstract class Validator
{
    /**
     * @var
     */
    public $message;
    /**
     * @var
     */
    public $translateDomain;

    /**
     * Validator constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $validator = new ValidatorBuilder();

        $metadata->addPropertyConstraint('email', new Assert\Email([
            'message' => 'The email "{{ value }}" is not a valid email.',
            'checkMX' => true,
        ]));
    }
}
