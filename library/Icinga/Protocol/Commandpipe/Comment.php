<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Class Comment
 * @package Icinga\Protocol\Commandpipe
 */
class Comment implements IComment
{
    /**
     * @var bool
     */
    public $persistent = false;

    /**
     * @var string
     */
    public $author = "";

    /**
     * @var string
     */
    public $comment = "";

    /**
     * @param $author
     * @param $comment
     * @param bool $persistent
     */
    public function __construct($author, $comment, $persistent = false)
    {
        $this->author = $author;
        $this->comment = $comment;
        $this->persistent = $persistent;
    }

    /**
     * @param $type
     * @return string
     * @throws InvalidCommandException
     */
    public function getFormatString($type)
    {
        $params = ';' . ($this->persistent ? '1' : '0') . ';' . $this->author . ';' . $this->comment;

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
        return "ADD_{$typeVar}_COMMENT$params";
    }
}
