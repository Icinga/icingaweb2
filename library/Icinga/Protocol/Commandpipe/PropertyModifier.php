<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
 * Copyright (C) 2013 Icinga Development Team
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Commandpipe;

/**
 * Class PropertyModifier
 * @package Icinga\Protocol\Commandpipe
 */
class PropertyModifier
{
    /**
     *
     */
    const STATE_ENABLE = 1;

    /**
     *
     */
    const STATE_DISABLE = 0;

    /**
     *
     */
    const STATE_KEEP = -1;

    /**
     *
     */
    const FLAPPING = "%s_FLAP_DETECTION";

    /**
     *
     */
    const ACTIVE = "%s_CHECK";

    /**
     *
     */
    const PASSIVE = "PASSIVE_%s_CHECKS";

    /**
     *
     */
    const NOTIFICATIONS = "%s_NOTIFICATIONS";

    /**
     *
     */
    const FRESHNESS = "%s_FRESHNESS_CHECKS";

    /**
     *
     */
    const EVENTHANDLER = "%s_EVENT_HANDLER";

    /**
     * @var array
     */
    public $flags = array(
        self::FLAPPING => self::STATE_KEEP,
        self::ACTIVE => self::STATE_KEEP,
        self::PASSIVE => self::STATE_KEEP,
        self::NOTIFICATIONS => self::STATE_KEEP,
        self::FRESHNESS => self::STATE_KEEP,
        self::EVENTHANDLER => self::STATE_KEEP
    );

    /**
     * @param array $flags
     */
    public function __construct(array $flags)
    {
        foreach ($flags as $type => $value) {
            if (isset($this->flags[$type])) {
                $this->flags[$type] = $value;
            }
        }
    }

    /**
     * @param $type
     * @return array
     */
    public function getFormatString($type)
    {
        $cmd = array();
        foreach ($this->flags as $cmdTemplate => $setting) {
            if ($setting == self::STATE_KEEP) {
                continue;
            }
            $commandString = ($setting == self::STATE_ENABLE ? "ENABLE_" : "DISABLE_");
            $targetString = $type;
            if ($type == CommandPipe::TYPE_SERVICE && $cmdTemplate == self::FRESHNESS) {
                // the external command definition is inconsistent here..
                $targetString = "SERVICE";
            }
            $commandString .= sprintf($cmdTemplate, $targetString);
            $cmd[] = $commandString;
        }
        return $cmd;
    }
}
