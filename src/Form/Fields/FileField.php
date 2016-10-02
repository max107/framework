<?php

namespace Mindy\Form\Fields;

use Mindy\Form\Widget\FileWidget;
use Mindy\Orm\Files\UploadedFile;
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

    public function setValue($value)
    {
        if ($value === '1') {
            // Clean file field hack
            $value = null;
        }
        return parent::setValue($value);
    }

    public function renderValue() : string
    {
        if (is_object($this->value)) {
            if ($this->value instanceof UploadedFile) {
                return $this->value->getClientFilename();
            }
            
            return '';
        }
        return parent::renderValue();
    }
}
