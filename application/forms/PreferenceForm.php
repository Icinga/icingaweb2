<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use DateTimeZone;
use Exception;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\StyleSheet;
use ipl\Html\HtmlElement;
use ipl\I18n\GettextTranslator;
use ipl\I18n\Locale;
use ipl\I18n\StaticTranslator;

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
        $this->setSubmitLabel($this->translate('Save to the Preferences'));
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
        $currentPreferences = $this->Auth()->getUser()->getPreferences();
        $oldTheme = $currentPreferences->getValue('icingaweb', 'theme');
        $oldMode = $currentPreferences->getValue('icingaweb', 'theme_mode');
        $oldLocale = $currentPreferences->getValue('icingaweb', 'language');
        $defaultTheme = Config::app()->get('themes', 'default', StyleSheet::DEFAULT_THEME);

        $this->preferences = new Preferences($this->store ? $this->store->load() : array());
        $webPreferences = $this->preferences->get('icingaweb', array());
        foreach ($this->getValues() as $key => $value) {
            if ($value === ''
                || $value === null
                || $value === 'autodetect'
                || ($key === 'theme' && $value === $defaultTheme)
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

        if ((($theme = $this->getElement('theme')) !== null
            && ($theme = $theme->getValue()) !== $oldTheme
            && ($theme !== $defaultTheme || $oldTheme !== null))
            || (($mode = $this->getElement('theme_mode')) !== null
            && ($mode->getValue()) !== $oldMode)
        ) {
            $this->getResponse()->setReloadCss(true);
        }

        if (($locale = $this->getElement('language')) !== null
            && $locale->getValue() !== 'autodetect'
            && $locale->getValue() !== $oldLocale
        ) {
            $this->getResponse()->setHeader('X-Icinga-Redirect-Http', 'yes');
        }

        try {
            if ($this->store && $this->getElement('btn_submit')->isChecked()) {
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

        if (! isset($values['auto_refresh'])) {
            $values['auto_refresh'] = '1';
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
                        'autosubmit'    => true,
                        'value'         => $this->preferences->getValue(
                            'icingaweb',
                            'theme',
                            $defaultTheme
                        )
                    )
                );
            }
        }

        if (isset($formData['theme']) && $formData['theme'] !== StyleSheet::DEFAULT_THEME) {
            $themeFile = StyleSheet::getThemeFile($formData['theme']);
            $file = $themeFile !== null ? @file_get_contents($themeFile) : false;
            if ($file && strpos($file, StyleSheet::LIGHT_MODE_IDENTIFIER) === false) {
                $disabled = ['', 'light', 'system'];
            }
        }

        $this->addElement(
            'radio',
            'theme_mode',
            [
                'class' => 'theme-mode-input',
                'label' => $this->translate('Theme Mode'),
                'multiOptions' => [
                    '' => HtmlElement::create(
                        'img',
                        ['src' => $this->getView()->href('img/theme-mode-thumbnail-dark.svg')]
                    ) . HtmlElement::create('span', [], $this->translate('Dark')),
                    'light' => HtmlElement::create(
                        'img',
                        ['src' => $this->getView()->href('img/theme-mode-thumbnail-light.svg')]
                    ) . HtmlElement::create('span', [], $this->translate('Light')),
                    'system' => HtmlElement::create(
                        'img',
                        ['src' => $this->getView()->href('img/theme-mode-thumbnail-system.svg')]
                    ) . HtmlElement::create('span', [], $this->translate('System'))
                ],
                'value' => isset($value) ? $value : '',
                'disable' => isset($disabled) ? $disabled : [],
                'escape' => false,
                'decorators' => array_merge(
                    array_slice(self::$defaultElementDecorators, 0, -1),
                    [['HtmlTag', ['tag' => 'div', 'class' => 'control-group theme-mode']]]
                )
            ]
        );

        /** @var GettextTranslator $translator */
        $translator = StaticTranslator::$instance;

        $languages = array();
        $availableLocales = $translator->listLocales();

        $locale = $this->getLocale($availableLocales);
        if ($locale !== null) {
            $languages['autodetect'] = sprintf($this->translate('Browser (%s)', 'preferences.form'), $locale);
        }

        $availableLocales[] = $translator->getDefaultLocale();
        foreach ($availableLocales as $language) {
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

        $this->addElement(
            'select',
            'show_application_state_messages',
            array(
                'required'      => true,
                'label'         => $this->translate('Show application state messages'),
                'description'   => $this->translate('Whether to show application state messages.'),
                'multiOptions'  => [
                    'system' => (bool) Config::app()->get('global', 'show_application_state_messages', true)
                        ? $this->translate('System (Yes)')
                        : $this->translate('System (No)'),
                    1        => $this->translate('Yes'),
                    0        => $this->translate('No')],
                'value'         => 'system'
            )
        );

        if (Auth::getInstance()->hasPermission('user/application/stacktraces')) {
            $this->addElement(
                'checkbox',
                'show_stacktraces',
                array(
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
                'label'     => $this->translate('Use benchmark')
            )
        );

        $this->addElement(
            'checkbox',
            'auto_refresh',
            array(
                'required'      => false,
                'autosubmit'    => true,
                'label'         => $this->translate('Enable auto refresh'),
                'description'   => $this->translate(
                    'This option allows you to enable or to disable the global page content auto refresh'
                ),
                'value'         => 1
            )
        );

        if (isset($formData['auto_refresh']) && $formData['auto_refresh']) {
            $this->addElement(
                'select',
                'auto_refresh_speed',
                [
                    'required'      => false,
                    'label'         => $this->translate('Auto refresh speed'),
                    'description'   => $this->translate(
                        'This option allows you to speed up or to slow down the global page content auto refresh'
                    ),
                    'multiOptions'  => [
                        '0.5'   => $this->translate('Fast', 'refresh_speed'),
                        ''      => $this->translate('Default', 'refresh_speed'),
                        '2'     => $this->translate('Moderate', 'refresh_speed'),
                        '4'     => $this->translate('Slow', 'refresh_speed')
                    ],
                    'value'         => ''
                ]
            );
        }

        $this->addElement(
            'number',
            'default_page_size',
            array(
                'label'         => $this->translate('Default page size'),
                'description'   => $this->translate('Default number of items per page for list views'),
                'placeholder'   => 25,
                'min'           => 25,
                'step'          => 1
            )
        );

        if ($this->store) {
            $this->addElement(
                'submit',
                'btn_submit',
                array(
                    'ignore'        => true,
                    'label'         => $this->translate('Save to the Preferences'),
                    'decorators'    => array('ViewHelper'),
                    'class'         => 'btn-primary'
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
            array('btn_submit', 'btn_submit_session', 'preferences-progress'),
            'submit_buttons',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'control-group form-controls'))
                )
            )
        );
    }

    public function addSubmitButton()
    {
        return $this;
    }

    public function isSubmitted()
    {
        if (parent::isSubmitted()) {
            return true;
        }

        return $this->getElement('btn_submit_session')->isChecked();
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
     * @return string|null
     */
    protected function getLocale($availableLocales)
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])
            ? (new Locale())->getPreferred($_SERVER['HTTP_ACCEPT_LANGUAGE'], $availableLocales)
            : null;
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
