<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Authentication;

use Icinga\User;

interface Authenticatable
{
    /**
     * Authenticate a user
     *
     * @param   User    $user
     * @param   string  $password
     *
     * @return  bool
     *
     * @throws  \Icinga\Exception\AuthenticationException If authentication errors
     */
    public function authenticate(User $user, $password);
}
