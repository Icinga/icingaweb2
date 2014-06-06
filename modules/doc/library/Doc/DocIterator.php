<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use ArrayIterator;
use RunetimeException;

class FileLockingIterator extends ArrayIterator
{
    public function next()
    {
        $this->current()->flock(LOCK_UN);
        parent::next();
    }

    public function valid()
    {
        if (!parent::valid()) {
            return false;
        }
        $fileInfo = $this->current();
        try {
            $fileObject = $fileInfo->openFile();
        } catch (RuntimeException $e) {
            throw new DocException($e->getMessage());
        }
        if ($fileObject->flock(LOCK_SH) === false) {
            throw new DocException('Couldn\'t get the lock');
        }
        $this[$this->key()] = $fileObject;
        return true;
    }
}

use IteratorAggregate;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class DocIterator implements IteratorAggregate
{
    protected $fileInfos;

    public function __construct($path)
    {
        $iter = new RecursiveIteratorIterator(
            new MarkdownFileIterator(
                new RecursiveDirectoryIterator($path)
            )
        );
        $fileInfos = iterator_to_array($iter);
        natcasesort($fileInfos);
        $this->fileInfos = $fileInfos;
    }

    public function getIterator()
    {
        return new FileLockingIterator($this->fileInfos);
    }
}
