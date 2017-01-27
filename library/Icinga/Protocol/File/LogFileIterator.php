<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Protocol\File;

use Icinga\Exception\IcingaException;
use SplFileObject;
use Iterator;

/**
 * Iterate over a log file, yielding the regex fields of the log messages
 */
class LogFileIterator implements Iterator
{
    /**
     * Log file
     *
     * @var SplFileObject
     */
    protected $file;

    /**
     * A PCRE string with the fields to extract
     * from the log messages as named subpatterns
     *
     * @var string
     */
    protected $fields;

    /**
     * Value for static::current()
     *
     * @var string
     */
    protected $current;

    /**
     * Index for static::key()
     *
     * @var int
     */
    protected $index;

    /**
     * Value for static::valid()
     *
     * @var boolean
     */
    protected $valid;

    /**
     * @var string
     */
    protected $next = null;

    /**
     * @param string $filename      The log file's name
     * @param string $fields        A PCRE string with the fields to extract
     *                              from the log messages as named subpatterns
     */
    public function __construct($filename, $fields)
    {
        $this->file = new SplFileObject($filename);
        $this->file->setFlags(
            SplFileObject::DROP_NEW_LINE |
            SplFileObject::READ_AHEAD
        );
        $this->fields = $fields;
    }

    public function rewind()
    {
        $this->file->rewind();
        $this->index = 0;
        $this->nextMessage();
    }

    public function next()
    {
        $this->file->next();
        ++$this->index;
        $this->nextMessage();
    }

    /**
     * @return string
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return $this->valid;
    }

    protected function nextMessage()
    {
        $message = $this->next === null ? array() : array($this->next);
        $this->valid = null;
        while ($this->file->valid()) {
            if (false === ($res = preg_match(
                $this->fields,
                $current = $this->file->current()
            ))) {
                throw new IcingaException('Failed at preg_match()');
            }
            if (empty($message)) {
                if ($res === 1) {
                    $message[] = $current;
                }
            } elseif ($res === 1) {
                $this->next = $current;
                $this->valid = true;
                break;
            } else {
                $message[] = $current;
            }

            $this->file->next();
        }
        if ($this->valid === null) {
            $this->next = null;
            $this->valid = ! empty($message);
        }

        if ($this->valid) {
            while (! empty($message)) {
                $matches = array();
                if (false === ($res = preg_match(
                    $this->fields,
                    implode(PHP_EOL, $message),
                    $matches
                ))) {
                    throw new IcingaException('Failed at preg_match()');
                }
                if ($res === 1) {
                    $this->current = $matches;
                    return;
                }
                array_pop($message);
            }
            $this->valid = false;
        }
    }
}
