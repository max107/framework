<?php

namespace Mindy\Form\Fields;

use GuzzleHttp\Psr7\UploadedFile;
use Mindy\Form\Widget\FileWidget;
use Symfony\Component\Validator\Constraints as Assert;

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
    public $types = [];
    /**
     * @var null|int maximum file size or null for unlimited. Default value 2 mb.
     */
    public $maxSize = 2097152;
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
        $this->html['accept'] = implode('|', $this->types);
    }

    public function getValidationConstraints() : array
    {
        return array_merge(parent::getValidationConstraints(), [
            new Assert\File([
                'maxSize' => $this->maxSize,
                'mimeTypes' => $this->types
            ])
        ]);
    }

    public function setValue($value)
    {
        if ($value instanceof UploadedFile && $value->getError() === UPLOAD_ERR_NO_FILE) {
            $value = null;
        }

        parent::setValue($value);
    }
}
