<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
