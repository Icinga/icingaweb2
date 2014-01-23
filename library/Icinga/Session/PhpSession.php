<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
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
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Session;

use Icinga\Application\Logger;
use \Icinga\Exception\ConfigurationError;

/**
 * Session implementation in PHP
 */
class PhpSession extends Session
{
    /**
     * Name of the session
     *
     * @var string
     */
    private $sessionName = 'Icingaweb2';

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
     * @param   array   $options    An optional array of ini options to set
     *
     * @throws  ConfigurationError
     * @see     http://php.net/manual/en/session.configuration.php
     */
    public function __construct(array $options = null)
    {
        if ($options !== null) {
            $options = array_merge(self::$defaultCookieOptions, $options);
        } else {
            $options = self::$defaultCookieOptions;
        }

        if (array_key_exists('test_session_name', $options)) {
            $this->sessionName = $options['test_session_name'];
            unset($options['test_session_name']);
        }

        foreach ($options as $sessionVar => $value) {
            if (ini_set("session." . $sessionVar, $value) === false) {
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

        $this->read();
    }

    /**
     * Open a PHP session
     */
    private function open()
    {
        session_name($this->sessionName);
        session_start();
    }

    /**
     * Read all values written to the underling session and make them accessible.
     */
    public function read()
    {
        $this->open();
        $this->setAll($_SESSION);
        session_write_close();
    }

    /**
     * Write all values of this session object to the underlying session implementation
     */
    public function write()
    {
        $this->open();
        foreach ($this->getAll() as $key => $value) {
            $_SESSION[$key] = $value;
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
