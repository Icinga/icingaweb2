<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use Icinga\Application\Logger as Logger;

/**
 * Class PhpSession
 *
 * Standard PHP Session handling
 * You have to call read() first in order to start the session. If
 * no parameter is given to read, the session is closed immediately
 * after reading the persisted variables, in order to avoid concurrent
 * requests to be blocked. Otherwise, you can call write() (again with
 * no parameter in order to auto-close it) to persist all values previously
 * set with the set() method
 *
 * @package Icinga\Authentication
 */
class PhpSession extends Session
{
    const SESSION_NAME = "Icinga2Web";
    private $isOpen = false;
    private $isFlushed = false;
    
    private static $DEFAULT_COOKIEOPTIONS = array(
        'use_trans_sid'           => false,
        'use_cookies'             => true,
        'cookie_httponly'         => true,
        'use_only_cookies'        => true,
        'hash_function'           => true,
        'hash_bits_per_character' => 5,
    );

    public function __construct(array $options = null)
    {
        if ($options !== null) {
            $options = array_merge(PhpSession::$DEFAULT_COOKIEOPTIONS, $options);
        } else {
            $options = PhpSession::$DEFAULT_COOKIEOPTIONS;
        }
        foreach ($options as $sessionVar => $value) {
            if (ini_set("session.".$sessionVar, $value) === false) {
                Logger::warn(
                    "Could not set php.ini setting %s = %s. This might affect your sessions behaviour.",
                    $sessionVar,
                    $value
                );
            }
        }
        if (!is_writable(session_save_path())) {
            throw new \Icinga\Exception\ConfigurationError("Can't save session");
        }
    }

    private function sessionCanBeChanged()
    {
        if ($this->isFlushed) {
            Logger::error("Tried to work on a closed session, session changes will be ignored");
            return false;
        }
        return true;
    }
    
    private function sessionCanBeOpened()
    {
        if ($this->isOpen) {
            Logger::warn("Tried to open a session more than once");
            return false;
        }
        return $this->sessionCanBeChanged();
    }
 
    public function open()
    {
        if (!$this->sessionCanBeOpened()) {
            return false;
        }

        session_name(PhpSession::SESSION_NAME);
        session_start();
        $this->isOpen = true;
        $this->setAll($_SESSION);
        return true;
    }
  
    private function ensureOpen()
    {
        // try to open first
        if (!$this->isOpen) {
            if (!$this->open()) {
                return false;
            }
        }
        return true;
    }
 
    public function read($keepOpen = false)
    {
        if (!$this->ensureOpen()) {
            return false;
        }
        if ($keepOpen) {
            return true;
        }
        $this->close();
        return true;
    }
 
    public function write($keepOpen = false)
    {
        if (!$this->ensureOpen()) {
            return false;
        }
        foreach ($this->getAll() as $key => $value) {
            $_SESSION[$key] = $value;
        }
        if ($keepOpen) {
            return null;
        }
        $this->close();

        return null;
    }
    
    public function close()
    {
        if (!$this->isFlushed) {
            session_write_close();
        }
        $this->isFlushed = true;
    }

    public function purge()
    {
        if ($this->ensureOpen()) {
            $_SESSION = array();
            session_destroy();
            $this->clearCookies();
            $this->close();
        }
    }

    private function clearCookies()
    {
        if (ini_get("session.use_cookies")) {
            Logger::debug("Clearing cookies");
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

    }
}
