<?php

namespace Mindy\Storage\Files;

/**
 * Class UploadedFile
 * @package Mindy\Storage
 */
class UploadedFile extends File
{
    public function __construct(array $data)
    {
        $this->name = basename($data['name']);
        $this->path = $data['tmp_name'];
        $this->size = $data['size'];
        $this->type = $data['type'];
        $this->error = $data['error'];
    }
}
