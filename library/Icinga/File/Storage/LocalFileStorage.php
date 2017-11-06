<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\File\Storage;

use ErrorException;
use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\NotFoundError;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

/**
 * Stores files in the local file system
 */
class LocalFileStorage implements StorageInterface
{
    /**
     * The root directory of this storage
     *
     * @var string
     */
    protected $baseDir;

    /**
     * Constructor
     *
     * @param   string  $baseDir    The root directory of this storage
     */
    public function __construct($baseDir)
    {
        $this->baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR);
    }

    public function getIterator()
    {
        try {
            return new LocalFileStorageIterator($this->baseDir);
        } catch (UnexpectedValueException $e) {
            throw new NotReadableError('Couldn\'t read the directory "%s": %s', $this->baseDir, $e);
        }
    }

    public function has($path)
    {
        return is_file($this->resolvePath($path));
    }

    public function create($path, $content)
    {
        $resolvedPath = $this->resolvePath($path);

        $this->ensureDir(dirname($resolvedPath));

        try {
            $stream = fopen($resolvedPath, 'x');
        } catch (ErrorException $e) {
            throw new AlreadyExistsException('Couldn\'t create the file "%s": %s', $path, $e);
        }

        try {
            fclose($stream);
            chmod($resolvedPath, 0664);
            file_put_contents($resolvedPath, $content);
        } catch (ErrorException $e) {
            throw new NotWritableError('Couldn\'t create the file "%s": %s', $path, $e);
        }
    }

    public function read($path)
    {
        $resolvedPath = $this->resolvePath($path, true);

        try {
            return file_get_contents($resolvedPath);
        } catch (ErrorException $e) {
            throw new NotReadableError('Couldn\'t read the file "%s": %s', $path, $e);
        }
    }

    public function update($path, $content)
    {
        $resolvedPath = $this->resolvePath($path, true);

        try {
            file_put_contents($resolvedPath, $content);
        } catch (ErrorException $e) {
            throw new NotWritableError('Couldn\'t update the file "%s": %s', $path, $e);
        }
    }

    public function delete($path)
    {
        $resolvedPath = $this->resolvePath($path, true);

        try {
            unlink($resolvedPath);
        } catch (ErrorException $e) {
            throw new NotWritableError('Couldn\'t delete the file "%s": %s', $path, $e);
        }
    }

    public function resolvePath($path, $assertExistence = false)
    {
        if ($assertExistence && ! $this->has($path)) {
            throw new NotFoundError('No such file: "%s"', $path);
        }

        $steps = preg_split('~/~', $path, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i < count($steps);) {
            if ($steps[$i] === '.') {
                array_splice($steps, $i, 1);
            } elseif ($steps[$i] === '..' && $i > 0 && $steps[$i - 1] !== '..') {
                array_splice($steps, $i - 1, 2);
                --$i;
            } else {
                ++$i;
            }
        }

        if ($steps[0] === '..') {
            throw new InvalidArgumentException('Paths above the base directory are not allowed');
        }

        return $this->baseDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $steps);
    }

    /**
     * Ensure that the given directory exists
     *
     * @param   string  $dir
     *
     * @throws  NotWritableError
     */
    protected function ensureDir($dir)
    {
        if (! is_dir($dir)) {
            $this->ensureDir(dirname($dir));

            try {
                mkdir($dir, 02770);
            } catch (ErrorException $e) {
                throw new NotWritableError('Couldn\'t create the directory "%s": %s', $dir, $e);
            }
        }
    }
}
