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

namespace Icinga\Form\Config;

use \DateTimeZone;
use \Zend_Config;
use \Zend_Form_Element_Text;
use \Zend_Form_Element_Select;
use \Zend_View_Helper_DateFormat;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Validator\WritablePathValidator;
use \Icinga\Web\Form\Validator\TimeFormatValidator;
use \Icinga\Web\Form\Validator\DateFormatValidator;
use \Icinga\Web\Form\Decorator\ConditionalHidden;

/**
 * Configuration form for general, application-wide settings
 */
class GeneralForm extends Form
{
    /**
     * The base directory of the icingaweb configuration
     *
     * @var string
     */
    private $configDir = null;

    /**
     * The resources to use instead of the factory provided ones (use for testing)
     *
     * @var null
     */
    private $resources;

    /**
     * The view helper to format date/time strings
     *
     * @var Zend_View_Helper_DateFormat
     */
    private $dateHelper;

    /**
     * Set a specific configuration directory to use for configuration specific default paths
     *
     * @param string    $dir
     */
    public function setConfigDir($dir)
    {
        $this->configDir = $dir;
    }

    /**
     * Return the config path set for this form or the application wide config path if none is set
     *
     * @return  string
     *
     * @see     IcingaConfig::configDir
     */
    public function getConfigDir()
    {
        return $this->configDir === null ? IcingaConfig::$configDir : $this->configDir;
    }

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
     * @param Zend_View_Helper_DateFormat   $dateHelper
     */
    public function setDateFormatter(Zend_View_Helper_DateFormat $dateHelper)
    {
        $this->dateHelper = $dateHelper;
    }

    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     * (used for testing)
     *
     * @param array $resources  The resources to use for populating the db selection field
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
    }

    /**
     * Return content of the resources.ini or previously set resources for displaying in the database selection field
     *
     * @return array
     */
    public function getResources()
    {
        if ($this->resources === null) {
            return DbAdapterFactory::getResources();
        } else {
            return $this->resources;
        }
    }

    /**
     * Add the checkbox for using the development environment to this form
     *
     * @param Zend_Config   $cfg    The "global" section of the config.ini
     */
    private function addDevelopmentCheckbox(Zend_Config $cfg)
    {
        $env = $cfg->get('environment', 'development');
        $this->addElement(
            'checkbox',
            'environment',
            array(
                'label'     => 'Development Mode',
                'required'  => true,
                'helptext'  => 'Set true to show more detailed errors and disable certain optimizations in order to '
                    . 'make debugging easier.',
                'tooltip'   => 'More verbose output',
                'value'     => $env === 'development'
            )
        );

    }

    /**
     * Add a select field for setting the default timezone.
     *
     * Possible values are determined by DateTimeZone::listIdentifiers
     *
     * @param Zend_Config   $cfg    The "global" section of the config.ini
     */
    private function addTimezoneSelection(Zend_Config $cfg)
    {
        $tzList = array();
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }
        $helptext = 'Select the timezone to be used as the default. User\'s can set their own timezone if'
            . ' they like to, but this is the timezone to be used as the default setting .';

        $this->addElement(
            'select',
            'timezone',
            array(
                'label'         =>  'Default Application Timezone',
                'required'      =>  true,
                'multiOptions'  =>  $tzList,
                'helptext'      =>  $helptext,
                'value'         =>  $cfg->get('timezone', date_default_timezone_get())
            )
        );
    }

    /**
     * Add configuration settings for module paths
     *
     * @param Zend_Config   $cfg    The "global" section of the config.ini
     */
    private function addModuleSettings(Zend_Config $cfg)
    {
        $this->addElement(
            'text',
            'module_folder',
            array(
                'label'     => 'Module Folder',
                'required'  => true,
                'helptext'  => 'The moduleFolder directive is currently not used anywhere but '
                    . 'configureable via the frontend and ini. With feature #4607 moduleFolder '
                    . 'will be replaced with a configuration directive for locations of '
                    . 'installed modules',
                'value'     => $cfg->get('moduleFolder', $this->getConfigDir() . '/config/enabledModules')
            )
        );
    }

    /**
     * Add text fields for the date and time format used in the application
     *
     * @param Zend_Config   $cfg    The "global" section of the config.ini
     */
    private function addDateFormatSettings(Zend_Config $cfg)
    {
        $phpUrl = '<a href="http://php.net/manual/en/function.date.php" target="_new">'
            . 'the official PHP documentation</a>';

        $dateFormatValue = $this->getRequest()->getParam('date_format', '');
        if (empty($dateFormatValue)) {
            $dateFormatValue = $cfg->get('dateFormat', 'd/m/Y');
        }
        $txtDefaultDateFormat = new Zend_Form_Element_Text(
            array(
                'name'      =>  'date_format',
                'label'     =>  'Date Format',
                'helptext'  =>  'Display dates according to this format. (See ' . $phpUrl . ' for possible values.) '
                                . 'Example result: ' . $this->getDateFormatter()->format(time(), $dateFormatValue),
                'required'  =>  true,
                'value'     =>  $dateFormatValue
            )
        );
        $this->addElement($txtDefaultDateFormat);
        $txtDefaultDateFormat->addValidator(new DateFormatValidator());

        $timeFormatValue = $this->getRequest()->getParam('time_format', '');
        if (empty($timeFormatValue)) {
            $timeFormatValue = $cfg->get('timeFormat', 'g:i A');
        }
        $txtDefaultTimeFormat = new Zend_Form_Element_Text(
            array(
                'name'      =>  'time_format',
                'label'     =>  'Time Format',
                'required'  =>  true,
                'helptext'  =>  'Display times according to this format. (See ' . $phpUrl . ' for possible values.) '
                                . 'Example result: ' . $this->getDateFormatter()->format(time(), $timeFormatValue),
                'value'     =>  $timeFormatValue
            )
        );
        $txtDefaultTimeFormat->addValidator(new TimeFormatValidator());
        $this->addElement($txtDefaultTimeFormat);
    }

    /**
     * Add form elements for setting the user preference storage backend
     *
     * @param Zend_Config   $cfg    The Zend_config object of preference section
     */
    public function addUserPreferencesDialog(Zend_Config $cfg)
    {
        $backend = $cfg->get('type', 'ini');
        if ($this->getRequest()->get('preferences_type', null) !== null) {
            $backend = $this->getRequest()->get('preferences_type');
        }
        $this->addElement(
            'select',
            'preferences_type',
            array(
                'label'         => 'User Preference Storage Type',
                'required'      => true,
                'value'         => $backend,
                'multiOptions'  => array(
                    'ini'   => 'File System (ini files)',
                    'db'    => 'Database'
                )
            )
        );

        $txtPreferencesIniPath = new Zend_Form_Element_Text(
            array(
                'name'      =>  'preferences_ini_path',
                'label'     =>  'User Preference Filepath',
                'required'  =>  $backend === 'ini',
                'condition' =>  $backend === 'ini',
                'value'     =>  $cfg->get('configPath')
            )
        );

        $backends = array();
        foreach ($this->getResources() as $name => $resource) {
            if ($resource['type'] !== 'db') {
                continue;
            }
            $backends[$name] = $name;
        }

        $txtPreferencesDbResource = new Zend_Form_Element_Select(
            array(
                'name'          =>  'preferences_db_resource',
                'label'         =>  'Database Connection',
                'required'      =>  $backend === 'db',
                'condition'     =>  $backend === 'db',
                'value'         =>  $cfg->get('resource'),
                'multiOptions'  =>  $backends
            )
        );
        $validator = new WritablePathValidator();
        $validator->setRequireExistence();
        $txtPreferencesIniPath->addValidator($validator);
        $this->addElement($txtPreferencesIniPath);
        $this->addElement($txtPreferencesDbResource);

        $txtPreferencesIniPath->addDecorator(new ConditionalHidden());
        $txtPreferencesDbResource->addDecorator(new ConditionalHidden());
        $this->enableAutoSubmit(
            array(
                'preferences_type'
            )
        );
    }

    /**
     * Create the general form, using the provided configuration
     *
     * @see Form::create()
     */
    public function create()
    {
        $config = $this->getConfiguration();
        $global = $config->global;
        if ($global === null) {
            $global = new Zend_Config(array());
        }
        $preferences = $config->preferences;
        if ($preferences === null) {
            $preferences = new Zend_Config(array());
        }
        $this->setName('form_config_general');
        $this->addDevelopmentCheckbox($global);
        $this->addTimezoneSelection($global);
        $this->addModuleSettings($global);
        $this->addDateFormatSettings($global);
        $this->addUserPreferencesDialog($preferences);

        $this->setSubmitLabel('{{SAVE_ICON}} Save Changes');
    }

    /**
     * Return an Zend_Config object containing the configuration set in this form
     *
     * @return Zend_Config
     */
    public function getConfig()
    {
        $config = $this->getConfiguration();
        if ($config->global === null) {
            $config->global = new Zend_Config(array());
        }
        if ($config->preferences === null) {
            $config->preferences = new Zend_Config(array());
        }

        $values = $this->getValues();
        $cfg = clone $config;
        $cfg->global->environment  = ($values['environment'] == 1) ? 'development' : 'production';
        $cfg->global->timezone     = $values['timezone'];
        $cfg->global->moduleFolder = $values['module_folder'];
        $cfg->global->dateFormat   = $values['date_format'];
        $cfg->global->timeFormat   = $values['time_format'];

        $cfg->preferences->type = $values['preferences_type'];
        if ($cfg->preferences->type === 'ini') {
            $cfg->preferences->configPath = $values['preferences_ini_path'];
        } elseif ($cfg->preferences->type === 'db') {
            $cfg->preferences->resource = $values['preferences_db_resource'];
        }

        return $cfg;
    }
}
