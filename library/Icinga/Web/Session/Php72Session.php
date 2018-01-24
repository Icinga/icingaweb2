<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Session;

use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Cookie;

/**
 * Session implementation in PHP
 */
class Php72Session extends PhpSession
{
    /**
     * Open a PHP session
     */
    protected function open()
    {
        session_name($this->sessionName);

        $cookie = new Cookie('bogus');
        session_set_cookie_params(
            0,
            $cookie->getPath(),
            $cookie->getDomain(),
            $cookie->isSecure(),
            true
        );

        session_start(array(
            'use_cookies'       => true,
            'use_only_cookies'  => true,
            'use_trans_sid'     => false
        ));
    }
}
