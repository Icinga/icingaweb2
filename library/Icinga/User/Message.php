<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User;

use Zend_Log;

/**
 * Class Message
 *
 * A Message with an additional logging level to indicate the type.
 *
 * @package Icinga\User
 */
class Message
{
    /**
     * The content of this message
     *
     * @var string
     */
    private $message;

    /**
     * The logging-level of this message
     */
    private $level;

    /**
     * Create a new Message
     *
     * @param string    $message  The message content
     * @param           $level    The status of the message
     *                            * Zend_Log::INFO
     *                            * Zend_Log::ERR
     */
    public function __construct($message, $level = Zend_Log::INFO)
    {
        $this->message = $message;
        $this->level = $level;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return The
     */
    public function getLevel()
    {
        return $this->level;
    }
}
