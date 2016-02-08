<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
