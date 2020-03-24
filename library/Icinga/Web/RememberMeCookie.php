<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Util\Json;

/**
 * Handle acknowledged application state messages via cookie
 */
class RememberMeCookie extends Cookie
{

    public function __construct()
    {
        parent::__construct('remember-me');

        $this->setExpire(60);
        $this->setPath('/');
        $this->setDomain("");

    }

}
