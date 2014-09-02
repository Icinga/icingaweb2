<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\General;

use Icinga\Application\Icinga;
use Icinga\Web\Form;
use Icinga\Web\Form\Validator\WritablePathValidator;

class LoggingConfigForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_general_logging');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $elements = array();

        $elements[] = $this->createElement(
            'select',
            'logging_level',
            array(
                'required'      => true,
                'label'         => t('Logging Level'),
                'helptext'      => t('The maximum loglevel to emit.'),
                'multiOptions'  => array(
                    0 => t('None'),
                    1 => t('Error'),
                    2 => t('Warning'),
                    3 => t('Information'),
                    4 => t('Debug')
                )
            )
        );
        $elements[] = $this->createElement(
            'select',
            'logging_type',
            array(
                'required'      => true,
                'class'         => 'autosubmit',
                'label'         => t('Logging Type'),
                'helptext'      => t('The type of logging to utilize.'),
                'multiOptions'  => array(
                    'syslog'    => 'Syslog',
                    'file'      => t('File')
                )
            )
        );

        if (false === isset($formData['logging_type']) || $formData['logging_type'] === 'syslog') {
            $elements[] = $this->createElement(
                'text',
                'logging_application',
                array(
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
                'logging_facility',
                array(
                    'required'      => true,
                    'label'         => t('Facility'),
                    'helptext'      => t('The Syslog facility to utilize.'),
                    'multiOptions'  => array(
                        'LOG_USER'  => 'LOG_USER'
                    )
                )
            );
        } elseif ($formData['logging_type'] === 'file') {
            $elements[] = $this->createElement(
                'text',
                'logging_target',
                array(
                    'required'      => true,
                    'label'         => t('Filepath'),
                    'helptext'      => t('The logfile to write messages to.'),
                    'value'         => $this->getDefaultLogDir(),
                    'validators'    => array(new WritablePathValidator())
                )
            );
        }

        return $elements;
    }

    /**
     * Return the default logging directory for type "file"
     *
     * @return  string
     */
    protected function getDefaultLogDir()
    {
        return realpath(Icinga::app()->getApplicationDir('../var/log/icingaweb.log'));
    }
}
