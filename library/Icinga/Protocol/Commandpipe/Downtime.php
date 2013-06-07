<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Class Downtime
 * @package Icinga\Protocol\Commandpipe
 */
class Downtime
{
    /**
     * @var mixed
     */
    public $startTime;

    /**
     * @var mixed
     */
    public $endTime;

    /**
     * @var mixed
     */
    private $fixed = false;

    /**
     * @var mixed
     */
    public $duration;

    /**
     * @var mixed
     */
    public $comment;

    /**
     * @param $start
     * @param $end
     * @param Comment $comment
     * @param int $duration
     */
    public function __construct($start, $end, Comment $comment, $duration = 0)
    {
        $this->startTime = $start;
        $this->endTime = $end;
        $this->comment = $comment;
        if ($duration != 0) {
            $this->fixed = true;
        }
        $this->duration = intval($duration);
    }

    /**
     * @param $type
     * @return string
     */
    public function getFormatString($type)
    {
        return 'SCHEDULE_' . $type . '_DOWNTIME;%s'
        . ($type == CommandPipe::TYPE_SERVICE ? ';%s;' : ';')
        . $this->startTime . ';' . $this->endTime
        . ';' . ($this->fixed ? '1' : '0') . ';' . $this->duration . ';0;'
        . $this->comment->author . ';' . $this->comment->comment;
    }
}
