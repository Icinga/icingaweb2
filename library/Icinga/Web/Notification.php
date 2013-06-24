<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use Icinga\Exception\ProgrammingError;
use Icinga\Application\Platform;
use Icinga\Application\Logger as Log;

/**
 * Class Notification
 * @package Icinga\Web
 */
class Notification
{
    /**
     * @var Notification
     */
    private static $instance;

    /**
     * @var bool
     */
    private $cliFlag = false;

    /**
     * @param boolean $cliFlag
     */
    public function setCliFlag($cliFlag)
    {
        $this->cliFlag = $cliFlag;
    }

    /**
     * @return boolean
     */
    public function getCliFlag()
    {
        return $this->cliFlag;
    }

    /**
     * @param $msg
     */
    public static function info($msg)
    {
        self::getInstance()->addMessage($msg, 'info');
    }

    /**
     * @param $msg
     */
    public static function success($msg)
    {
        self::getInstance()->addMessage($msg, 'success');
    }

    /**
     * @param $msg
     */
    public static function warning($msg)
    {
        self::getInstance()->addMessage($msg, 'warning');
    }

    /**
     * @param $msg
     */
    public static function error($msg)
    {
        self::getInstance()->addMessage($msg, 'error');
    }

    /**
     * @param $message
     * @param string $type
     * @throws \Icinga\Exception\ProgrammingError
     */
    public function addMessage($message, $type = 'info')
    {
        if (!in_array(
            $type,
            array(
                'info',
                'error',
                'warning',
                'success'
            )
        )
        ) {
            throw new ProgrammingError(
                sprintf(
                    '"%s" is not a valid notification type',
                    $type
                )
            );
        }

        if ($this->cliFlag) {
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

        $mo = (object)array(
            'type' => $type,
            'message' => $message,
        );

        // Get, change, set - just to be on the safe side:
        $msgs = $this->session->messages;
        $msgs[] = $mo;
        $this->session->messages = $msgs;
    }

    /**
     * @return bool
     */
    public function hasMessages()
    {
        return !empty($this->session->messages);
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        $msgs = $this->session->messages;
        $this->session->messages = array();
        return $msgs;
    }

    /**
     * Create a new Notification object
     */
    final private function __construct()
    {
        //$this->session = new SessionNamespace('IcingaNotification');
        //if (!is_array($this->session->messages)) {
            $this->session->messages = array();
        //}

        if (Platform::isCli()) {
            $this->cliFlag = true;
        }
    }

    /**
     * @return Notification
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Notification();
        }
        return self::$instance;
    }
}
