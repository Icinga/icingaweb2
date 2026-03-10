<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\File\Storage;

use ErrorException;
use Exception;
use Icinga\File\Storage\LocalFileStorage;
use Icinga\File\Storage\TemporaryLocalFileStorage;
use Icinga\Test\BaseTestCase;

class LocalFileStorageTest extends BaseTestCase
{
    protected static int $oldErrorReportingLevel;

    public static function setUpBeforeClass(): void
    {
        static::$oldErrorReportingLevel = error_reporting();
        error_reporting(E_ALL);

        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (error_reporting() === 0) {
                // Error was suppressed with the @-operator
                return false; // Continue with the normal error handler
            }

            switch ($errno) {
                case E_NOTICE:
                case E_WARNING:
                case E_RECOVERABLE_ERROR:
                    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
            }

            return false; // Continue with the normal error handler
        });
    }

    public static function tearDownAfterClass(): void
    {
        error_reporting(static::$oldErrorReportingLevel);
        restore_error_handler();
    }

    public function testGetIterator()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');

        foreach ($lfs as $path => $_) {
            $this->assertEquals($lfs->resolvePath('foobar'), $path);
        }
    }

    public function testGetIteratorThrowsNotReadableError()
    {
        $this->expectException(\Icinga\Exception\NotReadableError::class);

        $lfs = new LocalFileStorage('/notreadabledirectory');
        $lfs->getIterator();
    }

    public function testHas()
    {
        $lfs = new TemporaryLocalFileStorage();
        static::assertFalse($lfs->has('foobar'));

        $lfs->create('foobar', 'Hello world!');
        static::assertTrue($lfs->has('foobar'));
    }

    public function testCreate()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foo/bar', 'Hello world!');
        static::assertSame('Hello world!', $lfs->read('foo/bar'));
    }

    public function testCreateThrowsAlreadyExistsException()
    {
        $this->expectException(\Icinga\Exception\AlreadyExistsException::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        $lfs->create('foobar', 'Hello world!');
    }

    public function testCreateThrowsNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $lfs = new LocalFileStorage('/notwritabledirectory');
        $lfs->create('foobar', 'Hello world!');
    }

    public function testRead()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        static::assertSame('Hello world!', $lfs->read('foobar'));
    }

    public function testReadThrowsNotFoundError()
    {
        $this->expectException(\Icinga\Exception\NotFoundError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->read('foobar');
    }

    public function testReadThrowsNotReadableError()
    {
        $this->expectException(\Icinga\Exception\NotReadableError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        chmod($lfs->resolvePath('foobar'), 0);
        $lfs->read('foobar');
    }

    public function testUpdate()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        $lfs->update('foobar', 'Hello universe!');
        static::assertSame('Hello universe!', $lfs->read('foobar'));
    }

    public function testUpdateThrowsNotFoundError()
    {
        $this->expectException(\Icinga\Exception\NotFoundError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->update('foobar', 'Hello universe!');
    }

    public function testUpdateThrowsNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        chmod($lfs->resolvePath('foobar'), 0);
        $lfs->update('foobar', 'Hello universe!');
    }

    public function testDelete()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        $lfs->delete('foobar');
        static::assertFalse($lfs->has('foobar'));
    }

    public function testDeleteThrowsNotFoundError()
    {
        $this->expectException(\Icinga\Exception\NotFoundError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->delete('foobar');
    }

    public function testDeleteThrowsNotWritableError()
    {
        $this->expectException(\Icinga\Exception\NotWritableError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');

        $baseDir = dirname($lfs->resolvePath('foobar'));
        chmod($baseDir, 0500);

        try {
            $lfs->delete('foobar');
        } catch (Exception $e) {
            chmod($baseDir, 0700);
            throw $e;
        }

        chmod($baseDir, 0700);
    }

    public function testResolvePath()
    {
        $lfs = new LocalFileStorage('/notreadabledirectory');
        static::assertSame('/notreadabledirectory/foobar', $lfs->resolvePath('./notRelevant/../foobar'));
    }

    public function testResolvePathAssertExistence()
    {
        $lfs = new TemporaryLocalFileStorage();
        $lfs->create('foobar', 'Hello world!');
        $lfs->resolvePath('./notRelevant/../foobar', true);
    }

    public function testResolvePathThrowsNotFoundError()
    {
        $this->expectException(\Icinga\Exception\NotFoundError::class);

        $lfs = new TemporaryLocalFileStorage();
        $lfs->resolvePath('foobar', true);
    }

    public function testResolvePathThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $lfs = new LocalFileStorage('/notreadabledirectory');
        $lfs->resolvePath('../foobar');
    }
}
