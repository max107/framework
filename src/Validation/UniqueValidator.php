<?php


namespace Mindy\Validation;
use function Mindy\app;

/**
 * Class UniqueValidator
 * @package Mindy\Validation
 */
class UniqueValidator extends Validator
{
    /**
     * @var string
     */
    public $message = "Must be a unique";

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->message = $message;
        }
    }

    public function validate($value)
    {
        $model = $this->getModel();
        $qs = $model::objects()->filter([$this->getName() => $value]);
        if (!$model->getIsNewRecord()) {
            $qs->exclude(['pk' => $model->pk]);
        }

        if ($qs->count() > 0) {
            $this->addError(app()->t('validation', $this->message, [
                '{name}' => $this->name
            ]));
        }

        return $this->hasErrors() === false;
    }
}