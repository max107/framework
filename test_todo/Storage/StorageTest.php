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
 * @date 25/06/14.06.2014 11:33
 */

namespace Modules\Files\Tests\Cases;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Cached\CachedAdapter;
use League\Flysystem\Cached\Storage\Memory as CacheStore;
use Mindy\Storage\Storage;

class StorageTest extends \PHPUnit_Framework_TestCase
{
    public function testStorage()
    {
        $s = new Storage([
            'adapters' => [
                'default' => function () {
                    // Create the adapter
                    $localAdapter = new Local(__DIR__ . '/../www/media');
                    // Create the cache store
                    $cacheStore = new CacheStore();
                    // Decorate the adapter
                    return new CachedAdapter($localAdapter, $cacheStore);
                }
            ]
        ]);

        $fs = $s->getFileSystem();
        $this->assertTrue($fs->has('.gitkeep'));
        $stream = fopen(__FILE__, 'r+');
        $state = $fs->writeStream('test.txt', $stream);
        $this->assertTrue($state);
        fclose($stream);
        $this->assertTrue($fs->has('test.txt'));

        $this->assertEquals(2, count($fs->listContents('')));
    }

    protected function tearDown()
    {
        @unlink(__DIR__ . '/../www/media/test.txt');
    }
}
