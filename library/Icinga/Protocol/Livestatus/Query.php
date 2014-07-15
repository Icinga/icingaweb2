<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Livestatus;

use Icinga\Protocol\AbstractQuery;

class Query extends AbstractQuery
{

    protected $connection;
    protected $table;
    protected $filters = array();
    protected $limit_count;
    protected $limit_offset;
    protected $columns;
    protected $order_columns = array();
    protected $count = false;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getAdapter()
    {
        return $this->connection;
    }

    public function compare(& $a, & $b, $col_num = 0)
    {
        if (! array_key_exists($col_num, $this->order_columns)) {
            return 0;
        }
        $col = $this->order_columns[$col_num][0];
        $dir = $this->order_columns[$col_num][1];

        //$res = strnatcmp(strtolower($a->$col), strtolower($b->$col));
        $res = strcmp(strtolower($a->$col), strtolower($b->$col));
        if ($res === 0) {
            if (array_key_exists(++$col_num, $this->order_columns)) {
                return $this->compare($a, $b, $col_num);
            } else {
                return 0;
            }
        }
        if ($dir === self::SORT_ASC) {
            return $res;
        } else {
            return $res * -1;
        }
    }

    public function hasOrder()
    {
        return ! empty($this->order_columns);
    }

    public function where($key, $val = null)
    {
        $this->filters[$key] = $val;
        return $this;
    }

    public function order($col)
    {
        if (($pos = strpos($col, ' ')) === false) {
            $col = $col;
            $dir = self::SORT_ASC;
        } else {
            $dir = strtoupper(substr($col, $pos + 1));
            if ($dir === 'DESC') {
                $dir = self::SORT_DESC;
            } else {
                $dir = self::SORT_ASC;
            }
            $col = substr($col, 0, $pos);
        }
        $this->order_columns[] = array($col, $dir);
        return $this;
    }

    // Nur wenn keine stats, sonst im RAM!!
    // Offset gibt es nicht, muss simuliert werden
    public function limit($count = null, $offset = null)
    {
        if (! preg_match('~^\d+~', $count . $offset)) {
            throw new Exception(
                sprintf(
                    'Got invalid limit: %s, %s',
                    $count,
                    $offset
                )
            );
        }
        $this->limit_count  = (int) $count;
        $this->limit_offset = (int) $offset;
        return $this;
    }

    public function hasLimit()
    {
        return $this->limit_count !== null;
    }

    public function hasOffset()
    {
        return $this->limit_offset > 0;
    }

    public function getLimit()
    {
        return $this->limit_count;
    }

    public function getOffset()
    {
        return $this->limit_offset;
    }

    public function from($table, $columns = null)
    {
        if (! $this->connection->hasTable($table)) {
            throw new Exception(
                sprintf(
                    'This livestatus connection does not provide "%s"',
                    $table
                )
            );
        }
        $this->table = $table;
        if (is_array($columns)) {
            // TODO: check for valid names?
            $this->columns = $columns;
        }
        return $this;
    }

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

    public function count()
    {
        $this->count = true;
        return $this;
    }

    public function __toString()
    {
        if ($this->table === null) {
            throw new Exception('Table is required');
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
