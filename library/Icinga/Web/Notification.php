<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

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
    /**
     * Notification type info
     *
     * @var string
     */
    const INFO = 'info';

    /**
     * Notification type error
     *
     * @var string
     */
    const ERROR = 'error';

    /**
     * Notification type success
     *
     * @var string
     */
    const SUCCESS = 'success';

    /**
     * Notification type warning
     *
     * @var string
     */
    const WARNING = 'warning';

    /**
     * Name of the session key for notification messages
     *
     * @var string
     */
    const SESSION_KEY = 'session';

    /**
     * Singleton instance
     *
     * @var self
     */
    protected static $instance;

    /**
     * Whether the platform is CLI
     *
     * @var bool
     */
    protected $isCli = false;

    /**
     * Notification messages
     *
     * @var array
     */
    protected $messages = array();

    /**
     * Session
     *
     * @var Session
     */
    protected $session;

    /**
     * Create the notification instance
     */
    final private function __construct()
    {
        if (Platform::isCli()) {
            $this->isCli = true;
            return;
        }

        $this->session = Session::getSession();
        $messages = $this->session->get(self::SESSION_KEY);
        if (is_array($messages)) {
            $this->messages = $messages;
            $this->session->delete(self::SESSION_KEY);
            $this->session->write();
        }
    }

    /**
     * Get the Notification instance
     *
     * @return Notification
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Add info notification
     *
     * @param string $msg
     */
    public static function info($msg)
    {
        self::getInstance()->addMessage($msg, self::INFO);
    }

    /**
     * Add error notification
     *
     * @param string $msg
     */
    public static function error($msg)
    {
        self::getInstance()->addMessage($msg, self::ERROR);
    }

    /**
     * Add success notification
     *
     * @param string $msg
     */
    public static function success($msg)
    {
        self::getInstance()->addMessage($msg, self::SUCCESS);
    }

    /**
     * Add warning notification
     *
     * @param string $msg
     */
    public static function warning($msg)
    {
        self::getInstance()->addMessage($msg, self::WARNING);
    }

    /**
     * Add a notification message
     *
     * @param   string $message
     * @param   string $type
     */
    protected function addMessage($message, $type = self::INFO)
    {
        if ($this->isCli) {
            $msg = sprintf('[%s] %s', $type, $message);
            switch ($type) {
                case self::INFO:
                case self::SUCCESS:
                    Logger::info($msg);
                    break;
                case self::ERROR:
                    Logger::error($msg);
                    break;
                case self::WARNING:
                    Logger::warning($msg);
                    break;
            }
        } else {
            $this->messages[] = (object) array(
                'type'    => $type,
                'message' => $message,
            );
        }
    }

    /**
     * Pop the notification messages
     *
     * @return array
     */
    public function popMessages()
    {
        $messages = $this->messages;
        $this->messages = array();
        return $messages;
    }

    /**
     * Get whether notification messages have been added
     *
     * @return bool
     */
    public function hasMessages()
    {
        return ! empty($this->messages);
    }

    /**
     * Destroy the notification instance
     */
    final public function __destruct()
    {
        if ($this->isCli) {
            return;
        }
        if ($this->hasMessages() && $this->session->get('messages') !== $this->messages) {
            $this->session->set(self::SESSION_KEY, $this->messages);
            $this->session->write();
        }
    }
}
