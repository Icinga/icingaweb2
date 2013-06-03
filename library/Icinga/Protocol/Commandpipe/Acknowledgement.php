<?php
namespace Icinga\Protocol\Commandpipe;

use \Icinga\Protocol\Commandpipe\Exception\InvalidCommandException;
use \Icinga\Protocol\Commandpipe\Comment;


class Acknowledgement implements IComment
{
    public $expireTime = -1;
    public $notify = false;
    public $comment = null;
    public $sticky;
    public function setExpireTime($time)
    {
        $this->expireTime = intval($time);
    }

    public function setNotify($bool)
    {
        $this->notify = (bool) $bool;
    }

    public function __construct(Comment $comment, $notify = false, $expire = -1, $sticky=false)
    {
        $this->comment = $comment;
        $this->setNotify($notify);
        $this->setExpireTime($expire);
        $this->sticky = $sticky;
    }

    public function getFormatString($type)
    {
        $params = ';'.($this->sticky ? '2' : '0').';'.($this->notify ? '1 ': '0').';'.($this->comment->persistent ? '1' : '0');
        $params .= ($this->expireTime > -1 ? ';'.$this->expireTime.';' : ';').$this->comment->author.';'.$this->comment->comment;

        switch($type) {
            case CommandPipe::TYPE_HOST:
                $typeVar = "HOST";
                $params = ";%s".$params;
                break;
            case CommandPipe::TYPE_SERVICE:
                $typeVar = "SVC";
                $params = ";%s;%s".$params;
                break;
            default:
                throw new InvalidCommandException("Acknowledgements can only apply on hosts and services ");
        }

        $base = "ACKNOWLEDGE_{$typeVar}_PROBLEM".($this->expireTime > -1 ? '_EXPIRE' : '' );
        return $base.$params;



    }


}