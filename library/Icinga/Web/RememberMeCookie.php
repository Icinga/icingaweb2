<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

/**
 * Cookie for the "remember me" feature upon login with a default expiration time of 30 days
 *
 */
class RememberMeCookie extends Cookie
{
    public function __construct()
    {
        parent::__construct('remember-me');

        $this->setExpire(time() + 60 * 60 * 24 * 30);
        $this->setHttpOnly(true);
    }
}
