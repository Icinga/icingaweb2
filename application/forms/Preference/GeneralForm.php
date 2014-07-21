<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Preference;

use DateTimeZone;
use Icinga\Web\Form;
use Icinga\Util\Translator;
use Icinga\User\Preferences;

/**
 * General user preferences
 */
class GeneralForm extends Form
{
    /**
     * Initialize this preferences config form
     */
    public function init()
    {
        $this->setName('form_preference_set');
    }

    /**
     * Add a select field for setting the user's language
     *
     * Possible values are determined by Translator::getAvailableLocaleCodes.
     * Also, a 'use browser language' checkbox is added in order to allow a user to discard his setting
     *
     * @param   array   $formData   The data to populate the elements with
     */
    protected function getLanguageElements(array $formData)
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }
        $languages[Translator::DEFAULT_LOCALE] = Translator::DEFAULT_LOCALE;

        $useBrowserLanguage = isset($formData['browser_language']) ? $formData['browser_language'] == 1 : true;
        $selectOptions = array(
            'label'         => t('Your Current Language'),
            'required'      => false === $useBrowserLanguage,
            'multiOptions'  => $languages,
            'helptext'      => t('Use the following language to display texts and messages'),
            'value'         => isset($formData['language'])
                ? $formData['language']
                : substr(setlocale(LC_ALL, 0), 0, 5)
        );
        if ($useBrowserLanguage) {
            $selectOptions['disabled'] = 'disabled';
        }

        return array(
            $this->createElement(
                'checkbox',
                'browser_language',
                array(
                    'required'  => true,
                    'class'     => 'autosubmit',
                    'label'     => t('Use your browser\'s language suggestions'),
                    'value'     => $useBrowserLanguage
                )
            ),
            $this->createElement('select', 'language', $selectOptions)
        );
    }

    /**
     * Add a select field for setting the user's timezone
     *
     * Possible values are determined by DateTimeZone::listIdentifiers.
     * Also, a 'use local timezone' checkbox is added in order to allow a user to discard his overwritten setting
     *
     * @param   array   $formData   The data to populate the elements with
     */
    protected function getTimezoneElements(array $formData)
    {
        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $useLocalTimezone = isset($formData['local_timezone']) ? $formData['local_timezone'] == 1 : true;
        $selectOptions = array(
            'label'         => 'Your Current Timezone',
            'required'      => false === $useLocalTimezone,
            'multiOptions'  => $tzList,
            'helptext'      => t('Use the following timezone for dates and times'),
            'value'         => isset($formData['timezone'])
                ? $formData['timezone']
                : date_default_timezone_get()
        );
        if ($useLocalTimezone) {
            $selectOptions['disabled'] = 'disabled';
        }

        return array(
            $this->createElement(
                'checkbox',
                'local_timezone',
                array(
                    'required'  => true,
                    'class'     => 'autosubmit',
                    'label'     => t('Use your local timezone'),
                    'value'     => $useLocalTimezone,
                )
            ),
            $this->createElement('select', 'timezone', $selectOptions)
        );
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $elements = array_merge($this->getLanguageElements($formData), $this->getTimezoneElements($formData));
        $elements[] = $this->createElement(
            'checkbox',
            'show_benchmark',
            array(
                'label' => t('Use benchmark'),
                'value' => isset($formData['show_benchmark']) ? $formData['show_benchmark'] : 0
            )
        );

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
                'label' => t('Save Changes')
            )
        );

        return $this;
    }

    /**
     * Populate the form with the given preferences
     *
     * @param   Preferences     $preferences    The preferences to populate the form with
     *
     * @return  self
     */
    public function setPreferences(Preferences $preferences)
    {
        $defaults = array(
            'browser_language'  => $preferences->get('app.language') === null,
            'local_timezone'    => $preferences->get('app.timezone') === null
        );

        if ($preferences->get('app.language') !== null) {
            $defaults['language'] = $preferences->get('app.language');
        }
        if ($preferences->get('app.timezone') !== null) {
            $defaults['timezone'] = $preferences->get('app.timezone');
        }
        if ($preferences->get('app.show_benchmark')) {
            $defaults['show_benchmark'] = $preferences->get('app.show_benchmark');
        }

        $this->setDefaults($defaults);
        return $this;
    }

    /**
     * Return the configured preferences
     *
     * @return  Preferences
     */
    public function getPreferences()
    {
        $values = $this->getValues();
        return new Preferences(
            array(
                'app.language'          => $values['browser_language'] ? null : $values['language'],
                'app.timezone'          => $values['local_timezone'] ? null : $values['timezone'],
                'app.show_benchmark'    => $values['show_benchmark'] ? $values['show_benchmark'] : null
            )
        );
    }
}
