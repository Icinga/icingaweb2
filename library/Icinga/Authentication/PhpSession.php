<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Authentication;

use Icinga\Application\Logger;
use \Icinga\Exception\ConfigurationError;

/**
 * Session implementation in PHP
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
    /**
     * Name of the session
     *
     * @var string
     */
    const SESSION_NAME = 'Icinga2Web';

    /**
     * Flag if session is open
     *
     * @var bool
     */
    private $isOpen = false;

    /**
     * Flag if session is flushed
     *
     * @var bool
     */
    private $isFlushed = false;

    /**
     * Configuration for cookie options
     *
     * @var array
     */
    private static $defaultCookieOptions = array(
        'use_trans_sid'             => false,
        'use_cookies'               => true,
        'cookie_httponly'           => true,
        'use_only_cookies'          => true,
        'hash_function'             => true,
        'hash_bits_per_character'   => 5,
    );

    /**
     * Create a new PHPSession object using the provided options (if any)
     *
     * @param   array $options An optional array of ini options to set,
     *
     * @throws  ConfigurationError
     * @see     http://php.net/manual/en/session.configuration.php
     */
    public function __construct(array $options = null)
    {
        if ($options !== null) {
            $options = array_merge(PhpSession::$defaultCookieOptions, $options);
        } else {
            $options = PhpSession::$defaultCookieOptions;
        }
        foreach ($options as $sessionVar => $value) {
            if (ini_set("session.".$sessionVar, $value) === false) {
                Logger::warn(
                    'Could not set php.ini setting %s = %s. This might affect your sessions behaviour.',
                    $sessionVar,
                    $value
                );
            }
        }
        if (!is_writable(session_save_path())) {
            throw new ConfigurationError('Can\'t save session');
        }
    }

    /**
     * Return true when the session has not yet been closed
     *
     * @return bool
     */
    private function sessionCanBeChanged()
    {
        if ($this->isFlushed) {
            Logger::error('Tried to work on a closed session, session changes will be ignored');
            return false;
        }
        return true;
    }

    /**
     * Return true when the session has not yet been opened
     *
     * @return bool
     */
    private function sessionCanBeOpened()
    {
        if ($this->isOpen) {
            Logger::warn('Tried to open a session more than once');
            return false;
        }
        return $this->sessionCanBeChanged();
    }

    /**
     * Open a PHP session when possible
     *
     * @return bool True on success
     */
    public function open()
    {
        if (!$this->sessionCanBeOpened()) {
            return false;
        }

        session_name(self::SESSION_NAME);
        session_start();
        $this->isOpen = true;
        $this->setAll($_SESSION);
        return true;
    }

    /**
     * Ensure that the session is open modifiable
     *
     * @return bool True on success
     */
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
     * Read all values written to the underling session and
     * makes them accessible. if keepOpen is not set, the session
     * is immediately closed again
     *
     * @param   bool $keepOpen  Set to true when modifying the session
     *
     * @return  bool            True on success
     */
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
     * Write all values of this session object to the underlying session implementation
     *
     * If keepOpen is not set, the session is closed
     *
     * @param   bool $keepOpen  Set to true when modifying the session further
     *
     * @return  bool            True on success
     */
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
     * Close and writes the session
     *
     * Only call this if you want the session to be closed without any changes.
     *
     * @see PHPSession::write
     */
    public function close()
    {
        if (!$this->isFlushed) {
            session_write_close();
        }
        $this->isFlushed = true;
    }

    /**
     * Delete the current session, causing all session information to be lost
     */
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
     * Remove session cookies
     */
    private function clearCookies()
    {
        if (ini_get('session.use_cookies')) {
            Logger::debug('Clear session cookie');
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }
}
