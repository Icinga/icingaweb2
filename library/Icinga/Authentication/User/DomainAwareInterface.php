<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

/**
 * Interface for user backends that are responsible for a specific domain
 */
interface DomainAwareInterface
{
    /**
     * Get the domain the backend is responsible for
     *
     * @return  string
     */
    public function getDomain();
}
