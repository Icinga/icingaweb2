<?php
/* Icinga Web 2 | (c) 2020 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

/**
 * Class RememberMeCookie
 *
 * This class sets the given value of the cookie for 30 days,
 * when the user "checks" the "remember me" button while logging in.
 *
 * @package Icinga\Web
 */
class RememberMeCookie extends Cookie
{
    public function __construct($expire = NULL)
    {
        if (! $expire) {
            $expire = time() + 60 * 60 * 24 * 30;
        }
        parent::__construct('remember-me');

        $this->setExpire($expire);
        $this->setHttpOnly(true);
    }
}
