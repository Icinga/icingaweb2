<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\UserGroup;

use Icinga\User;

/**
 * Interface for user group backends
 */
interface UserGroupBackendInterface
{
    /**
     * Set this backend's name
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function setName($name);

    /**
     * Return this backend's name
     *
     * @return  string
     */
    public function getName();

    /**
     * Return the groups the given user is a member of
     *
     * @param   User    $user
     *
     * @return  array
     */
    public function getMemberships(User $user);

    /**
     * Return the name of the backend that is providing the given user
     *
     * @param   string  $username
     *
     * @return  null|string     The name of the backend or null in case this information is not available
     */
    public function getUserBackendName($username);
}
