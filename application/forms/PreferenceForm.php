<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use DateTimeZone;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\TimezoneDetect;
use Icinga\Util\Translator;
use Icinga\Web\Cookie;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\StyleSheet;

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

        $oldTheme = $this->preferences->getValue('icingaweb', 'theme');

        $webPreferences = $this->preferences->get('icingaweb', array());
        foreach ($this->getValues() as $key => $value) {
            if ($value === ''
                || $value === 'autodetect'
                || ($key === 'theme' && $value === Config::app()->get('themes', 'default', StyleSheet::DEFAULT_THEME))
            ) {
                if (isset($webPreferences[$key])) {
                    unset($webPreferences[$key]);
                }
            } else {
                $webPreferences[$key] = $value;
            }
        }
        $this->preferences->icingaweb = $webPreferences;

        Session::getSession()->user->setPreferences($this->preferences);

        if (($theme = $this->getElement('theme')) !== null
            && ($theme = $theme->getValue()) !== $oldTheme
        ) {
            $this->getResponse()->setReloadCss(true);
        }

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
        $auth = Auth::getInstance();
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
        if (setlocale(LC_ALL, 0) === 'C') {
            $this->warning(
                $this->translate(
                    'Your language setting is not applied because your platform is missing the corresponding locale.'
                    . ' Make sure to install the correct language pack and restart your web server afterwards.'
                ),
                false
            );
        }

        if (! (bool) Config::app()->get('themes', 'disabled', false)) {
            $themes = Icinga::app()->getThemes();
            if (count($themes) > 1) {
                $defaultTheme = Config::app()->get('themes', 'default', StyleSheet::DEFAULT_THEME);
                if (isset($themes[$defaultTheme])) {
                    $themes[$defaultTheme] .= ' (' . $this->translate('default') . ')';
                }
                $this->addElement(
                    'select',
                    'theme',
                    array(
                        'label'         => $this->translate('Theme', 'Form element label'),
                        'multiOptions'  => $themes,
                        'value'         => $this->preferences->getValue(
                            'icingaweb',
                            'theme',
                            $defaultTheme
                        )
                    )
                );
            }
        }

        $languages = array();
        $languages['autodetect'] = sprintf($this->translate('Browser (%s)', 'preferences.form'), $this->getLocale());
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }

        $tzList = array();
        $tzList['autodetect'] = sprintf(
            $this->translate('Browser (%s)', 'preferences.form'),
            $this->getDefaultTimezone()
        );
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

        if (Auth::getInstance()->hasPermission('application/stacktraces')) {
            $this->addElement(
                'checkbox',
                'show_stacktraces',
                array(
                    'required'      => true,
                    'value'         => $this->getDefaultShowStacktraces(),
                    'label'         => $this->translate('Show Stacktraces'),
                    'description'   => $this->translate('Set whether to show an exception\'s stacktrace.')
                )
            );
        }

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
                'description'   => $this->translate(
                    'This option allows you to enable or to disable the global page content auto refresh'
                ),
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
                    'decorators'    => array('ViewHelper')
                )
            );
        }

        $this->addElement(
            'submit',
            'btn_submit_session',
            array(
                'ignore'        => true,
                'label'         => $this->translate('Save for the current Session'),
                'decorators'    => array('ViewHelper')
            )
        );

        $this->setAttrib('data-progress-element', 'preferences-progress');
        $this->addElement(
            'note',
            'preferences-progress',
            array(
                'decorators'    => array(
                    'ViewHelper',
                    array('Spinner', array('id' => 'preferences-progress'))
                )
            )
        );

        $this->addDisplayGroup(
            array('btn_submit_preferences', 'btn_submit_session', 'preferences-progress'),
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

    /**
     * Return the default global setting for show_stacktraces
     *
     * @return  bool
     */
    protected function getDefaultShowStacktraces()
    {
        return Config::app()->get('global', 'show_stacktraces', true);
    }
}
