<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

use \Icinga\Protocol\Commandpipe\Exception\InvalidCommandException;
use \Icinga\Protocol\Commandpipe\Comment;

/**
 * Class Acknowledgement
 * @package Icinga\Protocol\Commandpipe
 */
class Acknowledgement implements IComment
{
    /**
     * @var int
     */
    public $expireTime = -1;

    /**
     * @var bool
     */
    public $notify = false;

    /**
     * @var Comment|null
     */
    public $comment = null;

    /**
     * @var bool
     */
    public $sticky;

    /**
     * @param int $time
     */
    public function setExpireTime($time)
    {
        $this->expireTime = intval($time);
    }

    /**
     * @param boolean $bool
     */
    public function setNotify($bool)
    {
        $this->notify = (bool)$bool;
    }

    /**
     * @param Comment $comment
     * @param bool $notify
     * @param $expire
     * @param bool $sticky
     */
    public function __construct(Comment $comment, $notify = false, $expire = -1, $sticky = false)
    {
        $this->comment = $comment;
        $this->setNotify($notify);
        $this->setExpireTime($expire);
        $this->sticky = $sticky;
    }

    /**
     * @param $type
     * @return string
     * @throws Exception\InvalidCommandException
     */
    public function getFormatString($type)
    {
        $params = ';'
            . ($this->sticky ? '2' : '0')
            . ';' . ($this->notify ? '1 ' : '0')
            . ';' . ($this->comment->persistent ? '1' : '0');

        $params .= ($this->expireTime > -1 ? ';'. $this->expireTime . ';' : ';')
            . $this->comment->author . ';' . $this->comment->comment;

        switch ($type) {
            case CommandPipe::TYPE_HOST:
                $typeVar = "HOST";
                $params = ";%s" . $params;
                break;
            case CommandPipe::TYPE_SERVICE:
                $typeVar = "SVC";
                $params = ";%s;%s" . $params;
                break;
            default:
                throw new InvalidCommandException("Acknowledgements can only apply on hosts and services ");
        }

        $base = "ACKNOWLEDGE_{$typeVar}_PROBLEM" . ($this->expireTime > -1 ? '_EXPIRE' : '');
        return $base . $params;
    }
}
