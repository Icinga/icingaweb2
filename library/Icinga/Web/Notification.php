<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Platform;
use Icinga\Application\Logger;
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
                '"%s" is not a valid notification type',
                $type
            );
        }

        if ($this->isCli) {
            $msg = sprintf('[%s] %s', $type, $message);
            switch ($type) {
                case 'info':
                case 'success':
                    Logger::info($msg);
                    break;
                case 'warning':
                    Logger::warn($msg);
                    break;
                case 'error':
                    Logger::error($msg);
                    break;
            }
            return;
        }

        $messages = & Session::getSession()->getByRef('messages');
        $messages[] = (object) array(
            'type'    => $type,
            'message' => $message,
        );
    }

    public function hasMessages()
    {
        $session = Session::getSession();
        return false === empty($session->messages);
    }

    public function getMessages()
    {
        $session = Session::getSession();
        $messages = $session->messages;
        if (false === empty($messages)) {
            $session->messages = array();
        }

        return $messages;
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
