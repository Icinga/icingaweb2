<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;

/**
 * An object for which the user can retrieve status information
 *
 * This interface is useful for providing summaries or diagnostic information about objects
 * to users.
 */
interface Inspectable
{
    /**
     * Inspect this object to gain extended information about its health
     *
     * @return Inspection           The inspection result
     */
    public function inspect();
}
