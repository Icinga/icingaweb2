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

use \Zend_Config;
use \Zend_Form_Element_Text;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\Icinga;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Validator\WritablePathValidator;
use \Icinga\Web\Form\Decorator\ConditionalHidden;

/**
 * Form class for setting the application wide logging configuration
 */
class LoggingForm extends Form
{
    /**
     * Base directory to use instead of the one provided by Icinga::app (used for testing)
     *
     * @var null
     */
    private $baseDir = null;

    /**
     * Set a different base directory to use for default paths instead of the one provided by Icinga::app()
     *
     * @param string $dir The new directory to use
     */
    public function setBaseDir($dir)
    {
        $this->baseDir = $dir;
    }

    /**
     * Return the applications base directory or the value from a previous setBaseDir call
     *
     * This is used to determine the default logging paths in a manner that allows to set a different path
     * during testing
     *
     * @return string
     */
    public function getBaseDir()
    {
        if ($this->baseDir) {
            return $this->baseDir;
        }
        return realpath(Icinga::app()->getApplicationDir() . '/../');
    }

    /**
     * Return true if the debug log path textfield should be displayed
     *
     * This is the case if the "logging_use_debug" field is autosubmitted
     * and true or if it is not submitted, but the configuration for debug
     * logging is set to true
     *
     * @param   Zend_Config $config The debug section of the config.ini
     *
     * @return  bool Whether to display the debug path field or not
     */
    private function shouldDisplayDebugLog(Zend_Config $config)
    {
        $debugParam = $this->getRequest()->getParam('logging_debug_enable', null);
        if ($debugParam !== null) {
            return intval($debugParam) === 1;
        } else {
            return intval($config->get('enable', 0)) === 1;
        }

    }

    /**
     * Create this logging configuration form
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_config_logging');

        $config = $this->getConfiguration();
        $logging = $config->logging;
        if ($logging === null) {
            $logging = new IcingaConfig(array());
        }
        $debug = $config->logging->debug;
        if ($debug === null) {
            $debug = new IcingaConfig(array());
        }

        $txtLogPath = new Zend_Form_Element_Text(
            array(
                'name'          => 'logging_app_target',
                'label'         => 'Application Log Path',
                'helptext'      => 'The logfile to write the icingaweb debug logs to.'
                    . 'The webserver must be able to write at this location',
                'required'      => true,
                'value'         => $logging->get('target', '/var/log/icingaweb.log')
            )
        );
        $txtLogPath->addValidator(new WritablePathValidator());
        $this->addElement($txtLogPath);

        $this->addElement(
            'checkbox',
            'logging_app_verbose',
            array(
                'label'     => 'Verbose Logging',
                'required'  => true,
                'helptext'  => 'Check to write more verbose output to the icinga log file',
                'value'     => intval($logging->get('verbose', 0)) === 1
            )
        );

        $this->addElement(
            'checkbox',
            'logging_debug_enable',
            array(
                'label'     => 'Use Debug Log',
                'required'  => true,
                'helptext'  => 'Check to write a seperate debug log (Warning: This file can grow very big)',
                'value'     => $this->shouldDisplayDebugLog($debug)
            )
        );

        $textLoggingDebugPath = new Zend_Form_Element_Text(
            array(
                'name'      => 'logging_debug_target',
                'label'     => 'Debug Log Path',
                'required'  => $this->shouldDisplayDebugLog($debug),
                'condition' => $this->shouldDisplayDebugLog($debug),
                'value'     => $debug->get('target', $this->getBaseDir() . '/var/log/icinga2.debug.log'),
                'helptext'  => 'Set the path to the debug log'
            )
        );
        $textLoggingDebugPath->addValidator(new WritablePathValidator());

        $decorator = new ConditionalHidden();
        $this->addElement($textLoggingDebugPath);
        $textLoggingDebugPath->addDecorator($decorator);

        $this->enableAutoSubmit(array('logging_debug_enable'));

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     *  Return a Zend_Config object containing the state defined in this form
     *
     *  @return Zend_Config The config defined in this form
     */
    public function getConfig()
    {
        $config = $this->getConfiguration();
        if ($config->logging === null) {
            $config->logging = new IcingaConfig(array());
        }
        if ($config->logging->debug === null) {
            $config->logging->debug = new IcingaConfig(array());
        }

        $values = $this->getValues();
        $cfg = $config->toArray();

        $cfg['logging']['enable']           =   1;
        $cfg['logging']['type']             =   'stream';
        $cfg['logging']['verbose']          =   $values['logging_app_verbose'];
        $cfg['logging']['target']           =   $values['logging_app_target'];

        $cfg['logging']['debug']['enable']  =   intval($values['logging_debug_enable']);
        $cfg['logging']['debug']['type']    =   'stream';
        $cfg['logging']['debug']['target']  =   $values['logging_debug_target'];
        return new Zend_Config($cfg);
    }
}
