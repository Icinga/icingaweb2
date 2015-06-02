<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication\User;

use Icinga\Exception\AuthenticationException;
use Icinga\User;

/**
 * Interface for user backends
 */
interface UserBackendInterface
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
     * Authenticate the given user
     *
     * @param   User        $user
     * @param   string      $password
     *
     * @return  bool                        True on success, false on failure
     *
     * @throws  AuthenticationException     In case authentication is not possible due to an error
     */
    public function authenticate(User $user, $password);
}
