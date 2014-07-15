<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Preference;

use \DateTimeZone;
use \Zend_Config;
use \Zend_Form_Element_Text;
use \Zend_Form_Element_Select;
use \Zend_View_Helper_DateFormat;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Validator\TimeFormatValidator;
use \Icinga\Web\Form\Validator\DateFormatValidator;
use \Icinga\Util\Translator;

/**
 * General user preferences
 */
class GeneralForm extends Form
{
    /**
     * Add a select field for setting the user's language
     *
     * Possible values are determined by Translator::getAvailableLocaleCodes.
     * Also, a 'use default format' checkbox is added in order to allow a user to discard his overwritten setting
     *
     * @param   Zend_Config     $cfg    The "global" section of the config.ini to be used as default value
     */
    private function addLanguageSelection(Zend_Config $cfg)
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }
        $languages[Translator::DEFAULT_LOCALE] = Translator::DEFAULT_LOCALE;
        $prefs = $this->getUserPreferences();
        $useDefaultLanguage = $this->getRequest()->getParam('default_language', !$prefs->has('app.language'));

        $this->addElement(
            'checkbox',
            'default_language',
            array(
                'label'     => t('Use Default Language'),
                'value'     => $useDefaultLanguage,
                'required'  => true
            )
        );
        $selectOptions = array(
            'label'         => t('Your Current Language'),
            'required'      => !$useDefaultLanguage,
            'multiOptions'  => $languages,
            'helptext'      => t('Use the following language to display texts and messages'),
            'value'         => $prefs->get('app.language', $cfg->get('language', Translator::DEFAULT_LOCALE))
        );
        if ($useDefaultLanguage) {
            $selectOptions['disabled'] = 'disabled';
        }
        $this->addElement('select', 'language', $selectOptions);
        $this->enableAutoSubmit(array('default_language'));
    }

    /**
     * Add a select field for setting the user's timezone.
     *
     * Possible values are determined by DateTimeZone::listIdentifiers
     * Also, a 'use default format' checkbox is added in order to allow a user to discard his overwritten setting
     *
     * @param Zend_Config $cfg The "global" section of the config.ini to be used as default value
     */
    private function addTimezoneSelection(Zend_Config $cfg)
    {
        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }
        $helptext = 'Use the following timezone for dates and times';
        $prefs = $this->getUserPreferences();
        $useGlobalTimezone = $this->getRequest()->getParam('default_timezone', !$prefs->has('app.timezone'));

        $selectTimezone = new Zend_Form_Element_Select(
            array(
                'name'          => 'timezone',
                'label'         =>  'Your Current Timezone',
                'required'      =>  !$useGlobalTimezone,
                'multiOptions'  =>  $tzList,
                'helptext'      =>  $helptext,
                'value'         =>  $prefs->get('app.timezone', $cfg->get('timezone', date_default_timezone_get()))
            )
        );
        $this->addElement(
            'checkbox',
            'default_timezone',
            array(
                'label'         => 'Use Default Timezone',
                'value'         => $useGlobalTimezone,
                'required'      => true
            )
        );
        if ($useGlobalTimezone) {
            $selectTimezone->setAttrib('disabled', 1);
        }
        $this->addElement($selectTimezone);
        $this->enableAutoSubmit(array('default_timezone'));
    }

    /**
     * Create the general form, using the global configuration as fallback values for preferences
     *
     * @see Form::create()
     */
    public function create()
    {
        $this->setName('form_preference_set');

        $config = $this->getConfiguration();
        $global = $config->global;
        if ($global === null) {
            $global = new Zend_Config(array());
        }

        $this->addLanguageSelection($global);
        $this->addTimezoneSelection($global);

        $this->setSubmitLabel('Save Changes');

        $this->addElement(
            'checkbox',
            'show_benchmark',
            array(
                'label' => 'Use benchmark',
                'value' => $this->getUserPreferences()->get('app.show_benchmark')
            )
        );
    }

    /**
     * Return an array containing the preferences set in this form
     *
     * @return array
     */
    public function getPreferences()
    {
        $values = $this->getValues();
        return array(
            'app.language'          => $values['default_language'] ? null : $values['language'],
            'app.timezone'          => $values['default_timezone'] ? null : $values['timezone'],
            'app.show_benchmark'    => $values['show_benchmark'] === '1' ? true : false
        );
    }
}
