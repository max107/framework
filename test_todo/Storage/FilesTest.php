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
 * @date 30/06/14.06.2014 18:54
 */

namespace Modules\Files\Tests\Cases;

use Mindy\Storage\Files\LocalFile;
use Mindy\Storage\Files\UploadedFile;

class FilesTest extends \PHPUnit_Framework_TestCase
{
    public function testFiles()
    {
        $localFile = new LocalFile(__FILE__);
        $this->assertEquals(filesize(__FILE__), $localFile->size);
        $this->assertEquals('FilesTest.php', $localFile->name);
        $this->assertEquals(__FILE__, $localFile->path);
        $this->assertEquals('text/x-php', $localFile->type);
        $this->assertEquals('php', $localFile->getExt());

        // key: value from $_FILES
        $uploadedFile = new UploadedFile([
            'name' => __FILE__,
            'tmp_name' => __FILE__,
            'type' => 'text/x-php',
            'size' => filesize(__FILE__),
            'error' => UPLOAD_ERR_OK
        ]);
        $this->assertEquals(filesize(__FILE__), $uploadedFile->size);
        $this->assertEquals('FilesTest.php', $uploadedFile->name);
        $this->assertEquals(__FILE__, $uploadedFile->path);
        $this->assertEquals('text/x-php', $uploadedFile->type);
        $this->assertEquals('php', $uploadedFile->getExt());
    }
}
