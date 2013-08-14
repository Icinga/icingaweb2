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

namespace Icinga\Form\Config;

use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\Icinga;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Element\Note;
use \Icinga\Web\Form\Decorator\ConditionalHidden;
use \Zend_Config;
use \Zend_Form_Element_Text;

/**
 * Form class for setting the application wide logging configuration
 *
 */
class LoggingForm extends Form
{
    /**
     * The configuration to use for this form
     *
     * @var Zend_Config
     */
    private $config = null;

    /**
     * Set the configuration of this form
     *
     * If not called, default values are used instead
     *
     * @param Zend_Config $cfg      The config.ini to set with this form
     */
    public function setConfiguration(Zend_Config $cfg)
    {
        $this->config = $cfg;
    }

    /**
     * Return true if the debug log path textfield should be displayed
     *
     * This is the case if the "logging_use_debug" field is autosubmitted
     * and true or if it is not submitted, but the configuration for debug
     * logging is set to true
     *
     * @param Zend_Config $config       The debug section of the config.ini
     *
     * @return bool                     Whether to display the debug path field or not
     */
    private function shouldDisplayDebugLog(Zend_Config $config)
    {
        $debugParam = $this->getRequest()->getParam('logging_use_debug', null);
        if ($debugParam !== null) {
            return intval($debugParam) === 1;
        } else {
            return intval($config->get('enable', 0)) === 1;
        }

    }

    private function loggingIsEnabled(Zend_Config $config)
    {
        $loggingRequestParam = $this->getRequest()->getParam('logging_enable', null);

        if ($loggingRequestParam !== null) {
            return intval($loggingRequestParam) === 1;
        } else {
            return intval($config->get('enable', 0)) === 1;
        }
    }

    /**
     * @see Form::create()
     */
    public function create()
    {
        if ($this->config === null) {
            $this->config = new Zend_Config(array());
        }

        $logging = $this->config->logging;
        if ($logging === null) {
            $logging = new IcingaConfig(array());
        }

        $debug = $logging->debug;
        if ($debug === null) {
            $debug = new IcingaConfig(array());
        }
        $this->addElement(
            'checkbox',
            'logging_enable',
            array(
                'label'     => 'Logging enabled',
                'required'  => true,
                'value'     => $this->loggingIsEnabled($logging)
            )
        );
        if (!$this->loggingIsEnabled($debug)) {
            $this->addElement(
                new Note(
                    array(
                        'name'      => 'note_logging_disabled',
                        'value'     => 'Logging is disabled.'
                    )
                )
            );
            $this->setSubmitLabel('Save changes');
            $this->enableAutoSubmit(array('logging_enable'));

            return;
        }

        $this->addElement(
            'text',
            'logging_app_path',
            array(
                'label'         => 'Application log path',
                'required'      => true,
                'value'         => $logging->get('target', '/var/log/icingaweb.log')
            )
        );

        $this->addElement(new Note(
            array(
                'name' => 'note_logging_app_path',
                'value'=> 'The logfile to write the icingaweb debug logs to. The webserver must be able to write'
                            . 'at this location'
            )
        ));

        $this->addElement(
            'checkbox',
            'logging_app_verbose',
            array(
                'label'     => 'Verbose logging',
                'required'  => true,
                'value'     => intval($logging->get('verbose', 0)) === 1
            )
        );

        $this->addElement(new Note(
            array(
                'name' => 'note_logging_app_verbose',
                'value'=> 'Check to write more verbose output to the icinga log file'
            )
        ));

        $this->addElement(
            'checkbox',
            'logging_use_debug',
            array(
                'label'     => 'Use debug log',
                'required'  => true,
                'value'     => $this->shouldDisplayDebugLog($debug)
            )
        );
        $this->addElement(new Note(
            array(
                'name' => 'note_logging_use_debug',
                'value'=> 'Check to write a seperate debug log (Warning: This file can grow very big)'
            )
        ));


        $textLoggingDebugPath = new Zend_Form_Element_Text(
            array(
                'name'      => 'logging_debug_path',
                'label'     => 'Debug log path',
                'required'  => true,
                'condition' => $this->shouldDisplayDebugLog($debug),
                'value'     => $debug->get('target')
            )
        );
        $loggingPathNote = new Note(
            array(
                'name'      => 'note_logging_debug_path',
                'value'     => 'Set the path to the debug log',
                'condition' => $this->shouldDisplayDebugLog($debug)
            )
        );
        $decorator = new ConditionalHidden();
        $this->addElement($textLoggingDebugPath);
        $this->addElement($loggingPathNote);

        $textLoggingDebugPath->addDecorator($decorator);
        $loggingPathNote->addDecorator($decorator);

        $this->enableAutoSubmit(array('logging_use_debug', 'logging_enable'));

        $this->setSubmitLabel('Save changes');
    }

}