<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Command;

use Icinga\Protocol\Commandpipe\Command;

/**
 * Configurable simple command
 */
class SingleArgumentCommand extends Command
{
    /**
     * Value used for this command
     *
     * @var mixed
     */
    private $value;

    /**
     * Name of host command
     *
     * @var string
     */
    private $hostCommand;

    /**
     * Name of service command
     *
     * @var string
     */
    private $serviceCommand;

    /**
     * Ignore host in command string
     *
     * @var bool
     */
    private $ignoreObject = false;

    /**
     * Setter for this value
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Setter for command names
     *
     * @param string $hostCommand
     * @param string $serviceCommand
     */
    public function setCommand($hostCommand, $serviceCommand)
    {
        $this->hostCommand = $hostCommand;
        $this->serviceCommand = $serviceCommand;
    }

    /**
     * Ignore object values upon command creation
     *
     * @param bool $flag
     */
    public function setObjectIgnoreFlag($flag = true)
    {
        $this->ignoreObject = (bool) $flag;
    }

    /**
     * Return this command's arguments in the order expected by the actual command definition
     *
     * @return array
     */
    public function getArguments()
    {
        if ($this->value !== null) {
            return array($this->value);
        } else {
            return array();
        }
    }

    /**
     * Build the argument string based on objects and arguments
     *
     * @param   array $objectNames
     *
     * @return  string String to append to command
     */
    private function getArgumentString(array $objectNames)
    {
        $data = array();
        if ($this->ignoreObject === true) {
            $data = $this->getArguments();
        } else {
            $data = array_merge($objectNames, $this->getArguments());
        }

        return implode(';', $data);
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     */
    public function getHostCommand($hostname)
    {
        return strtoupper($this->hostCommand). ';' . $this->getArgumentString(array($hostname));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string $hostname       The name of the host to insert
     * @param   string $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return strtoupper($this->serviceCommand)
        . ';'
        . $this->getArgumentString(array($hostname, $servicename));
    }
}
