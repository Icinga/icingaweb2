<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

/**
 * Set cookie for RememberMe button
 */
class RememberMeCookie extends Cookie
{

    public function __construct()
    {
        parent::__construct('remember-me');

        $this->setExpire(time()+60*60*24*30);
        $this->setHttpOnly(true);
    }
}
