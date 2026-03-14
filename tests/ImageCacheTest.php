<?php

namespace Ycdev\OsmStaticAero\Tests;

use PHPUnit\Framework\TestCase;
use Ycdev\OsmStaticAero\Image;

/**
 * @requires extension gd
 */
class ImageCacheTest extends TestCase
{
    private $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/test_tiles_cache_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cacheDir)) {
            $this->recursiveDelete($this->cacheDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        $files = glob($dir . '/*');
        foreach ($files as $file) {
            is_dir($file) ? $this->recursiveDelete($file) : unlink($file);
        }
        rmdir($dir);
    }

    public function testCacheFileIdIsConsistent()
    {
        $img = new Image();
        $img->cacheDirectory = $this->cacheDir;

        // Use reflection to test private method
        $reflection = new \ReflectionMethod(Image::class, 'cacheFileId');
        $reflection->setAccessible(true);

        $id1 = $reflection->invoke($img, 'https://example.com/tile/1/2/3.png');
        $id2 = $reflection->invoke($img, 'https://example.com/tile/1/2/3.png');
        $id3 = $reflection->invoke($img, 'https://example.com/tile/1/2/4.png');

        $this->assertEquals($id1, $id2);
        $this->assertNotEquals($id1, $id3);
    }

    public function testSaveToCacheAndGetFromCache()
    {
        $img = new Image();
        $img->cacheDirectory = $this->cacheDir;

        $url = 'https://example.com/tile/1/2/3.png';
        $data = 'fake image data for testing';

        // Use reflection
        $saveMethod = new \ReflectionMethod(Image::class, 'saveToCache');
        $saveMethod->setAccessible(true);
        $saveMethod->invoke($img, $url, $data);

        $getMethod = new \ReflectionMethod(Image::class, 'getFileFromCache');
        $getMethod->setAccessible(true);
        $result = $getMethod->invoke($img, $url);

        $this->assertEquals($data, $result);
    }

    public function testIsCachedBeforeAndAfterSave()
    {
        $img = new Image();
        $img->cacheDirectory = $this->cacheDir;

        $url = 'https://example.com/tile/5/6/7.png';

        $isCachedMethod = new \ReflectionMethod(Image::class, 'isCached');
        $isCachedMethod->setAccessible(true);

        $this->assertFalse($isCachedMethod->invoke($img, $url));

        $saveMethod = new \ReflectionMethod(Image::class, 'saveToCache');
        $saveMethod->setAccessible(true);
        $saveMethod->invoke($img, $url, 'test data');

        $this->assertTrue($isCachedMethod->invoke($img, $url));
    }
}
