<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Cli;

use Icinga\Cli\Screen;

class CliUtils
{
    protected $hostColors = array(
        0 => array('black', 'lightgreen'),
        1 => array('black', 'lightred'),
        2 => array('black', 'brown'),
        99 => array('black', 'lightgray'),
    );
    protected $serviceColors = array(
        0 => array('black', 'lightgreen'),
        1 => array('black', 'yellow'),
        2 => array('black', 'lightred'),
        3 => array('black', 'lightpurple'),
        99 => array('black', 'lightgray'),
    );
    protected $hostStates = array(
        0 => 'UP',
        1 => 'DOWN',
        2 => 'UNREACHABLE',
        99 => 'PENDING',
    );

    protected $serviceStates = array(
        0 => 'OK',
        1 => 'WARNING',
        2 => 'CRITICAL',
        3 => 'UNKNOWN',
        99 => 'PENDING',
    );

    protected $screen;
    protected $hostState;
    protected $serviceState;

    public function __construct(Screen $screen)
    {
        $this->screen = $screen;
    }

    public function setHostState($state)
    {
        $this->hostState = $state;
    }

    public function setServiceState($state)
    {
        $this->serviceState = $state;
    }

    public function shortHostState($state = null)
    {
        if ($state === null) {
            $state = $this->hostState;
        }
        return sprintf('%-4s', substr($this->hostStates[$state], 0, 4));
    }

    public function shortServiceState($state = null)
    {
        if ($state === null) {
            $state = $this->serviceState;
        }
        return sprintf('%-4s', substr($this->serviceStates[$state], 0, 4));
    }

    public function hostStateBackground($text, $state = null)
    {
        if ($state === null) {
            $state = $this->hostState;
        }
        return $this->screen->colorize(
            $text,
            $this->hostColors[$state][0],
            $this->hostColors[$state][1]
        );
    }

    public function serviceStateBackground($text, $state = null)
    {
        if ($state === null) {
            $state = $this->serviceState;
        }
        return $this->screen->colorize(
            $text,
            $this->serviceColors[$state][0],
            $this->serviceColors[$state][1]
        );
    }

    public function objectStateFlags($type, & $row)
    {
        $extra = array();
        if ($row->{$type . '_in_downtime'}) {
            if ($this->screen->hasUtf8()) {
                $extra[] = 'DOWNTIME ⌚';
            } else {
                $extra[] = 'DOWNTIME';
            }
        }
        if ($row->{$type . '_acknowledged'}) {
            if ($this->screen->hasUtf8()) {
                $extra[] = 'ACK ✓';
            } else {
                $extra[] = 'ACK';
            }
        }

        if (empty($extra)) {
            $extra = '';
        } else {
            $extra = sprintf(' [ %s ]', implode(', ', $extra));
        }
        return $extra;
    }

}
