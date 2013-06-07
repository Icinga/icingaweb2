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
     * @param null $expression
     * @param array $value
     */
    public function __construct($expression = null, &$value = array());

    /**
     * @param array $base
     * @param null $idx
     * @return mixed
     */
    public function filter(array &$base, &$idx = null);
}
