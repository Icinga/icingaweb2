<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form;

use Exception;
use DateTimeZone;
use Icinga\Application\Logger;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\TimezoneDetect;
use Icinga\Util\Translator;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Request;
use Icinga\Web\Session;

/**
 * Form class to adjust user preferences
 */
class PreferenceForm extends Form
{
    /**
     * The preferences to work with
     *
     * @var Preferences
     */
    protected $preferences;

    /**
     * The preference store to use
     *
     * @var PreferencesStore
     */
    protected $store;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_preferences');
        $this->setSubmitLabel(t('Save Changes'));
    }

    /**
     * Set preferences to work with
     *
     * @param   Preferences     $preferences    The preferences to work with
     *
     * @return  self
     */
    public function setPreferences(Preferences $preferences)
    {
        $this->preferences = $preferences;
        return $this;
    }

    /**
     * Set the preference store to use
     *
     * @param   PreferencesStore    $store      The preference store to use
     *
     * @return  self
     */
    public function setStore(PreferencesStore $store)
    {
        $this->store = $store;
    }

    /**
     * Persist preferences
     *
     * @return  self
     */
    public function save()
    {
        $this->store->load(); // Necessary for patching existing preferences
        $this->store->save($this->preferences);
        return $this;
    }

    /**
     * Adjust preferences and persist them
     *
     * @see Form::onSuccess()
     */
    public function onSuccess(Request $request)
    {
        $webPreferences = $this->preferences->get('icingaweb', array());
        foreach ($this->getValues() as $key => $value) {
            if ($value === null) {
                if (isset($webPreferences[$key])) {
                    unset($webPreferences[$key]);
                }
            } else {
                $webPreferences[$key] = $value;
            }
        }
        $this->preferences->icingaweb = $webPreferences;

        // TODO: Is this even necessary in case the session is written on response?
        $session = Session::getSession();
        $session->user->setPreferences($this->preferences);
        $session->write();

        try {
            $this->save();
            Notification::success(t('Preferences successfully saved'));
        } catch (Exception $e) {
            Logger::error($e);
            Notification::error($e->getMessage());
        }
    }

    /**
     * Populate preferences
     *
     * @see Form::onRequest()
     */
    public function onRequest(Request $request)
    {
        $values = $this->preferences->get('icingaweb', array());
        $values['browser_language'] = false === isset($values['language']);
        $values['local_timezone'] = false === isset($values['timezone']);
        $this->populate($values);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }

        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'checkbox',
            'browser_language',
            array(
                'ignore'        => true,
                'required'      => true,
                'autosubmit'    => true,
                'value'         => true,
                'label'         => t('Use your browser\'s language suggestions')
            )
        );

        $useBrowserLanguage = isset($formData['browser_language']) ? $formData['browser_language'] == 1 : true;
        $languageSelection = $this->createElement(
            'select',
            'language',
            array(
                'required'      => false === $useBrowserLanguage,
                'label'         => t('Your Current Language'),
                'description'   => t('Use the following language to display texts and messages'),
                'multiOptions'  => $languages,
                'value'         => substr(setlocale(LC_ALL, 0), 0, 5)
            )
        );
        if ($useBrowserLanguage) {
            $languageSelection->setAttrib('disabled', 'disabled');
        }
        $this->addElement($languageSelection);

        $this->addElement(
            'checkbox',
            'local_timezone',
            array(
                'ignore'        => true,
                'required'      => true,
                'autosubmit'    => true,
                'value'         => true,
                'label'         => t('Use your local timezone')
            )
        );

        $useLocalTimezone = isset($formData['local_timezone']) ? $formData['local_timezone'] == 1 : true;
        $timezoneSelection = $this->createElement(
            'select',
            'timezone',
            array(
                'required'      => false === $useLocalTimezone,
                'label'         => t('Your Current Timezone'),
                'description'   => t('Use the following timezone for dates and times'),
                'multiOptions'  => $tzList,
                'value'         => $this->getDefaultTimezone()
            )
        );
        if ($useLocalTimezone) {
            $timezoneSelection->setAttrib('disabled', 'disabled');
        }
        $this->addElement($timezoneSelection);

        $this->addElement(
            'checkbox',
            'show_benchmark',
            array(
                'required'  => true,
                'label'     => t('Use benchmark')
            )
        );
    }

    /**
     * Return the current default timezone
     *
     * @return  string
     */
    protected function getDefaultTimezone()
    {
        $detect = new TimezoneDetect();
        if ($detect->success()) {
            return $detect->getTimezoneName();
        } else {
            return date_default_timezone_get();
        }
    }
}
