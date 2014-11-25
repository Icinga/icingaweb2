<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Forms\Config\General;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
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
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'logging_log',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => t('Logging Type'),
                'description'   => t('The type of logging to utilize.'),
                'multiOptions'  => array(
                    'syslog'    => 'Syslog',
                    'file'      => t('File', 'app.config.logging.type'),
                    'none'      => t('None', 'app.config.logging.type')
                )
            )
        );

        if (! isset($formData['logging_log']) || $formData['logging_log'] !== 'none') {
            $this->addElement(
                'select',
                'logging_level',
                array(
                    'required'      => true,
                    'label'         => t('Logging Level'),
                    'description'   => t('The maximum logging level to emit.'),
                    'multiOptions'  => array(
                        Logger::$levels[Logger::ERROR]      => t('Error', 'app.config.logging.level'),
                        Logger::$levels[Logger::WARNING]    => t('Warning', 'app.config.logging.level'),
                        Logger::$levels[Logger::INFO]       => t('Information', 'app.config.logging.level'),
                        Logger::$levels[Logger::DEBUG]      => t('Debug', 'app.config.logging.level')
                    )
                )
            );
        }

        if (false === isset($formData['logging_log']) || $formData['logging_log'] === 'syslog') {
            $this->addElement(
                'text',
                'logging_application',
                array(
                    'required'      => true,
                    'label'         => t('Application Prefix'),
                    'description'   => t('The name of the application by which to prefix syslog messages.'),
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
            /*
             * Note(el): Since we provide only one possible value for the syslog facility, I opt against exposing
             * this configuration.
             */
//            $this->addElement(
//                'select',
//                'logging_facility',
//                array(
//                    'required'      => true,
//                    'label'         => t('Facility'),
//                    'description'   => t('The syslog facility to utilize.'),
//                    'multiOptions'  => array(
//                        'user' => 'LOG_USER'
//                    )
//                )
//            );
        } elseif (isset($formData['logging_log']) && $formData['logging_log'] === 'file') {
            $this->addElement(
                'text',
                'logging_file',
                array(
                    'required'      => true,
                    'label'         => t('File path'),
                    'description'   => t('The full path to the log file to write messages to.'),
                    'value'         => $this->getDefaultLogDir(),
                    'validators'    => array(new WritablePathValidator())
                )
            );
        }

        return $this;
    }

    /**
     * Return the default logging directory for type 'file'
     *
     * @return string
     */
    protected function getDefaultLogDir()
    {
        return realpath(Icinga::app()->getApplicationDir('../var/log/icingaweb.log'));
    }
}
