<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Livestatus;

use Icinga\Data\SimpleQuery;
use Icinga\Exception\IcingaException;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filter\FilterChain;
use Icinga\Data\Filter\FilterExpression;
use Icinga\Data\Filter\FilterOr;
use Icinga\Data\Filter\FilterAnd;
use Icinga\Data\Filter\FilterNot;
use Exception;

class Query extends SimpleQuery
{

    /**
     * All available columns. To be overridden by specific query implementations
     */
    protected $available_columns = array();

    protected $count = false;

    /**
     * Headers for columns sent to Livestatus socket
     */
    protected $preparedHeaders = array();

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
        try {
            return $this->toString();
        } catch (Exception $e) {
            trigger_error(
                sprintf(
                    '%s in %s on line %d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ),
                E_USER_ERROR
            );
        }
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

        // Headers we always send
        $default_headers = array(
            // Our preferred output format is CSV as it allows us to fetch and
            // process the result row by row
            'OutputFormat: csv',
            'ResponseHeader: fixed16',
            // Tried to find a save list of separators, this might be subject to
            // change and eventually be transforment into constants
            'Separators: ' . implode(' ', array(ord("\n"), ord('`'), ord(','), ord(';'))),
            // We always use the keepalive feature, connection teardown happens
            // in the connection destructor
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

    protected function columnsToString()
    {
        $columns = array();
        $this->preparedHeaders = array();


        $usedColumns = $this->columns;
        if (! $this->filterIsSupported()) {
            foreach ($this->filter->listFilteredColumns() as $col) {
                if (! in_array($col, $usedColumns)) {
                    $usedColumns[] = $col;
                }
            }
        }
        foreach ($usedColumns as $col) {
// TODO: No alias if filter???
           if (array_key_exists($col, $this->available_columns)) {
              // Alias if such
              $col = $this->available_columns[$col];
           }
           if ($col[0] === '_') {
               $columns['custom_variables'] = true;
           } elseif (is_array($col)) {
               foreach ($col as $k) {
                   $columns[$k] = true;
               }    
           } else {
               $columns[$col] = true;
           }
        }

        $this->preparedHeaders = array_keys($columns);

        if ($this->count === false && $this->columns !== null) {
            return 'Columns: ' . implode(' ', array_keys($columns));
        } else {
            return ''; // TODO: 'Stats: state >= 0'; when count
        }
    }

    /**
     * Whether Livestatus is able to apply the current filter
     *
     * TODO: find a better method name
     * TODO: more granular checks, also render filter-flag columns with lql
     *
     * @return bool
     */
    public function filterIsSupported()
    {
        foreach ($this->filter->listFilteredColumns() as $column) {
            if (is_array($this->available_columns[$column])) {
                // Combined column, hardly filterable. Is it? May work!
                return false;
            }
        }
        return true;
    }

    /**
     * Create a Filter object for a given URL-like filter string. We allow
     * for spaces as we do not search for custom string values here. This is
     * internal voodoo.
     *
     * @param string $string Filter string
     *
     * @return Filter
     */
    protected function filterStringToFilter($string)
    {
        return Filter::fromQueryString(str_replace(' ', '', $string));
    }

    /**
     * Render the current filter to LQL
     *
     * @return string
     */
    protected function filterToString()
    {
        return $this->renderFilter($this->filter);
    }

    /**
     * Filter rendering
     *
     * Happens recursively, useful for filters and for Stats expressions
     *
     * @param Filter $filter    The filter that should be rendered
     * @param string $type      Filter type. Usually "Filter" or "Stats"
     * @param int    $level     Nesting level during recursion. Don't touch
     * @param bool   $keylookup Whether to resolve alias names
     *
     * @return string
     */
    protected function renderFilter(Filter $filter, $type = 'Filter', $level = 0, $keylookup = true)
    {
        $str = '';
        if ($filter instanceof FilterChain) {
            if ($filter instanceof FilterAnd) {
                $op = 'And';
            } elseif ($filter instanceof FilterOr) {
                $op = 'Or';
            } elseif ($filter instanceof FilterNot) {
                $op = 'Negate';
            } else {
                throw new IcingaException(
                    'Cannot render filter: %s',
                    $filter
                );
            }
            $parts = array();
            if (! $filter->isEmpty()) {
                foreach ($filter->filters() as $f) {
                    $parts[] = $this->renderFilter($f, $type, $level + 1, $keylookup);
                }
                $str .= implode("\n", $parts);
                if ($type === 'Filter') {
                    if (count($parts) > 1) {
                        $str .= "\n" . $op . ': ' . count($parts);
                    }
                } else {
                    $str .= "\n" . $type . $op . ': ' . count($parts);
                }
            }
        } else {
            $str .= $type . ': ' . $this->renderFilterExpression($filter, $keylookup);
        }

        return $str;
    }

    /**
     * Produce a safe regex string as required by LQL
     *
     * @param string $expression search expression
     *
     * @return string
     */
    protected function safeRegex($expression)
    {
        return '^' . preg_replace('/\*/', '.*', $expression) . '$';
    }

    /**
     * Render a single filter expression
     *
     * @param FilterExpression $filter    the filter expression
     * @param bool             $keylookup whether to resolve alias names
     *
     * @return string
     */
    public function renderFilterExpression(FilterExpression $filter, $keylookup = true)
    {
        if ($keylookup) {
            $col = $this->available_columns[$filter->getColumn()];
        } else {
            $col = $filter->getColumn();
        }

        $isArray = array_key_exists($col, $this->arrayColumns);

        $sign = $filter->getSign();
        if ($isArray && $sign === '=') {
            $sign = '>=';
        }
        $expression = $filter->getExpression();
        if ($sign === '=' && strpos($expression, '*') !== false) {
            return $col . ' ~~ ' . $this->safeRegex($expression);
        } elseif ($sign === '!=' && strpos($expression, '*') !== false) {
            return $col . ' !~~ ' . $this->safeRegex($expression);
        } else {
            return $col . ' ' . $sign . ' ' . $expression;
        }
    }

    public function __destruct()
    {
        unset($this->connection);
    }
}
