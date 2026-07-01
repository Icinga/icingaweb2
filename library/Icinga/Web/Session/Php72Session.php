<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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

        session_start([
            'use_cookies'       => true,
            'use_only_cookies'  => true,
            'use_trans_sid'     => false
        ]);
    }
}
