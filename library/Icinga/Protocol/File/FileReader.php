<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\File;

use Icinga\Data\Selectable;
use Countable;
use Zend_Config;

/**
 * Read file line by line
 */
class FileReader implements Selectable, Countable
{
    /**
     * A PCRE string with the fields to extract from the file's lines as named subpatterns
     *
     * @var string
     */
    protected $fields;

    /**
     * Name of the target file
     *
     * @var string
     */
    protected $filename;

    /**
     * Create a new reader
     *
     * @param   Zend_Config $config
     *
     * @throws  FileReaderException If a required $config directive (filename or fields) is missing
     */
    public function __construct(Zend_Config $config)
    {
        foreach (array('filename', 'fields') as $key) {
            if (isset($config->{$key})) {
                $this->{$key} = $config->{$key};
            } else {
                throw new FileReaderException('The directive `%s\' is required', $key);
            }
        }
    }

    /**
     * Instantiate a FileIterator object with the target file
     *
     * @return FileIterator
     */
    public function iterate()
    {
        return new FileIterator($this->filename, $this->fields);
    }

    /**
     * Instantiate a FileQuery object
     *
     * @return FileQuery
     */
    public function select()
    {
        return new FileQuery($this);
    }

    /**
     * Return the number of available valid lines.
     *
     * @return int
     */
    public function count()
    {
        return iterator_count($this->iterate());
    }

    /**
     * Fetch result as an array of objects
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchAll(FileQuery $query)
    {
        $all = array();
        foreach ($this->fetchPairs($query) as $index => $value) {
            $all[$index] = (object) $value;
        }
        return $all;
    }

    /**
     * Fetch result as a key/value pair array
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchPairs(FileQuery $query)
    {
        $skip = $query->getOffset();
        $read = $query->getLimit();
        if ($skip === null) {
            $skip = 0;
        }
        $lines = array();
        if ($query->sortDesc()) {
            $count = $this->count($query);
            if ($count <= $skip) {
                return $lines;
            } else if ($count < ($skip + $read)) {
                $read = $count - $skip;
                $skip = 0;
            } else {
                $skip = $count - ($skip + $read);
            }
        }
        foreach ($this->iterate() as $index => $line) {
            if ($index >= $skip) {
                if ($index >= $skip + $read) {
                    break;
                }
                $lines[] = $line;
            }
        }
        if ($query->sortDesc()) {
            $lines = array_reverse($lines);
        }
        return $lines;
    }

    /**
     * Fetch first result row
     *
     * @param   FileQuery $query
     *
     * @return  object
     */
    public function fetchRow(FileQuery $query)
    {
        $all = $this->fetchAll($query);
        if (isset($all[0])) {
            return $all[0];
        }
        return null;
    }

    /**
     * Fetch first result column
     *
     * @param   FileQuery $query
     *
     * @return  array
     */
    public function fetchColumn(FileQuery $query)
    {
        $column = array();
        foreach ($this->fetchPairs($query) as $pair) {
            foreach ($pair as $value) {
                $column[] = $value;
                break;
            }
        }
        return $column;
    }

    /**
     * Fetch first column value from first result row
     *
     * @param   FileQuery $query
     *
     * @return  mixed
     */
    public function fetchOne(FileQuery $query)
    {
        $pairs = $this->fetchPairs($query);
        if (isset($pairs[0])) {
            foreach ($pairs[0] as $value) {
                return $value;
            }
        }
        return null;
    }
}
