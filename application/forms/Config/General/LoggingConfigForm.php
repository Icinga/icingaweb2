<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Forms\Config\General;

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
                'label'         => $this->translate('Logging Type'),
                'description'   => $this->translate('The type of logging to utilize.'),
                'multiOptions'  => array(
                    'syslog'    => 'Syslog',
                    'file'      => $this->translate('File', 'app.config.logging.type'),
                    'none'      => $this->translate('None', 'app.config.logging.type')
                )
            )
        );

        if (! isset($formData['logging_log']) || $formData['logging_log'] !== 'none') {
            $this->addElement(
                'select',
                'logging_level',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Logging Level'),
                    'description'   => $this->translate('The maximum logging level to emit.'),
                    'multiOptions'  => array(
                        Logger::$levels[Logger::ERROR]   => $this->translate('Error', 'app.config.logging.level'),
                        Logger::$levels[Logger::WARNING] => $this->translate('Warning', 'app.config.logging.level'),
                        Logger::$levels[Logger::INFO]    => $this->translate('Information', 'app.config.logging.level'),
                        Logger::$levels[Logger::DEBUG]   => $this->translate('Debug', 'app.config.logging.level')
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
                    'label'         => $this->translate('Application Prefix'),
                    'description'   => $this->translate(
                        'The name of the application by which to prefix syslog messages.'
                    ),
                    'value'         => 'icingaweb2',
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
//                    'label'         => $this->translate('Facility'),
//                    'description'   => $this->translate('The syslog facility to utilize.'),
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
                    'label'         => $this->translate('File path'),
                    'description'   => $this->translate('The full path to the log file to write messages to.'),
                    'value'         => '/var/log/icingaweb2/icingaweb2.log',
                    'validators'    => array(new WritablePathValidator())
                )
            );
        }

        return $this;
    }
}
