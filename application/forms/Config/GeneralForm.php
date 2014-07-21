<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use DateTimeZone;
use Zend_Config;
use Icinga\Web\Form;
use Icinga\Util\Translator;
use Icinga\Data\ResourceFactory;

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
            $this->getLanguageSelection($formData),
            $this->getTimezoneSelection($formData),
            $this->getModulePathInput($formData)
        );

        return array_merge($elements, $this->getPreferencesElements($formData));
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
                'label' => t('Save Changes')
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
        $defaults = $config->get('global', new Zend_Config(array()))->toArray();
        if ($config->get('preferences', new Zend_Config(array()))->type !== null) {
            $defaults['preferences_type'] = $config->get('preferences')->type;
            $defaults['preferences_resource'] = $config->get('preferences')->resource;
        }

        $this->setDefaults($defaults);
        return $this;
    }

    /**
     * Return the configured configuration values
     *
     * @return  Zend_Config
     */
    public function getConfiguration()
    {
        $values = $this->getValues();
        $globalData = array(
            'language'   => $values['language'],
            'timezone'   => $values['timezone'],
            'modulePath' => $values['modulePath']
        );

        $preferencesData = array('type' => $values['preferences_type']);
        if ($values['preferences_type'] === 'db') {
            $preferencesData['resource'] = $values['preferences_resource'];
        }

        return new Zend_Config(array('global' => $globalData, 'preferences' => $preferencesData));
    }

    /**
     * Return a select field for setting the default language
     *
     * Possible values are determined by Translator::getAvailableLocaleCodes.
     *
     * @param   array   $formData   The data to populate the elements with
     *
     * @return  Zend_Form_Element
     */
    protected function getLanguageSelection(array $formData)
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }
        $languages[Translator::DEFAULT_LOCALE] = Translator::DEFAULT_LOCALE;

        return $this->createElement(
            'select',
            'language',
            array(
                'label'         => t('Default Language'),
                'required'      => true,
                'multiOptions'  => $languages,
                'helptext'      => t(
                    'Select the language to use by default. Can be overwritten by a user in his preferences.'
                ),
                'value'         => isset($formData['language']) ? $formData['language'] : Translator::DEFAULT_LOCALE
            )
        );
    }

    /**
     * Return a select field for setting the default timezone
     *
     * Possible values are determined by DateTimeZone::listIdentifiers.
     *
     * @param   array   $formData   The data to populate the elements with
     *
     * @return  Zend_Form_Element
     */
    protected function getTimezoneSelection(array $formData)
    {
        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'select',
            'timezone',
            array(
                'label'         => t('Default Application Timezone'),
                'required'      => true,
                'multiOptions'  => $tzList,
                'helptext'      => t(
                    'Select the timezone to be used as the default. User\'s can set their own timezone if'
                    . ' they like to, but this is the timezone to be used as the default setting .'
                ),
                'value'         => isset($formData['timezone']) ? $formData['timezone'] : date_default_timezone_get()
            )
        );
    }

    /**
     * Return a input field for setting the module path
     *
     * @param   array   $formData   The data to populate the elements with
     */
    protected function getModulePathInput(array $formData)
    {
        $this->addElement(
            'text',
            'modulePath',
            array(
                'label'     => t('Module Path'),
                'required'  => true,
                'helptext'  => t(
                    'Contains the directories that will be searched for available modules, separated by '
                    . 'colons. Modules that don\'t exist in these directories can still be symlinked in '
                    . 'the module folder, but won\'t show up in the list of disabled modules.'
                ),
                'value'     => isset($formData['modulePath'])
                    ? $formData['modulePath']
                    : realpath(ICINGAWEB_APPDIR . '/../modules')
            )
        );
    }

    /**
     * Return form elements for setting the user preference storage backend
     *
     * @param   array   $formData   The data to populate the elements with
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
                    'value'         => isset($formData['preferences_type']) ? $formData['preferences_type'] : 'ini',
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
                    'label'         => t('Database Connection'),
                    'value'         => isset($formData['preferences_resource'])
                        ? $formData['preferences_resource']
                        : null
                )
            );
        }

        return $elements;
    }
}
