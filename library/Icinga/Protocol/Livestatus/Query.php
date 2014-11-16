<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Livestatus;

use Icinga\Data\SimpleQuery;
use Icinga\Exception\IcingaException;

class Query extends SimpleQuery
{

    public function hasColumns()
    {
        return $this->columns !== null;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Parse the given encoded array
     *
     * @param string $str the encoded array string
     *
     * @return array
     */
    public function parseArray($str)
    {
        if (empty($str)) {
            return array();
        }

        $result = array();
        $entries = preg_split('/,/', $str);
        foreach ($entries as $e) {
            $result[] = preg_split('/;/', $e);
        }

        return $result;
    }

    public function getColumnAliases()
    {
        $aliases = array();
        foreach ($this->getColumns() as $key => $val) {
            if (is_int($key)) {
                $aliases[] = $val;
            } else {
                $aliases[] = $key;
            }
        }
        return $aliases;
    }
/*
    public function count()
    {
        $this->count = true;
        return $this;
    }
*/

    /**
     * Automagic string casting
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Render query string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->table === null) {
            throw new IcingaException('Table is required');
        }
        $default_headers = array(
            'OutputFormat: json',
            'ResponseHeader: fixed16',
            'KeepAlive: on'
        );
        $parts = array(
            sprintf('GET %s', $this->table)
        );
        if ($this->count === false && $this->columns !== null) {
            $parts[] = 'Columns: ' . implode(' ', $this->columns);
        }
        foreach ($this->filters as $key => $val) {
            if ($key === 'search') {
                $parts[] = 'Filter: host_name ~~ ' . $val;
                $parts[] = 'Filter: description ~~ ' . $val;
                $parts[] = 'Or: 2';
                continue;
            }
            if ($val === null) {
                $parts[] = 'Filter: ' . $key;
            } elseif (strpos($key, '?') === false) {
                $parts[] = sprintf('Filter: %s = %s', $key, $val);
            } else {
                $parts[] = sprintf('Filter: %s', str_replace('?', $val, $key));
            }
        }
        if ($this->count === true) {
            $parts[] = 'Stats: state >= 0';
        }
        if (! $this->count && $this->hasLimit() && ! $this->hasOrder()) {
            $parts[] = 'Limit: ' . ($this->limit_count + $this->limit_offset);
        }
        $lql = implode("\n", $parts)
             . "\n"
             . implode("\n", $default_headers)
             . "\n\n";
        return $lql;
    }

    public function __destruct()
    {
        unset($this->connection);
    }
}
