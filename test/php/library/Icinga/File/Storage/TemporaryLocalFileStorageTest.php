<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\File\Storage;

use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Test\BaseTestCase;

class TemporaryLocalFileStorageTest extends BaseTestCase
{
    public function testDestructorRemovesFiles()
    {
        $storage = new TemporaryLocalFileStorage();
        $storage->create('foo', 'bar');
        $storage->create('bar', 'foo');

        $fooPath = $storage->resolvePath('foo');
        $barPath = $storage->resolvePath('bar');

        $storage = null;

        $this->assertFileDoesNotExist($fooPath);
        $this->assertFileDoesNotExist($barPath);
    }

    public function testDestructorRemovesDirectories()
    {
        $storage = new TemporaryLocalFileStorage();
        $storage->create('foo/bar', 'raboof');

        $dirPath = dirname($storage->resolvePath('foo/bar'));

        $storage = null;

        $this->assertDirectoryDoesNotExist($dirPath);
    }

    public function testDestructorRemovesNestedDirectories()
    {
        $storage = new TemporaryLocalFileStorage();
        $storage->create('a/b/c/fileA', 'foo');
        $storage->create('a/b/d/fileB', 'bar');
        $storage->create('a/e/f/g/h/fileC', 'raboof');

        $aPath = dirname($storage->resolvePath('a/b/c/fileA'), 3);

        $storage = null;

        $this->assertDirectoryDoesNotExist($aPath);
    }
}
