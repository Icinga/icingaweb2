<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat\Query;

/**
 * Class IQueryPart
 * @package Icinga\Protocol\Statusdat\Query
 */
interface IQueryPart
{
    /**
     * Create a new query part with an optional expression to be parse
     *
     * @param string $expression        An optional expression string to use
     * @param array $value              The values fot the optional expression
     */
    public function __construct($expression = null, &$value = array());

    /**
     * Filter the given resultset
     *
     * @param array $base           The resultset to use for filtering
     * @param array $idx            An optional array containing prefiltered indices
     */
    public function filter(array &$base, &$idx = null);

    /**
     * Add additional information about the query this filter belongs to
      *
     * @param $query
     * @return mixed
     */
    public function setQuery($query);
}
