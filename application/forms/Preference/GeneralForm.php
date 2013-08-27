<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
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

/**
 * General user preferences
 */
class GeneralForm extends Form
{
    /**
     * The view helper to format date/time strings
     *
     * @var Zend_View_Helper_DateFormat
     */
    private $dateHelper;

    /**
     * Return the view helper to format date/time strings
     *
     * @return Zend_View_Helper_DateFormat
     */
    public function getDateFormatter()
    {
        if ($this->dateHelper === null) {
            return $this->getView()->dateFormat();
        }
        return $this->dateHelper;
    }

    /**
     * Set the view helper that is used to format date/time strings (used for testing)
     *
     * @param Zend_View_Helper_DateFormat $dateHelper
     */
    public function setDateFormatter(Zend_View_Helper_DateFormat $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    /**
     * Add a select field for setting the user's timezone.
     *
     * Possible values are determined by DateTimeZone::listIdentifiers
     * Also, a 'use default format' checkbox is added in order to allow a user to discard his overwritten setting
     *
     * @param Zend_Config $cfg The "global" section of the config.ini to be used as default valuse
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
                'value'         => !$prefs->has('app.timezone'),
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
     * Add text fields for the date and time format used for this user
     *
     * Also, a 'use default format' checkbox is added in order to allow a user to discard his overwritten setting
     *
     * @param Zend_Config $cfg The "global" section of the config.ini to be used as default values
     */
    private function addDateFormatSettings(Zend_Config $cfg)
    {
        $prefs = $this->getUserPreferences();
        $useGlobalDateFormat = $this->getRequest()->getParam('default_date_format', !$prefs->has('app.dateFormat'));
        $useGlobalTimeFormat = $this->getRequest()->getParam('default_time_format', !$prefs->has('app.timeFormat'));

        $phpUrl = '<a href="http://php.net/manual/en/function.date.php" target="_new">'
            . 'the official PHP documentation</a>';


        $this->addElement(
            'checkbox',
            'default_date_format',
            array(
                'label'         => 'Use Default Date Format',
                'value'         => !$prefs->has('app.dateFormat'),
                'required'      => true
            )
        );
        $dateFormatValue = $this->getRequest()->getParam('date_format', '');
        if (empty($dateFormatValue)) {
            $dateFormatValue = $prefs->get('app.dateFormat', $cfg->get('dateFormat', 'd/m/Y'));
        }
        $txtDefaultDateFormat = new Zend_Form_Element_Text(
            array(
                'name'      =>  'date_format',
                'label'     =>  'Preferred Date Format',
                'helptext'  =>  'Display dates according to this format. (See ' . $phpUrl . ' for possible values.) '
                                . 'Example result: ' . $this->getDateFormatter()->format(time(), $dateFormatValue),
                'required'  =>  !$useGlobalDateFormat,
                'value'     =>  $dateFormatValue
            )
        );

        $this->addElement($txtDefaultDateFormat);
        $txtDefaultDateFormat->addValidator(new DateFormatValidator());
        if ($useGlobalDateFormat) {
            $txtDefaultDateFormat->setAttrib('disabled', '1');
        }

        $this->addElement(
            'checkbox',
            'default_time_format',
            array(
                'label'         => 'Use Default Time Format',
                'value'         => !$prefs->has('app.timeFormat'),
                'required'      => !$useGlobalTimeFormat
            )
        );
        $timeFormatValue = $this->getRequest()->getParam('time_format', '');
        if (empty($timeFormatValue)) {
            $timeFormatValue = $prefs->get('app.timeFormat', $cfg->get('timeFormat', 'g:i A'));
        }
        $txtDefaultTimeFormat = new Zend_Form_Element_Text(
            array(
                'name'      =>  'time_format',
                'label'     =>  'Preferred Time Format',
                'required'  =>  !$useGlobalTimeFormat,
                'helptext'  =>  'Display times according to this format. (See ' . $phpUrl . ' for possible values.) '
                                . 'Example result: ' . $this->getDateFormatter()->format(time(), $timeFormatValue),
                'value'     =>  $timeFormatValue
            )
        );
        $txtDefaultTimeFormat->addValidator(new TimeFormatValidator());
        $this->addElement($txtDefaultTimeFormat);
        if ($useGlobalTimeFormat) {
            $txtDefaultTimeFormat->setAttrib('disabled', '1');
        }

        $this->enableAutoSubmit(array('default_time_format', 'default_date_format'));
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

        $this->addTimezoneSelection($global);
        $this->addDateFormatSettings($global);

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
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
            'app.timezone'      => $values['timezone'],
            'app.dateFormat'    => $values['date_format'],
            'app.timeFormat'    => $values['time_format']
        );
    }
}
