<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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

    protected $session;

    protected $messages = array();

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

        $this->messages[] = (object) array(
            'type'    => $type,
            'message' => $message,
        );
    }

    public function hasMessages()
    {
        return false === empty($this->messages);
    }

    public function getMessages()
    {
        $messages = $this->messages;
        $this->messages = array();
        return $messages;
    }

    final private function __construct()
    {
        if (Platform::isCli()) {
            $this->isCli = true;
            return;
        }

        $this->session = Session::getSession();

        $stored = $this->session->get('messages');
        if (is_array($stored)) {
            $this->messages = $stored;
            $this->session->set('messages', array());
            $this->session->write();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Notification();
        }
        return self::$instance;
    }

    final public function __destruct()
    {
        if ($this->isCli) {
            return;
        }
        if ($this->session->get('messages') !== $this->messages) {
            $this->session->set('messages', $this->messages);
        }
        $this->session->write();

        unset($this->session);
    }
}
