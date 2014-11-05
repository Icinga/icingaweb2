<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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

        $mo = (object) array(
            'type'    => $type,
            'message' => $message,
        );

        // Get, change, set - just to be on the safe side:
        $session = Session::getSession();
        $msgs = $session->messages;
        $msgs[] = $mo;
        $session->messages = $msgs;
        $session->write();
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
        if (false === empty($msgs)) {
            $session->messages = array();
            $session->write();
        }

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
