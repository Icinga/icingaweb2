<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
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
    
    /**
    *   Creates a new PHPSession object using the provided options (if any)
    *   
    *   @param  Array   $options        An optional array of ini options to set,
    *                                   @see http://php.net/manual/en/session.configuration.php
    **/
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

    /**
    *   Returns true when the session has not yet been closed
    *
    *   @return Boolean
    **/
    private function sessionCanBeChanged()
    {
        if ($this->isFlushed) {
            Logger::error("Tried to work on a closed session, session changes will be ignored");
            return false;
        }
        return true;
    }
    
    /**
    *   Returns true when the session has not yet been opened
    *
    *   @return Boolean
    **/
    private function sessionCanBeOpened()
    {
        if ($this->isOpen) {
            Logger::warn("Tried to open a session more than once");
            return false;
        }
        return $this->sessionCanBeChanged();
    }
 
    /**
    *   Opens a PHP session when possible
    *
    *   @return Boolean     True on success
    **/
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
  
    /**
    *   Ensures that the session is open modifyable
    * 
    *   @return Boolean     True on success
    **/
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
 
    /**
    *   Reads all values written to the underyling session and
    *   makes them accessible. if keepOpen is not set, the session
    *   is immediately closed again
    *
    *   @param  Boolean $keepOpen       Set to true when modifying the session
    *
    *   @return Boolean                 True on success
    **/
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
 
    /**
    *   Writes all values of this session opbject to the underyling session implementation
    *   If keepOpen is not set, the session is closed
    *
    *   @param  Boolean $keepOpen       Set to true when modifying the session further
    *
    *   @return Boolean                 True on success
    **/
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
    
    /**
    *   Closes and writes the session. Call @see PHPSession::write in order to persist changes
    *   and only call this if you want the session to be closed without any changes
    *
    **/
    public function close()
    {
        if (!$this->isFlushed) {
            session_write_close();
        }
        $this->isFlushed = true;
    }
    
    /**
    *  Deletes the current session, causing all session information to be lost
    *
    **/
    public function purge()
    {
        if ($this->ensureOpen()) {
            $_SESSION = array();
            session_destroy();
            $this->clearCookies();
            $this->close();
        }
    }

    /**
    *   Removes session cookies
    *
    **/
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
