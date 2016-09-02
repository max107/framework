<?php

namespace Mindy\Storage\Files;

use Mindy\Helper\File as FileHelper;

/**
 * Class LocalFile
 * @package Mindy\Storage
 */
class LocalFile extends File
{
    public function __construct($path)
    {
        if (!is_file($path)) {
            throw new \Mindy\Exception\Exception("File {$path} not found");
        }

        $this->path = $path;
        $this->name = FileHelper::mbBasename($path);
        $this->size = filesize($path);

        if (function_exists("finfo_file")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
        } else if (function_exists("mime_content_type")) {
            $mime = mime_content_type($path);
        } else {
            throw new \Mindy\Exception\Exception("Unknown file extension");
        }
        $this->type = $mime;
    }
}
