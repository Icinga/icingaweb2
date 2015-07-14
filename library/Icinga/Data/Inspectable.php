<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;


/**
 * An object for which the user can retrieve status information
 */
interface Inspectable
{
    /**
     * Get information about this objects state
     *
     * @return array    An array of strings that describe the state in a human-readable form, each array element
     *                  represents one fact about this object
     */
    public function getInfo();

    /**
     * If this object is working in its current configuration
     *
     * @return Bool     True if the object is working, false if not
     */
    public function isHealthy();
}
