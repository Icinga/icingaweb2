<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use DateTimeZone;
use Icinga\Application\Logger;
use Icinga\Authentication\Manager;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\TimezoneDetect;
use Icinga\Util\Translator;
use Icinga\Web\Form;
use Icinga\Web\Notification;
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
        $this->setTitle($this->translate('Preferences'));
    }

    /**
     * Set preferences to work with
     *
     * @param   Preferences     $preferences    The preferences to work with
     *
     * @return  $this
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
     * @return  $this
     */
    public function setStore(PreferencesStore $store)
    {
        $this->store = $store;
        return $this;
    }

    /**
     * Persist preferences
     *
     * @return  $this
     */
    public function save()
    {
        $this->store->save($this->preferences);
        return $this;
    }

    /**
     * Adjust preferences and persist them
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        $this->preferences = new Preferences($this->store ? $this->store->load() : array());

        $webPreferences = $this->preferences->get('icingaweb', array());
        foreach ($this->getValues() as $key => $value) {
            if ($value === null || $value === 'autodetect') {
                if (isset($webPreferences[$key])) {
                    unset($webPreferences[$key]);
                }
            } else {
                $webPreferences[$key] = $value;
            }
        }
        $this->preferences->icingaweb = $webPreferences;

        Session::getSession()->user->setPreferences($this->preferences);

        try {
            if ($this->store && $this->getElement('btn_submit_preferences')->isChecked()) {
                $this->save();
                Notification::success($this->translate('Preferences successfully saved'));
            } else {
                Notification::success($this->translate('Preferences successfully saved for the current session'));
            }
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
    public function onRequest()
    {
        $auth = Manager::getInstance();
        $values = $auth->getUser()->getPreferences()->get('icingaweb');

        if (! isset($values['language'])) {
            $values['language'] = 'autodetect';
        }

        if (! isset($values['timezone'])) {
            $values['timezone'] = 'autodetect';
        }

        $this->populate($values);
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $languages = array();
        $languages['autodetect'] = sprintf($this->translate('Browser (%s)', 'preferences.form'), $this->getLocale());
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }

        $tzList = array();
        $tzList['autodetect'] = sprintf($this->translate('Browser (%s)', 'preferences.form'), $this->getDefaultTimezone());
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'select',
            'language',
            array(
                'required'      => true,
                'label'         => $this->translate('Your Current Language'),
                'description'   => $this->translate('Use the following language to display texts and messages'),
                'multiOptions'  => $languages,
                'value'         => substr(setlocale(LC_ALL, 0), 0, 5)
            )
        );

        $this->addElement(
            'select',
            'timezone',
            array(
                'required'      => true,
                'label'         => $this->translate('Your Current Timezone'),
                'description'   => $this->translate('Use the following timezone for dates and times'),
                'multiOptions'  => $tzList,
                'value'         => $this->getDefaultTimezone()
            )
        );

        $this->addElement(
            'checkbox',
            'show_benchmark',
            array(
                'required'  => true,
                'label'     => $this->translate('Use benchmark')
            )
        );

        $this->addElement(
            'checkbox',
            'auto_refresh',
            array(
                'required'      => false,
                'label'         => $this->translate('Enable auto refresh'),
                'description'   => $this->translate('This option allows you to enable or to disable the global page content auto refresh'),
                'value'         => 1
            )
        );

        if ($this->store) {
            $this->addElement(
                'submit',
                'btn_submit_preferences',
                array(
                    'ignore'        => true,
                    'label'         => $this->translate('Save to the Preferences'),
                    'decorators'    => array(
                        'ViewHelper',
                        array('HtmlTag', array('tag' => 'div'))
                    )
                )
            );
        }

        $this->addElement(
            'submit',
            'btn_submit_session',
            array(
                'ignore'        => true,
                'label'         => $this->translate('Save for the current Session'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'div'))
                )
            )
        );

        $this->addDisplayGroup(
            array('btn_submit_preferences', 'btn_submit_session'),
            'submit_buttons',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group'))
                )
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
            return @date_default_timezone_get();
        }
    }

    /**
     * Return the preferred locale based on the given HTTP header and the available translations
     *
     * @return string
     */
    protected function getLocale()
    {
        $locale = Translator::getPreferredLocaleCode($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        return $locale;
    }
}
