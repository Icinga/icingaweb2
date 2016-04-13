<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Util;

use ArrayIterator;
use InvalidArgumentException;
use RecursiveIterator;

/**
 * Iterator for traversing a directory
 */
class DirectoryIterator implements RecursiveIterator
{
    /**
     * Iterate files first
     *
     * @var int
     */
    const FILES_FIRST = 1;

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
     * Scanned files
     *
     * @var ArrayIterator
     */
    private $files;

    /**
     * Iterator flags
     *
     * @var int
     */
    protected $flags;

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
     * Directory queue if FILES_FIRST flag is set
     *
     * @var array
     */
    private $queue;

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
     * @param   int     $flags      Iterator flags
     */
    public function __construct($path, $extension = null, $flags = null)
    {
        if (empty($path)) {
            throw new InvalidArgumentException('The path can\'t be empty');
        }
        $this->path = $path;
        if (! empty($extension)) {
            $this->extension = '.' . ltrim($extension, '.');
        }
        if ($flags !== null) {
            $this->flags = $flags;
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
    public function hasChildren()
    {
        return static::isReadable($this->current);
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static($this->current, $this->extension, $this->flags);
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
            $this->files->next();
            $skip = false;
            if (! $this->files->valid()) {
                $file = false;
                $path = false;
                break;
            } else {
                $file = $this->files->current();
                do {
                    if ($this->skipHidden && $file[0] === '.') {
                        $skip = true;
                        break;
                    }

                    $path = $this->path . '/' . $file;

                    if (is_dir($path)) {
                        if ($this->flags & static::FILES_FIRST === static::FILES_FIRST) {
                            $this->queue[] = array($path, $file);
                            $skip = true;
                        }
                        break;
                    }

                    if ($this->skipEmpty && ! filesize($path)) {
                        $skip = true;
                        break;
                    }

                    if ($this->extension && ! StringHelper::endsWith($file, $this->extension)) {
                        $skip = true;
                        break;
                    }
                } while (0);
            }
        } while ($skip);

        /** @noinspection PhpUndefinedVariableInspection */

        if ($path === false && ! empty($this->queue)) {
            list($path, $file) = array_shift($this->queue);
        }

        $this->current = $path;
        $this->key = $file;
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
        if ($this->files === null) {
            $files = scandir($this->path);
            natcasesort($files);
            $this->files = new ArrayIterator($files);
        }
        $this->files->rewind();
        $this->queue = array();
        $this->next();
    }
}
