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

    public function __construct($value)
    {
        parent::__construct('remember-me',$value);

        $this->setExpire(60);
        $this->setPath('/');
        $this->setDomain("");
        $this->setHttpOnly(true);

        // not in use yet
        /*if (isset($_COOKIE['remember-me'])) {
            try {
                $cookie = Json::decode($_COOKIE['remember-me'], true);
            } catch (JsonDecodeException $e) {
                Logger::error(
                    "Can't decode the remember me cookie of user '%s'. An error occurred: %s",
                    Auth::getInstance()->getUser()->getUsername(),
                    $e
                );

                return;
            }

        }*/
    }

}
