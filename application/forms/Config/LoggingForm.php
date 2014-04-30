<?php
// @codeCoverageIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Zend_Config;
use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Web\Form\Validator\WritablePathValidator;

/**
 * Form class for setting the application wide logging configuration
 */
class LoggingForm extends Form
{
    /**
     * Return the default logging directory for type "stream"
     *
     * @return  string
     */
    protected function getDefaultLogDir()
    {
        return realpath(Icinga::app()->getApplicationDir() . '/../var/log/icingaweb.log');
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
        if (($loggingConfig = $config->logging) === null) {
            $loggingConfig = new Zend_Config(array());
        }

        $this->addElement(
            'checkbox',
            'logging_enable',
            array(
                'required'  => true,
                'label'     => t('Logging Enabled'),
                'helptext'  => t('Check this to enable logging.'),
                'value'     => $loggingConfig->enable ? 1 : 0
            )
        );
        $this->addElement(
            'select',
            'logging_level',
            array(
                'required'      => true,
                'label'         => t('Logging Level'),
                'helptext'      => t('The maximum loglevel to emit.'),
                'value'         => intval($loggingConfig->get('level', 0)),
                'multiOptions'  => array(
                    0 => t('Error'),
                    1 => t('Warning'),
                    2 => t('Information'),
                    3 => t('Debug')
                )
            )
        );
        $this->addElement(
            'select',
            'logging_type',
            array(
                'required'      => true,
                'label'         => t('Logging Type'),
                'helptext'      => t('The type of logging to utilize.'),
                'value'         => $loggingConfig->get('type', 'stream'),
                'multiOptions'  => array(
                    'stream'    => t('File'),
                    'syslog'    => 'Syslog'
                )
            )
        );
        $this->enableAutoSubmit(array('logging_type'));

        switch ($this->getRequest()->getParam('logging_type', $loggingConfig->get('type', 'stream')))
        {
            case 'stream':
                $this->addElement(
                    'text',
                    'logging_target',
                    array(
                        'required'      => true,
                        'label'         => t('Filepath'),
                        'helptext'      => t('The logfile to write messages to.'),
                        'value'         => $loggingConfig->target ? $loggingConfig->target : $this->getDefaultLogDir(),
                        'validators'    => array(new WritablePathValidator())
                    )
                );
                break;
            case 'syslog':
                $this->addElement(
                    'text',
                    'logging_application',
                    array(
                        'required'  => true,
                        'label'     => t('Application Prefix'),
                        'helptext'  => t('The name of the application by which to prefix syslog messages.'),
                        'value'     => $loggingConfig->get('application', 'icingaweb')
                    )
                );
                $this->addElement(
                    'select',
                    'logging_facility',
                    array(
                        'required'      => true,
                        'label'         => t('Facility'),
                        'helptext'      => t('The Syslog facility to utilize.'),
                        'value'         => $loggingConfig->get('facility', 'LOG_USER'),
                        'multiOptions'  => array(
                            'LOG_USER'
                        )
                    )
                );
                break;
        }

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return a Zend_Config object containing the state defined in this form
     *
     * @return  Zend_Config     The config defined in this form
     */
    public function getConfig()
    {
        $values = $this->getValues();
        $cfg = $this->getConfiguration()->toArray();

        $cfg['logging']['enable'] = $values['logging_enable'] == 1;
        $cfg['logging']['level'] = $values['logging_level'];

        switch ($values['logging_type'])
        {
            case 'stream':
                $cfg['logging']['target'] = $values['logging_target'];
                break;
            case 'syslog':
                $cfg['logging']['application'] = $values['logging_application'];
                $cfg['logging']['facility'] = $values['logging_facility'];
                break;
        }

        return new Zend_Config($cfg);
    }
}
// @codeCoverageIgnoreEnd
