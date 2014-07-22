<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Protocol\Commandpipe\Command;

/**
 * Command for scheduling a new downtime
 */
class ScheduleDowntimeCommand extends Command
{
    /**
     * When this downtime should start
     *
     * @var int     The time as UNIX timestamp
     */
    private $startTime;

    /**
     * When this downtime should end
     *
     * @var int     The time as UNIX timestamp
     */
    private $endTime;

    /**
     * The comment associated with this downtime
     *
     * @var Comment
     */
    private $comment;

    /**
     * Whether this is a fixed or flexible downtime
     *
     * @var bool
     */
    private $fixed;

    /**
     * The duration to use when this downtime is a flexible one
     *
     * @var int     In seconds
     */
    private $duration;

    /**
     * The ID of the downtime which triggers this one
     *
     * @var int
     */
    private $triggerId;

    /**
     * Whether this downtime should trigger children hosts
     *
     * @var bool
     */
    private $triggerChildren;

    /**
     * Set when to start this downtime
     *
     * @param   int     $startTime
     *
     * @return  self
     */
    public function setStart($startTime)
    {
        $this->startTime = (int) $startTime;
        return $this;
    }

    /**
     * Set when to end this downtime
     *
     * @param   int     $endTime
     *
     * @return  self
     */
    public function setEnd($endTime)
    {
        $this->endTime = (int) $endTime;
        return $this;
    }

    /**
     * Set the comment for this downtime
     *
     * @param   Comment     $comment
     *
     * @return  self
     */
    public function setComment(Comment $comment)
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Set whether this downtime is fixed or flexible
     *
     * @param   bool    $state
     *
     * @return  self
     */
    public function setFixed($state)
    {
        $this->fixed = (bool) $state;
        return $this;
    }

    /**
     * Set the duration of this downtime
     *
     * @param   int     $duration
     *
     * @return  self
     */
    public function setDuration($duration)
    {
        $this->duration = (int) $duration;
        return $this;
    }

    /**
     * Set the triggering id for this downtime
     *
     * @param   int     $triggerId
     *
     * @return  self
     */
    public function setTriggerId($triggerId)
    {
        $this->triggerId = (int) $triggerId;
        return $this;
    }

    /**
     * Initialise a new command object to schedule a downtime
     *
     * @param   int         $startTime      When to start this downtime as UNIX timestamp
     * @param   int         $endTime        When to end this downtime as UNIX timestamp
     * @param   Comment     $comment        The comment to use for this downtime
     * @param   bool        $fixed          Whether this downtime is fixed or flexible
     * @param   int         $duration       How long in seconds this downtime should apply if flexible
     * @param   int         $triggerId      The ID of the triggering downtime
     */
    public function __construct($startTime, $endTime, Comment $comment, $fixed = true, $duration = 0, $triggerId = 0)
    {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->comment = $comment;
        $this->fixed = $fixed;
        $this->duration = $duration;
        $this->triggerId = $triggerId;
    }

    /**
     * Include all children hosts with this command
     *
     * @param   bool    $state
     * @param   bool    $trigger    Whether children are triggered by this downtime
     *
     * @return  self
     */
    public function includeChildren($state = true, $trigger = false) {
        $this->triggerChildren = (bool) $trigger;
        return parent::includeChildren($state);
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return  array
     * @see     Command::getArguments()
     */
    public function getArguments()
    {
        return array_merge(
            array(
                $this->startTime,
                $this->endTime,
                $this->fixed ? '1' : '0',
                $this->triggerId,
                $this->duration
            ),
            $this->comment->getArguments(true)
        );
    }

    /**
     * Return the command as a string for the given host
     *
     * @param   type    $hostname       The name of the host to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getHostCommand($hostname)
    {
        if ($this->withChildren) {
            return sprintf('SCHEDULE_AND_PROPAGATE%s_HOST_DOWNTIME;', $this->triggerChildren ? '_TRIGGERED' : '')
            . implode(';', array_merge(array($hostname), $this->getArguments()));
        } else {
            return sprintf('SCHEDULE_HOST%s_DOWNTIME;', $this->onlyServices ? '_SVC' : '')
                . implode(';', array_merge(array($hostname), $this->getArguments()));
        }
    }

    /**
     * Return the command as a string for the given service
     *
     * @param   type    $hostname       The name of the host to insert
     * @param   type    $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return 'SCHEDULE_SVC_DOWNTIME;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getArguments()
            )
        );
    }

    /**
     * Return the command as a string for the given hostgroup
     *
     * @param   type    $hostgroup      The name of the hostgroup to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getHostgroupCommand($hostgroup)
    {
        return sprintf('SCHEDULE_HOSTGROUP_%s_DOWNTIME;', $this->withoutHosts ? 'SVC' : 'HOST')
            . implode(';', array_merge(array($hostgroup), $this->getArguments()));
    }

    /**
     * Return the command as a string for the given servicegroup
     *
     * @param   type    $servicegroup   The name of the servicegroup to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServicegroupCommand($servicegroup)
    {
        return sprintf('SCHEDULE_SERVICEGROUP_%s_DOWNTIME;', $this->withoutServices ? 'HOST' : 'SVC')
            . implode(';', array_merge(array($servicegroup), $this->getArguments()));
    }
}
