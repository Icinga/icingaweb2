<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use DateTimeZone;
use Zend_Config;
use Icinga\Web\Form;
use Icinga\Util\Translator;
use Icinga\Application\Icinga;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form\Validator\WritablePathValidator;

/**
 * Configuration form for general, application-wide settings
 */
class GeneralForm extends Form
{
    /**
     * Initialize this configuration form
     */
    public function init()
    {
        $this->setName('form_config_general');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $elements = array(
            $this->getLanguageSelection(),
            $this->getTimezoneSelection(),
            $this->getModulePathInput()
        );

        return array_merge(
            $elements,
            $this->getPreferencesElements($formData),
            $this->getLoggingElements($formData)
        );
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
                'label'     => t('Save Changes')
            )
        );

        return $this;
    }

    /**
     * Populate this form with the given configuration
     *
     * @param   Zend_Config     $config     The configuration to populate this form with
     *
     * @return  self
     */
    public function setConfiguration(Zend_Config $config)
    {
        $defaults = array();
        foreach ($config as $section => $properties) {
            foreach ($properties as $name => $value) {
                $defaults[$section . '_' . $name] = $value;
            }
        }

        $this->populate($defaults);
        return $this;
    }

    /**
     * Return the configured configuration values
     *
     * @return  Zend_Config
     */
    public function getConfiguration()
    {
        $config = array();
        $values = $this->getValues();
        foreach ($values as $sectionAndPropertyName => $value) {
            list($section, $property) = explode('_', $sectionAndPropertyName);
            $config[$section][$property] = $value;
        }

        return new Zend_Config($config);
    }

    /**
     * Return a select field for setting the default language
     *
     * Possible values are determined by Translator::getAvailableLocaleCodes.
     *
     * @return  Zend_Form_Element
     */
    protected function getLanguageSelection()
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }

        return $this->createElement(
            'select',
            'global_language',
            array(
                'label'         => t('Default Language'),
                'required'      => true,
                'multiOptions'  => $languages,
                'helptext'      => t(
                    'Select the language to use by default. Can be overwritten by a user in his preferences.'
                )
            )
        );
    }

    /**
     * Return a select field for setting the default timezone
     *
     * Possible values are determined by DateTimeZone::listIdentifiers.
     *
     * @return  Zend_Form_Element
     */
    protected function getTimezoneSelection()
    {
        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'select',
            'global_timezone',
            array(
                'label'         => t('Default Application Timezone'),
                'required'      => true,
                'multiOptions'  => $tzList,
                'helptext'      => t(
                    'Select the timezone to be used as the default. User\'s can set their own timezone if'
                    . ' they like to, but this is the timezone to be used as the default setting .'
                ),
                'value'         => date_default_timezone_get()
            )
        );
    }

    /**
     * Return a input field for setting the module path
     */
    protected function getModulePathInput()
    {
        $this->addElement(
            'text',
            'global_modulePath',
            array(
                'label'     => t('Module Path'),
                'required'  => true,
                'helptext'  => t(
                    'Contains the directories that will be searched for available modules, separated by '
                    . 'colons. Modules that don\'t exist in these directories can still be symlinked in '
                    . 'the module folder, but won\'t show up in the list of disabled modules.'
                ),
                'value'     => realpath(ICINGAWEB_APPDIR . '/../modules')
            )
        );
    }

    /**
     * Return form elements for setting the user preference storage backend
     *
     * @param   array   $formData   The data sent by the user
     */
    protected function getPreferencesElements(array $formData)
    {
        $elements = array(
            $this->createElement(
                'select',
                'preferences_type',
                array(
                    'required'      => true,
                    'class'         => 'autosubmit',
                    'label'         => t('User Preference Storage Type'),
                    'multiOptions'  => array(
                        'ini'   => t('File System (INI Files)'),
                        'db'    => t('Database'),
                        'null'  => t('Don\'t Store Preferences')
                    )
                )
            )
        );

        if (isset($formData['preferences_type']) && $formData['preferences_type'] === 'db') {
            $backends = array();
            foreach (ResourceFactory::getResourceConfigs()->toArray() as $name => $resource) {
                if ($resource['type'] === 'db') {
                    $backends[$name] = $name;
                }
            }

            $elements[] = $this->createElement(
                'select',
                'preferences_resource',
                array(
                    'required'      => true,
                    'multiOptions'  => $backends,
                    'label'         => t('Database Connection')
                )
            );
        }

        return $elements;
    }

    /**
     * Return form elements to setup the application's logging
     *
     * @param   array   $formData   The data sent by the user
     *
     * @return  array
     */
    protected function getLoggingElements(array $formData)
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
