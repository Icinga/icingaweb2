<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use \Zend_Config;
use Icinga\Web\Form;
use Icinga\Application\Icinga;
use Icinga\Web\Form\Validator\WritablePathValidator;

/**
 * Form class for setting the application wide logging configuration
 */
class LoggingForm extends Form
{
    /**
     * Return the default logging directory for type "file"
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
                'value'         => $loggingConfig->get('type', 'file'),
                'multiOptions'  => array(
                    'file'      => t('File'),
                    'syslog'    => 'Syslog'
                )
            )
        );
        $this->enableAutoSubmit(array('logging_type'));

        switch ($this->getRequest()->getParam('logging_type', $loggingConfig->get('type', 'file')))
        {
            case 'syslog':
                $this->addElement(
                    'text',
                    'logging_application',
                    array(
                        'required'  => true,
                        'label'     => t('Application Prefix'),
                        'helptext'  => t('The name of the application by which to prefix syslog messages.'),
                        'value'     => $loggingConfig->get('application', 'icingaweb'),
                        'validators' => array(
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
                $this->addElement(
                    'select',
                    'logging_facility',
                    array(
                        'required'      => true,
                        'label'         => t('Facility'),
                        'helptext'      => t('The Syslog facility to utilize.'),
                        'value'         => $loggingConfig->get('facility', 'LOG_USER'),
                        'multiOptions'  => array(
                            'LOG_USER' => 'LOG_USER'
                        )
                    )
                );
                break;
            case 'file':
            default:
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
        }

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    public function isValid($data) {
        foreach ($this->getElements() as $key => $element) {
            // Initialize all empty elements with their default values.
            if (!isset($data[$key])) {
                $data[$key] = $element->getValue();
            }
        }
        return parent::isValid($data);
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
            case 'file':
                $cfg['logging']['type'] = 'file';
                $cfg['logging']['target'] = $values['logging_target'];
                break;
            case 'syslog':
                $cfg['logging']['type'] = 'syslog';
                $cfg['logging']['application'] = $values['logging_application'];
                $cfg['logging']['facility'] = $values['logging_facility'];
                break;
        }

        return new Zend_Config($cfg);
    }
}
