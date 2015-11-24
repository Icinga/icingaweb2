<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use InvalidArgumentException;
use Iterator;

/**
 * Iterator for traversing a directory
 */
class DirectoryIterator implements Iterator
{
    /**
     * Current directory item
     *
     * @var string|false
     */
    private $current;

    /**
     * The file extension to filter for
     *
     * @var string
     */
    protected $extension;

    /**
     * Directory handle
     *
     * @var resource
     */
    private $handle;

    /**
     * Current key
     *
     * @var string
     */
    private $key;

    /**
     * The path of the directory to traverse
     *
     * @var string
     */
    protected $path;

    /**
     * Whether to skip empty files
     *
     * Defaults to true.
     *
     * @var bool
     */
    protected $skipEmpty = true;

    /**
     * Whether to skip hidden files
     *
     * Defaults to true.
     *
     * @var bool
     */
    protected $skipHidden = true;

    /**
     * Create a new directory iterator from path
     *
     * The given path will not be validated whether it is readable. Use {@link isReadable()} before creating a new
     * directory iterator instance.
     *
     * @param   string  $path       The path of the directory to traverse
     * @param   string  $extension  The file extension to filter for. A leading dot is optional
     */
    public function __construct($path, $extension = null)
    {
        if (empty($path)) {
            throw new InvalidArgumentException('The path can\'t be empty');
        }
        $this->path = $path;
        if (! empty($extension)) {
            $this->extension = '.' . ltrim($extension, '.');
        }
    }

    /**
     * Check whether the given path is a directory and is readable
     *
     * @param   string  $path   The path of the directory
     *
     * @return  bool
     */
    public static function isReadable($path)
    {
        return is_dir($path) && is_readable($path);
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        do {
            $file = readdir($this->handle);
            if ($file === false) {
                $key = false;
                break;
            } else {
                $skip = false;
                do {
                    if ($this->skipHidden && $file[0] === '.') {
                        $skip = true;
                        break;
                    }

                    $path = $this->path . '/' . $file;

                    if (is_dir($path)) {
                        $skip = true;
                        break;
                    }

                    if ($this->skipEmpty && ! filesize($path)) {
                        $skip = true;
                        break;
                    }

                    if ($this->extension && ! String::endsWith($file, $this->extension)) {
                        $skip = true;
                        break;
                    }

                    $key = $file;
                    $file = $path;
                } while (0);
            }
        } while ($skip);

        $this->current = $file;
        /** @noinspection PhpUndefinedVariableInspection */
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return $this->current !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        if ($this->handle === null) {
            $this->handle = opendir($this->path);
        } else {
            rewinddir($this->handle);
        }
        $this->next();
    }

    /**
     * Close directory handle if created
     */
    public function __destruct()
    {
        if ($this->handle !== null) {
            closedir($this->handle);
        }
    }
}
