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
    public function __toString()
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
