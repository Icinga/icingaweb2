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
 * Command to submit passive check results
 */
class SubmitPassiveCheckresultCommand extends Command
{
    /**
     * The plugin-state that is being reported
     *
     * @var int
     */
    private $state;

    /**
     * The output that is included
     *
     * @var string
     */
    private $output;

    /**
     * The performance data that is included
     *
     * @var string
     */
    private $perfData;

    /**
     * Initialises a new command object to submit a passive check result
     *
     * @param   int     $state      The plugin-state to report
     * @param   string  $output     The plugin-output to include
     * @param   string  $perfData   The performance data to include
     */
    public function __construct($state, $output, $perfData)
    {
        $this->state = $state;
        $this->output = $output;
        $this->perfData = $perfData;
    }

    /**
     * Set which plugin-state is being reported
     *
     * @param   int     $state
     *
     * @return  self
     */
    public function setState($state)
    {
        $this->state = (int) $state;
        return $this;
    }

    /**
     * Set the plugin-output to include in the result
     *
     * @param   string  $output
     *
     * @return  self
     */
    public function setOutput($output)
    {
        $this->output = (string) $output;
        return $this;
    }

    /**
     * Set the performance data to include in the result
     *
     * @param   string  $perfData
     * @return  self
     */
    public function setPerformanceData($perfData)
    {
        $this->perfData = (string) $perfData;
        return $this;
    }

    /**
     * Return this command's parameters properly arranged in an array
     *
     * @return  array
     * @see     Command::getArguments()
     */
    public function getArguments()
    {
        return array(
            $this->state,
            $this->perfData ? $this->output . '|' . $this->perfData : $this->output
        );
    }

    /**
     * Return the command as a string with the given host being inserted
     *
     * @param   string  $hostname   The name of the host to insert
     *
     * @return  string              The string representation of the command
     * @see     Command::getHostCommand()
     */
    public function getHostCommand($hostname)
    {
        return 'PROCESS_HOST_CHECK_RESULT;' . implode(';', array_merge(array($hostname), $this->getArguments()));
    }

    /**
     * Return the command as a string with the given host and service being inserted
     *
     * @param   string  $hostname       The name of the host to insert
     * @param   string  $servicename    The name of the service to insert
     *
     * @return  string                  The string representation of the command
     * @see     Command::getServiceCommand()
     */
    public function getServiceCommand($hostname, $servicename)
    {
        return 'PROCESS_SERVICE_CHECK_RESULT;' . implode(
            ';',
            array_merge(
                array($hostname, $servicename),
                $this->getArguments()
            )
        );
    }
}
