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

    /**
     * Read all values written to the underling session and make them accessible.
     */
    public function read()
    {
        $this->clear();
        $this->open();

        foreach ($_SESSION as $key => $value) {
            if (strpos($key, self::NAMESPACE_PREFIX) === 0) {
                $namespace = new SessionNamespace();
                $namespace->setAll($value);
                $this->namespaces[substr($key, strlen(self::NAMESPACE_PREFIX))] = $namespace;
            } else {
                $this->set($key, $value);
            }
        }

        session_write_close();
    }

    /**
     * Write all values of this session object to the underlying session implementation
     */
    public function write()
    {
        $this->open();

        foreach ($this->removed as $key) {
            unset($_SESSION[$key]);
        }
        foreach ($this->values as $key => $value) {
            $_SESSION[$key] = $value;
        }
        foreach ($this->removedNamespaces as $identifier) {
            unset($_SESSION[self::NAMESPACE_PREFIX . $identifier]);
        }
        foreach ($this->namespaces as $identifier => $namespace) {
            $_SESSION[self::NAMESPACE_PREFIX . $identifier] = $namespace->getAll();
        }

        session_write_close();
    }

    /**
     * Delete the current session, causing all session information to be lost
     */
    public function purge()
    {
        $this->open();
        $_SESSION = array();
        $this->clear();
        session_destroy();
        $this->clearCookies();
        session_write_close();
    }

    /**
     * @see Session::getId()
     */
    public function getId()
    {
        if (($id = session_id()) === '') {
            // Make sure we actually get a id
            $this->open();
            session_write_close();
            $id = session_id();
        }

        return $id;
    }

    /**
     * Assign a new sessionId to the currently active session
     */
    public function refreshId()
    {
        $this->open();
        if ($this->exists()) {
            session_regenerate_id();
        }
        session_write_close();
    }
}
