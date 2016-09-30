<?php

namespace Mindy\Form\Fields;

use Mindy\Form\Widget\FileWidget;
use Symfony\Component\Validator\Constraints as Assert;
use Mindy\Orm\Validation;

/**
 * Class FileField
 * @package Mindy\Form
 */
class FileField extends Field
{
    /**
     * @var string
     */
    public $template = "<input type='file' id='{id}' name='{name}'{html}/>";
    /**
     * List of allowed file types
     * @var array|null
     */
    public $mimeTypes = [];
    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $maxSize = '5M';
    /**
     * @var array
     */
    public $widget = [
        'class' => FileWidget::class
    ];

    /**
     * FileField constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->html['accept'] = implode('|', $this->mimeTypes);
    }

    /**
     * @return array
     */
    public function getValidationConstraints() : array
    {
        return array_merge(parent::getValidationConstraints(), [
            $constraints[] = new Validation\File([
                'maxSize' => $this->maxSize,
                'mimeTypes' => $this->mimeTypes,
            ])
        ]);
    }

//    protected $file;
//    public function setValue($value)
//    {
//        if ($value instanceof UploadedFile) {
//            $this->file = $value;
//            $value = null;
//        }
//        return parent::setValue($value);
//    }
}
