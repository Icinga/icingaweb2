<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

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
     *
     * Sets actually only the name.
     */
    public function init()
    {
        $this->setName('form_config_logging');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $elements = array();

        $elements[] = $this->createElement(
            'checkbox',
            'enable',
            array(
                'required'  => true,
                'label'     => t('Logging Enabled'),
                'helptext'  => t('Check this to enable logging.'),
                'value'     => isset($formData['enable']) ? $formData['enable'] : 0
            )
        );
        $elements[] = $this->createElement(
            'select',
            'level',
            array(
                'required'      => true,
                'label'         => t('Logging Level'),
                'helptext'      => t('The maximum loglevel to emit.'),
                'value'         => isset($formData['level']) ? $formData['level'] : 0,
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
                'value'         => isset($formData['type']) ? $formData['type'] : 'syslog',
                'multiOptions'  => array(
                    'file'      => t('File'),
                    'syslog'    => 'Syslog'
                )
            )
        );

        if (false === isset($formData['type']) || $formData['type'] === 'syslog') {
            $elements[] = $this->createElement(
                'text',
                'application',
                array(
                    'required'      => true,
                    'label'         => t('Application Prefix'),
                    'helptext'      => t('The name of the application by which to prefix syslog messages.'),
                    'value'         => isset($formData['application']) ? $formData['application'] : 'icingaweb',
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
                    'required'      => true,
                    'label'         => t('Facility'),
                    'helptext'      => t('The Syslog facility to utilize.'),
                    'value'         => isset($formData['facility']) ? $formData['facility'] : 'LOG_USER',
                    'multiOptions'  => array(
                        'LOG_USER'  => 'LOG_USER'
                    )
                )
            );
        } elseif ($formData['type'] === 'file') {
            $elements[] = $this->createElement(
                'text',
                'target',
                array(
                    'required'      => true,
                    'label'         => t('Filepath'),
                    'helptext'      => t('The logfile to write messages to.'),
                    'value'         => isset($formData['target']) ? $formData['target'] : $this->getDefaultLogDir(),
                    'validators'    => array(new WritablePathValidator())
                )
            );
        }

        return $elements;
    }

    /**
     * @see Form::addSubmitButton()
     */
    public function addSubmitButton()
    {
        $this->addElement(
            'submit',
            'btn_submit',
            array(
                'ignore'    => true,
                'label'     => t('Save')
            )
        );

        return $this;
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
