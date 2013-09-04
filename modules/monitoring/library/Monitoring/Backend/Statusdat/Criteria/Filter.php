<?php

namespace Icinga\Module\Monitoring\Backend\Statusdat\Criteria;

/**
 * Constants for filter definitions.
 * These only describe logical filter operations without going into storage specific
 * details, like which fields are used for querying. It's completely up to the query to determine what to do with these
 * constants (although the result should be consistent among the different storage apis).
 *
 */
class Filter
{
    /**
     * Whether to remove or keep handled objects
     * This means objects that are currently in a downtime or problems that have been acknowledged
     */
    const HANDLED = "handled";

    /**
     * Whether to display problems
     * This means objects with a state higher than 0
     */
    const PROBLEMS = "problems";

    /**
     * Whether to limit the result to a specific hostgroup.
     * Filters usually accept an array of hostgroup names
     */
    const HOSTGROUPS = "hostgroups";

    /**
     * Whether to limit the result to a specific servicegroup.
     * Filters usually accept an array of servicegroup names
     */
    const SERVICEGROUPS = "servicegroups";

    /**
     * Defines a string based search.
     * Which objects and criterias are used have to be decided in the backend
     */
    const SEARCH = "search";

    const STATE = "state";
    const HOSTSTATE  = "hoststate";
}
