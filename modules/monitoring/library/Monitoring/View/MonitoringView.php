<?php

namespace Icinga\Module\Monitoring\View;

use Icinga\Data\AbstractQuery;
use Icinga\Data\Filter;

/**
 * MonitoringView provides consistent views to our Icinga Backends
 *
 * TODO: * This could be renamed to AbstractView
 *       * We might need more complex filters (let's do the simple ones first)
 *
 * You should not directly instantiate such a view but always go through the
 * Monitoring Backend. Using the Backend's select() method selecting from
 * 'virtualtable' returns a Monitoring\View\VirtualtableView instance.
 *
 * Usage example:
 * <code>
 * use \Icinga\Module\Monitoring\Backend;
 * $backend = Backend::getInstance();
 * $query = $backend->select()->from('viewname', array(
 *     'one_column',
 *     'another_column',
 *     'alias' => 'whatever_column',
 * ))->where('any_column', $search);
 *
 * print_r($query->fetchAll());
 * </code>
 *
 * What we see in the example is that:
 * * you can (and should) use a defined set of columns when issueing a query
 * * you can use proper alias names to have an influence on column names
 *   in the result set
 * * the MonitoringView behaves like any Query object and provides useful
 *   methods such as fetchAll, count, fetchPairs and so on
 *
 * If you want to fill a dropdown form element with all your hostgroups
 * starting with "net", using the hostgroup name as the form elements value but
 * showing the hostgroup aliases in the dropdown you would probably do this as
 * follows:
 *
 * <code>
 * $pairs = $backend->select->from(
 *     'hostgroups',
 *     array('hostgroup_name', 'hostgroup_alias')
 * )->where('hostgroup_name', 'net*')->fetchPairs();
 * $formElement->setMultiOptions($pairs);
 * </code>
 *
 * MonitoringView is a proxy to your Backend Query. While both are Query objects
 * providing partially the same interface, they are not directly related to
 * each other.
 */
class MonitoringView extends AbstractQuery
{
    /**
     * Stores the backend-specific Query Object
     * @var AbstractQuery
     */
    protected $query;

    /**
     * All the columns provided by this View MUST be specified here
     * @var Array
     */
    protected $availableColumns = array();

    /**
     * Columns available for search only but not in result sets
     * @var Array
     */
    protected $specialFilters = array();

    /**
     * All views COULD have a generic column called 'search', if available the
     * real column name is defined here.
     * TODO: This may be subject to change as a "search" could involve multiple
     *       columns
     * @var string
     */
    protected $searchColumn;

    /**
     * Defines default sorting rules for specific column names. This helps in
     * providing "intelligent" sort defaults for different columns (involving
     * also other columns where needed)
     * @var Array
     */
    protected $sortDefaults = array();

    /**
     * Whether this view provides a specific column name
     *
     * @param  string $column Column name
     * @return bool
     */
    public function hasColumn($column)
    {
        return in_array($column, $this->availableColumns);
    }
    
    /**
     * Get a list of all available column names
     *
     * This might be useful for dynamic frontend tables or similar
     *
     * @return Array
     */
    public function getAvailableColumns()
    {
        return $this->availableColumns;
    }

    /**
     * Extract and apply filters and sort rules from a given request object
     *
     * TODO: Enforce Icinga\Web\Request (or similar) as soon as we replaced
     *       Zend_Controller_Request 
     *
     * @param  mixed $request  The request object
     * @return self
     */
    public function applyRequest($request)
    {
        return $this->applyRequestFilters($request)
                    ->applyRequestSorting($request);
    }

    /**
     * Extract and apply sort column and directon from given request object
     *
     * @param  mixed $request  The request object
     * @return self
     */
    protected function applyRequestSorting($request)
    {

        return $this->order(
            // TODO: Use first sortDefaults entry if available, fall back to
            //       column if not
            $request->getParam('sort', $this->availableColumns[0]),
            $request->getParam('dir')
        );
    }

    /**
     * Extract and apply filters from a given request object
     *
     * Columns not fitting any defined available column or special filter column
     * will be silently ignored.
     *
     * @param  mixed $request  The request object
     * @return self
     */
    protected function applyRequestFilters($request)
    {
        foreach ($request->getParams() as $key => $value) {
            if ($key === 'search' && $value !== '') {
                if (strpos($value, '=') === false) {
                    if ($this->searchColumn !== null) {
                        $this->where($this->searchColumn, $value);
                    }
                } else {
                    list($k, $v) = preg_split('~\s*=\s*~', $value, 2);
                    if ($this->isValidFilterColumn($k)) {
                        $this->where($k, $v);
                    }
                }
                continue;
            }
            if ($this->isValidFilterColumn($key)) {
                $this->where($key, $value);
            }
        }
        return $this;
    }

    // TODO: applyAuthFilters(Auth $auth = null)
    //       MonitoringView will enforce restrictions as provided by the Auth
    //       backend

    /**
     * Apply an array of filters. This might become obsolete or even improved
     * and accept Filter objects - this is still to be defined.
     *
     * @param  Array $filters Filter array
     * @return self
     */
    public function applyFilters($filters)
    {
        foreach ($filters as $col => $filter) {
            $this->where($col, $filter);
        }
        return $this;
    }

    /**
     * Gives you a filter object with all applied filters excluding auth filters
     * Might be used to build URLs fitting query objects.
     *
     * Please take care, as Url has been improved the Filter object might
     * become subject to change
     *
     * @return Filter
     */
    public function getAppliedFilter()
    {
        return new Filter($this->filters);
    }

    /**
     * Default sort direction for given column, ASCending if not defined
     *
     * @param  String $col Column name
     * @return int
     */
    protected function getDefaultSortDir($col)
    {
        if (isset($this->sortDefaults[$col]['default_dir'])) {
            return $this->sortDefaults[$col]['default_dir'];
        }
        return self::SORT_ASC;
    }

    /**
     * getQuery gives you an instance of the Query object implementing this
     * view for the chosen backend.
     *
     * @return AbstractQuery
     */
    public function getQuery()
    {

        if ($this->query === null) {
            $classParts = preg_split('|\\\|', get_class($this));
            $class = substr(
                array_pop($classParts),
                0,
                -4
            ) . 'Query';
            $class = '\\' . get_class($this->ds) . '\\Query\\' . $class;
            $query = new $class($this->ds, $this->columns);
            foreach ($this->filters as $f) {
                $query->where($f[0], $f[1]);
            }
            foreach ($this->order_columns as $col) {

                if (isset($this->sortDefaults[$col[0]]['columns'])) {
                    foreach ($this->sortDefaults[$col[0]]['columns'] as $c) {
                        $query->order($c, $col[1]);
                    }
                } else {
                    $query->order($col[0], $col[1]);
                }
            }

            $this->query = $query;
        }
        if ($this->hasLimit()) {
            $this->query->limit($this->getLimit(), $this->getOffset());
        }

        return $this->query;
    }

    public function count()
    {
        return $this->getQuery()->count();
    }

    public function fetchAll()
    {
        return $this->getQuery()->fetchAll();
    }

    public function fetchRow()
    {
        return $this->getQuery()->fetchRow();
    }

    public function fetchColumn()
    {
        return $this->getQuery()->fetchColumn();
    }

    public function fetchPairs()
    {
        return $this->getQuery()->fetchPairs();
    }

    public function isValidFilterColumn($column)
    {
        if (in_array($column, $this->specialFilters)) {
            return true;
        }
        return in_array($column, $this->availableColumns);
    }
}
