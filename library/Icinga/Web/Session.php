<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Web\Session\PhpSession;
use Icinga\Web\Session\Session as BaseSession;
use Icinga\Exception\ProgrammingError;

/**
 * Session container
 */
class Session
{
    /**
     * The current session
     *
     * @var BaseSession $session
     */
    private static $session;

    /**
     * Create the session
     *
     * @param   BaseSession  $session
     *
     * @return  BaseSession
     */
    public static function create(BaseSession $session = null)
    {
        if ($session === null) {
            self::$session = PhpSession::create();
        } else {
            self::$session = $session;
        }

        return self::$session;
    }

    /**
     * Return the current session
     *
     * @return  BaseSession
     * @throws  ProgrammingError
     */
    public static function getSession()
    {
        if (self::$session === null) {
            self::create();
        }

        return self::$session;
    }
}
