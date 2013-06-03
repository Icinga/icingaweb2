<?php

namespace Icinga\Backend\Criteria;

/**
 * Constants for order definitions.
 * These only describe logical orders without going into storage specific
 * details, like which fields are used for ordering. It's completely up to the query to determine what to do with these
 * constants (although the result should be consistent among the different storage apis).
 *
 */
class Order
{
    /**
     * Order by the newest events. What this means has to be determined in the context.
     * Mostly this affects last_state_change
     */
    const STATE_CHANGE = "state_change";

    /**
     * Order by the state of service objects. Mostly this is critical->unknown->warning->ok,
     * but also might take acknowledgments and downtimes in account
     */
    const SERVICE_STATE = "service_state";

    /**
     * Order by the state of host objects. Mostly this is critical->unknown->warning->ok,
     * but also might take acknowledgments and downtimes in account
     */
    const HOST_STATE    = "host_state";

    const HOST_NAME     = "host_name";
    const SERVICE_NAME  = "service_description";
}

