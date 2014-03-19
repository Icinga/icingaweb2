<?php

namespace Icinga\Protocol\File;

use Icinga\Data\DatasourceInterface;

class Reader implements DatasourceInterface
{
    private $filename;

    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->filename = $config->filename;
    }

    public function select()
    {
        return new Query($this);
    }

    public function fetchAll(Query $query)
    {
        $all = array();
        foreach ($this->fetchPairs($query) as $index => $value) {
            $all[$index] = new \stdClass();
            foreach ($value as $key => $value_2) {
                $all[$index]->{$key} = $value_2;
            }
        }
        return $all;
    }

    public function fetchRow(Query $query)
    {
        $all = $this->fetchAll($query);
        if (isset($all[0])) {
            return $all[0];
        }
        return null;
    }

    public function fetchColumn(Query $query)
    {
        $column = array();
        foreach ($this->fetchPairs($query) as $value) {
            foreach ($value as $value_2) {
                $column[] = $value_2;
                break;
            }
        }
        return $column;
    }

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

    public function fetchPairs(Query $query)
    {
        return $this->read($query);
    }

    public function validateLine($line, Query $query)
    {
        $data = array();
        $PCRE_result = @preg_match($this->config->fields, $line, $data);
        if ($PCRE_result === false) {
            throw new \Exception('Failed parsing regular expression!');
        } else if ($PCRE_result === 1) {
            foreach ($query->getFilters() as $filter) {
                if (strpos($line, $filter) === false) {
                    return false;
                }
            }
            foreach ($data as $key => $value) {
                if (is_int($key)) {
                    unset($data[$key]);
                }
            }
            return $data;
        }
        return false;
    }

    public function read(Query $query)
    {
        $skip_lines = $query->getOffset();
        $read_lines = $query->getLimit();
        if ($skip_lines === null) {
            $skip_lines = 0;
        }
        if ($query->sortDesc()) {
            return $this->readFromEnd($skip_lines, $read_lines, $query);
        }
        return $this->readFromStart($skip_lines, $read_lines, $query);
    }

    public function readFromEnd($skip_lines = null, $read_lines = null, Query $query)
    {
        $PHP_EOL_len = strlen(PHP_EOL);
        $lines = array();
        $s = '';
        $f = fopen($this->filename, 'rb');
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        while ($read_lines === null || count($lines) < $read_lines) {
            fseek($f, --$pos);
            $c = fgetc($f);
            if ($c === false || $pos < 0) {
                $l = $this->validateLine($s, $query);
                if (!($l === false || $skip_lines)) {
                    $lines[] = $l;
                }
                break;
            }
            $s = $c . $s;
            if (strpos($s, PHP_EOL) === 0) {
                $l = $this->validateLine((string)substr($s, $PHP_EOL_len), $query);
                if ($l !== false) {
                    if ($skip_lines) {
                        $skip_lines--;
                    } else {
                        $lines[] = $l;
                    }
                }
                $s = '';
            }
        }
        return $lines;
    }

    public function readFromStart($skip_lines = null, $read_lines = null, Query $query)
    {
        $PHP_EOL_len = strlen(PHP_EOL);
        $lines = array();
        $s = '';
        $f = fopen($this->filename, 'rb');
        while ($read_lines === null || count($lines) < $read_lines) {
            $c = fgetc($f);
            if ($c === false) {
                $l = $this->validateLine($s, $query);
                if (!($l === false || $skip_lines)) {
                    $lines[] = $l;
                }
                break;
            }
            $s .= $c;
            if (strpos($s, PHP_EOL) !== false) {
                $l = $this->validateLine((string)substr($s, 0, strlen($s) - $PHP_EOL_len), $query);
                if ($l !== false) {
                    if ($skip_lines) {
                        $skip_lines--;
                    } else {
                        $lines[] = $l;
                    }
                }
                $s = '';
            }
        }
        return $lines;
    }

    public function count(Query $query) {
        $PHP_EOL_len = strlen(PHP_EOL);
        $lines = 0;
        $s = '';
        $f = fopen($this->filename, 'rb');
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        while (true) {
            fseek($f, --$pos);
            $c = fgetc($f);
            if ($c === false || $pos < 0) {
                if ($this->validateLine($s, $query) !== false) {
                    $lines++;
                }
                break;
            }
            $s = $c . $s;
            if (strpos($s, PHP_EOL) === 0) {
                if ($this->validateLine((string)substr($s, $PHP_EOL_len), $query) !== false) {
                    $lines++;
                }
                $s = '';
            }
        }
        return $lines;
    }
}