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

namespace Icinga\Module\Monitoring\Form\Command;

use Zend_Controller_Request_Abstract;
use \Zend_Form_Element_Hidden;
use Icinga\Module\Monitoring\Command\AcknowledgeCommand;
use Icinga\Module\Monitoring\Command\SingleArgumentCommand;

/**
 * Sending commands to core which only have one value
 */
class SingleArgumentCommandForm extends CommandForm
{
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
     * Name of global command
     *
     * @var array
     */
    private $globalCommands = array();

    /**
     * Name of the parameter used as value
     *
     * @var string
     */
    private $parameterName;

    /**
     * Value of used parameter
     *
     * @var mixed
     */
    private $parameterValue;

    /**
     * Flag to ignore object name
     *
     * @var bool
     */
    private $ignoreObject = false;

    /**
     * Set command names
     *
     * @param string $hostCommand       Name of host command
     * @param string $serviceCommand    Name of service command
     */
    public function setCommand($hostCommand, $serviceCommand = null)
    {
        $this->hostCommand = $hostCommand;

        if ($serviceCommand !== null) {
            $this->serviceCommand = $serviceCommand;
        }
    }

    /**
     * Setter for global commands
     *
     * @param string $hostOrGenericGlobalCommand    Generic command or one for host
     * @param string $serviceGlobalCommand          If any (leave blank if you need a global global)
     */
    public function setGlobalCommands($hostOrGenericGlobalCommand, $serviceGlobalCommand = null)
    {
        $this->globalCommands[] = $hostOrGenericGlobalCommand;

        if ($serviceGlobalCommand !== null) {
            $this->globalCommands[] = $serviceGlobalCommand;
        }
    }

    /**
     * Use an explicit value to send with command
     *
     * @param mixed $parameterValue
     */
    public function setParameterValue($parameterValue)
    {
        $this->parameterValue = $parameterValue;
    }

    /**
     * Use a form field to take the value from
     *
     * @param string $parameterName
     */
    public function setParameterName($parameterName)
    {
        $this->parameterName = $parameterName;
    }

    /**
     * Flag to ignore every objects
     *
     * @param bool $flag
     */
    public function setObjectIgnoreFlag($flag = true)
    {
        $this->ignoreObject = (bool) $flag;
    }

    /**
     *
     */
    protected function create()
    {
        if ($this->parameterName) {
            $field = new Zend_Form_Element_Hidden($this->parameterName);
            $value = $this->getRequest()->getParam($field->getName());
            $field->setValue($value);
            $this->addElement($field);
        }
        parent::create();
    }

    public function setRequest(Zend_Controller_Request_Abstract $request)
    {
        parent::setRequest($request);

        if ($this->globalCommand === true) {
            $this->setParameterName('global');
        }
    }


    /**
     * Create command object for CommandPipe protocol
     *
     * @return SingleArgumentCommand
     */
    public function createCommand()
    {
        $command = new SingleArgumentCommand();

        if ($this->parameterValue !== null) {
            $command->setValue($this->parameterValue);
        } else {
            $command->setValue($this->getValue($this->parameterName));
        }

        if ($this->provideGlobalCommand() == true) {
            $command->setGlobalCommands($this->globalCommands);
            $this->ignoreObject = true;
        } else {
            $command->setCommand($this->hostCommand, $this->serviceCommand);
        }

        $command->setObjectIgnoreFlag($this->ignoreObject);

        return $command;
    }
}
