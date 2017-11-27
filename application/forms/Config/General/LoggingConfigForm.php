<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\General;

use Icinga\Application\Logger;
use Icinga\Application\Logger\Writer\SyslogWriter;
use Icinga\Application\Platform;
use Icinga\Web\Form;

/**
 * Configuration form for logging options
 *
 * This form is not used directly but as subform for the {@link GeneralConfigForm}.
 */
class LoggingConfigForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_general_logging');
    }

    /**
     * {@inheritdoc}
     *
     * @return  $this
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
                    'php'       => $this->translate('Webserver Log', 'app.config.logging.type'),
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

        if (! isset($formData['logging_log']) || in_array($formData['logging_log'], array('syslog', 'php'))) {
            $this->addElement(
                'text',
                'logging_application',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Application Prefix'),
                    'description'   => $this->translate(
                        'The name of the application by which to prefix log messages.'
                    ),
                    'requirement'   => $this->translate('The application prefix must not contain whitespace.'),
                    'value'         => 'icingaweb2',
                    'validators'    => array(
                        array(
                            'Regex',
                            false,
                            array(
                                'pattern'  => '/^\S+$/',
                                'messages' => array(
                                    'regexNotMatch' => $this->translate(
                                        'The application prefix must not contain whitespace.'
                                    )
                                )
                            )
                        )
                    )
                )
            );

            if (isset($formData['logging_log']) && $formData['logging_log'] === 'syslog') {
                if (Platform::isWindows()) {
                    /* @see https://secure.php.net/manual/en/function.openlog.php */
                    $this->addElement(
                        'hidden',
                        'logging_facility',
                        array(
                            'value' => 'user',
                            'disabled' => true
                        )
                    );
                } else {
                    $facilities = array_keys(SyslogWriter::$facilities);
                    $this->addElement(
                        'select',
                        'logging_facility',
                        array(
                            'required' => true,
                            'label' => $this->translate('Facility'),
                            'description' => $this->translate('The syslog facility to utilize.'),
                            'value' => 'user',
                            'multiOptions' => array_combine($facilities, $facilities)
                        )
                    );
                }
            }
        } elseif (isset($formData['logging_log']) && $formData['logging_log'] === 'file') {
            $this->addElement(
                'text',
                'logging_file',
                array(
                    'required'      => true,
                    'label'         => $this->translate('File path'),
                    'description'   => $this->translate('The full path to the log file to write messages to.'),
                    'value'         => '/var/log/icingaweb2/icingaweb2.log',
                    'validators'    => array('WritablePathValidator')
                )
            );
        }

        return $this;
    }
}
