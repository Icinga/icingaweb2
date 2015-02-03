<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Data;

/**
 * Interface for objects that are identifiable by an ID of any type
 */
interface Identifiable
{
    /**
     * Get the ID associated with this Identifiable object
     *
     * @return mixed
     */
    public function getId();
}
