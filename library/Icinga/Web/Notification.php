<?php

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Platform;
use Icinga\Application\Logger as Log;
use Icinga\Authentication\Manager as AuthManager;

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
        $msgs = $this->session->messages;
        $msgs[] = $mo;
        $this->session->messages = $msgs;
    }

    public function hasMessages()
    {
        return ! empty($this->session->messages);
    }

    public function getMessages()
    {
        $msgs = $this->session->messages;
        $this->session->messages = array();
        return $msgs;
    }

    final private function __construct()
    {
        $this->session = AuthManager::getInstance()->getSession();
        if (!is_array($this->session->get('messages'))) {
            $this->session->messages = array();
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
