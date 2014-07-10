<?php
// {{{ICINGA_LICENSE_HEADER}}}
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
     * Initialize this logging configuration form
     */
    public function init()
    {
        $this->setName('form_config_logging');
        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements()
    {
        $elements = array();

        $elements[] = $this->createElement(
            'checkbox',
            'enable',
            array(
                'required'  => true,
                'label'     => t('Logging Enabled'),
                'helptext'  => t('Check this to enable logging.'),
                'value'     => 0
            )
        );
        $elements[] = $this->createElement(
            'select',
            'level',
            array(
                'required'      => true,
                'label'         => t('Logging Level'),
                'helptext'      => t('The maximum loglevel to emit.'),
                'value'         => 0,
                'multiOptions'  => array(
                    0 => t('Error'),
                    1 => t('Warning'),
                    2 => t('Information'),
                    3 => t('Debug')
                )
            )
        );
        $elements[] = $this->createElement(
            'select',
            'type',
            array(
                'required'      => true,
                'class'         => 'autosubmit',
                'label'         => t('Logging Type'),
                'helptext'      => t('The type of logging to utilize.'),
                'value'         => 'file',
                'multiOptions'  => array(
                    'file'      => t('File'),
                    'syslog'    => 'Syslog'
                )
            )
        );
        $elements[] = $this->createElement(
            'text',
            'application',
            array(
                'depends'       => 'type',
                'requires'      => 'syslog',
                'required'      => true,
                'label'         => t('Application Prefix'),
                'helptext'      => t('The name of the application by which to prefix syslog messages.'),
                'value'         => 'icingaweb',
                'validators'    => array(
                    array(
                        'Regex',
                        false,
                        array(
                            'pattern'  => '/^[^\W]+$/',
                            'messages' => array(
                                'regexNotMatch' => 'The application prefix cannot contain any whitespaces.'
                            )
                        )
                    )
                )
            )
        );
        $elements[] = $this->createElement(
            'select',
            'facility',
            array(
                'depends'       => 'type',
                'requires'      => 'syslog',
                'required'      => true,
                'label'         => t('Facility'),
                'helptext'      => t('The Syslog facility to utilize.'),
                'value'         => 'LOG_USER',
                'multiOptions'  => array(
                    'LOG_USER'  => 'LOG_USER'
                )
            )
        );
        $elements[] = $this->createElement(
            'text',
            'target',
            array(
                'depends'       => 'type',
                'requires'      => 'file',
                'required'      => true,
                'label'         => t('Filepath'),
                'helptext'      => t('The logfile to write messages to.'),
                'value'         => $this->getDefaultLogDir(),
                'validators'    => array(new WritablePathValidator())
            )
        );

        return $elements;
    }

    /**
     * Return the current logging configuration
     *
     * @return  array
     */
    public function getConfiguration()
    {
        $loggingConfig = Icinga::app()->getConfig()->logging;
        if ($loggingConfig === null) {
            $loggingConfig = new Zend_Config(array());
        }

        $config = array();
        $config['enable'] = $loggingConfig->enable ? 1 : 0;
        $config['level'] = $loggingConfig->level;
        $config['type'] = $loggingConfig->type;

        if ($loggingConfig->type === 'file') {
            $config['target'] = $loggingConfig->target;
        } elseif ($loggingConfig->type === 'syslog') {
            $config['application'] = $loggingConfig->application;
            $config['facility'] = $loggingConfig->facility;
        }

        return $config;
    }

    /**
     * Return the default logging directory for type "file"
     *
     * @return  string
     */
    protected function getDefaultLogDir()
    {
        return realpath(Icinga::app()->getApplicationDir() . '/../var/log/icingaweb.log');
    }
}
