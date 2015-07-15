<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Data;
use Icinga\Exception\InspectionException;

/**
 * An object for which the user can retrieve status information
 */
interface Inspectable
{
    /**
     * Get information about this objects state
     *
     * @return Inspection
     * @throws InspectionException  When inspection of the object was not possible
     */
    public function inspect();
}
