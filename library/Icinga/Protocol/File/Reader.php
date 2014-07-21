<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\File;

use FilterIterator;
use Iterator;
use Zend_Config;
use Icinga\Protocol\File\FileReaderException;
use Icinga\Util\File;

/**
 * Read file line by line
 */
class Reader extends FilterIterator
{
    /**
     * A PCRE string with the fields to extract from the file's lines as named subpatterns
     *
     * @var string
     */
    protected $fields;

    /**
     * An associative array of the current line's fields ($field => $value)
     *
     * @var array
     */
    protected $currentData;

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
            if (! isset($config->{$key})) {
                throw new FileReaderException('The directive `' . $key . '\' is required');
            }
        }
        $this->fields = $config->fields;
        $f = new File($config->filename);
        $f->setFlags(
            File::DROP_NEW_LINE |
            File::READ_AHEAD |
            File::SKIP_EMPTY
        );
        parent::__construct($f);
    }

    /**
     * Return the current data
     *
     * @return array
     */
    public function current()
    {
        return $this->currentData;
    }

    /**
     * Accept lines matching the given PCRE pattern
     *
     * @return bool
     *
     * @throws FileReaderException  If PHP failed parsing the PCRE pattern
     */
    public function accept()
    {
        $data = array();
        $matched = @preg_match(
            $this->fields,
            $this->getInnerIterator()->current(),
            $data
        );
        if ($matched === false) {
            throw new FileReaderException('Failed parsing regular expression!');
        } else if ($matched === 1) {
            foreach ($data as $key) {
                if (is_int($key)) {
                    unset($data[$key]);
                }
            }
            $this->currentData = $data;
            return true;
        }
        return false;
    }

    /**
     * Instantiate a Query object
     *
     * @return Query
     */
    public function select()
    {
        return new Query($this);
    }

    /**
     * Return the number of available valid lines.
     *
     * @return int
     */
    public function count()
    {
        return iterator_count($this);
    }

    /**
     * Fetch result as an array of objects
     *
     * @param   Query $query
     *
     * @return  array
     */
    public function fetchAll(Query $query)
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
     * @param   Query $query
     *
     * @return  array
     */
    public function fetchPairs(Query $query)
    {
        $skipLines = $query->getOffset();
        $readLines = $query->getLimit();
        if ($skipLines === null) {
            $skipLines = 0;
        }
        $lines = array();
        if ($query->sortDesc()) {
            $count = $this->count($query);
            if ($count <= $skipLines) {
                return $lines;
            } else if ($count < ($skipLines + $readLines)) {
                $readLines = $count - $skipLines;
                $skipLines = 0;
            } else {
                $skipLines = $count - ($skipLines + $readLines);
            }
        }
        foreach ($this as $index => $line) {
            if ($index >= $skipLines) {
                if ($index >= $skipLines + $readLines) {
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
     * @param   Query $query
     *
     * @return  object
     */
    public function fetchRow(Query $query)
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
     * @param   Query $query
     *
     * @return  array
     */
    public function fetchColumn(Query $query)
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
     * @param   Query $query
     *
     * @return  mixed
     */
    public function fetchOne(Query $query)
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
