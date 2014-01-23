<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Platform;
use Icinga\Application\Logger as Log;
use Icinga\Web\Session;

/**
 * // @TODO(eL): Use Notification not as Singleton but within request:
 * <code>
 * <?php
 * $request->[getUser()]->notify('some message', Notification::INFO);
 * </code>
 */
class Notification
{
    protected static $instance;
    protected $isCli = false;

    public static function info($msg)
    {
        self::getInstance()->addMessage($msg, 'info');
    }

    public static function success($msg)
    {
        self::getInstance()->addMessage($msg, 'success');
    }

    public static function warning($msg)
    {
        self::getInstance()->addMessage($msg, 'warning');
    }

    public static function error($msg)
    {
        self::getInstance()->addMessage($msg, 'error');
    }

    protected function addMessage($message, $type = 'info')
    {
        if (! in_array(
            $type,
            array(
                'info',
                'error',
                'warning',
                'success'
            )
        )) {
            throw new ProgrammingError(
                sprintf(
                    '"%s" is not a valid notification type',
                    $type
                )
            );
        }

        if ($this->is_cli) {
            $msg = sprintf('[%s] %s', $type, $message);
            switch ($type) {
                case 'info':
                case 'success':
                    Log::info($msg);
                    break;
                case 'warning':
                    Log::warn($msg);
                    break;
                case 'error':
                    Log::error($msg);
                    break;
            }
            return;
        }

        $mo = (object) array(
            'type'    => $type,
            'message' => $message,
        );

        // Get, change, set - just to be on the safe side:
        $session = Session::getSession();
        $msgs = $session->messages;
        $msgs[] = $mo;
        $session->messages = $msgs;
    }

    public function hasMessages()
    {
        $session = Session::getSession();
        return !empty($session->messages);
    }

    public function getMessages()
    {
        $session = Session::getSession();
        $msgs = $session->messages;
        $session->messages = array();
        return $msgs;
    }

    final private function __construct()
    {
        $session = Session::getSession();
        if (!is_array($session->get('messages'))) {
            $session->messages = array();
        }

        if (Platform::isCli()) {
            $this->is_cli = true;
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Notification();
        }
        return self::$instance;
    }
}
