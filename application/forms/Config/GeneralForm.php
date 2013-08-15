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

use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Application\Icinga;
use \Icinga\Application\DbAdapterFactory;
use \Icinga\Web\Form;
use \Icinga\Web\Form\Decorator\ConditionalHidden;
use \Icinga\Web\Form\Element\Note;

use \DateTimeZone;
use \Zend_Config;
use \Zend_Form_Element_Text;
use \Zend_Form_Element_Select;

/**
 * Configuration form for general, application-wide settings
 *
 */
class GeneralForm extends Form
{
    /**
     * The configuration to use for populating this form
     *
     * @var IcingaConfig
     */
    private $config = null;

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
    private $resources = null;

    /**
     * Set the configuration to be used for this form
     *
     * @param IcingaConfig $cfg
     */
    public function setConfiguration($cfg)
    {
        $this->config = $cfg;
    }

    /**
     * Set a specific configuration directory to use for configuration specific default paths
     *
     * @param string $dir
     */
    public function setConfigDir($dir)
    {
        $this->configDir = $dir;
    }

    /**
     * Return the config path set for this form or the application wide config path if none is set
     *
     * @return string
     * @see IcingaConfig::configDir
     */
    public function getConfigDir()
    {
        return $this->configDir === null ? IcingaConfig::$configDir : $this->configDir;
    }

    /**
     * Set an alternative array of resources that should be used instead of the DBFactory resource set
     * (used for testing)
     *
     * @param array $resources              The resources to use for populating the db selection field
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
        if ($this->resources === null ) {
            return DbAdapterFactory::getResources();
        } else {
            return $this->resources;
        }
    }

    /**
     * Add the checkbox for using the development environment to this form
     *
     * @param Zend_Config $cfg         The "global" section of the config.ini
     */
    private function addDevelopmentCheckbox(Zend_Config $cfg)
    {
        $env = $cfg->get('environment', 'development');
        $this->addElement(
            'checkbox',
            'environment',
            array(
                'label'     => 'Development mode',
                'required'  => true,
                'tooltip'   => 'More verbose output',
                'value'     => $env === 'development'
            )
        );
        $this->addElement(new Note(array(
            'name'  => 'note_env',
            'value' => 'Set true to show more detailed errors and disable certain optimizations '
                        . 'in order to make debugging easier.'
        )));
    }

    /**
     * Add a select field for setting the default timezone.
     *
     * Possible values are determined by DateTimeZone::listIdentifiers
     *
     * @param Zend_Config $cfg         The "global" section of the config.ini
     */
    private function addTimezoneSelection(Zend_Config $cfg)
    {
        $tzList = array();
        foreach(DateTimeZone::listIdentifiers() as $tz) {
            $tzList[$tz] = $tz;
        }

        $this->addElement(
            'select',
            'timezone',
            array(
                'label'         =>  'Default application timezone',
                'required'      =>  true,
                'multiOptions'  =>  $tzList,
                'value'         =>  $cfg->get('timezone', date_default_timezone_get())
            )
        );
        $this->addElement(new Note(array(
            'name' => 'noteTimezone',
            'value' => 'Select the timezone to be used as the default. User\'s can set their own timezone if'.
            ' they like to, but this is the timezone to be used as the default setting .'
        )));
    }

    /**
     * Add configuration settings for module paths
     *
     * @param Zend_Config $cfg          The "global" section of the config.ini
     */
    private function addModuleSettings(Zend_Config $cfg)
    {
        $this->addElement(
            'text',
            'module_folder',
            array(
                'label'     => 'Module folder',
                'required'  => true,
                'value'     => $cfg->get('moduleFolder',  $this->getConfigDir() . '/config/enabledModules')
            )
        );
        $this->addElement(new Note(array(
            'name' => 'noteModuleFolder',
            'value' => 'Use this folder to activate modules (must be writable by your webserver)'
        )));
    }

    /**
     * Add text fields for the date and time format used in the application
     *
     * @param Zend_Config $cfg         The "global" section of the config.ini
     */
    private function addDateFormatSettings(Zend_Config $cfg)
    {
        $phpUrl = '<a href="http://php.net/manual/en/function.date.php" target="_new">the official PHP documentation</a>';

        $this->addElement(
            'text',
            'date_format',
            array(
                'label'     =>  'Date format',
                'required'  =>  true,
                'value'     => $cfg->get('dateFormat', 'd/m/Y')
            )
        );
        $this->addElement(new Note(array(
            'name'  =>  'noteDateFormat',
            'value' =>  'Display dates according to this format. See ' . $phpUrl . ' for possible values'
        )));


        $this->addElement(
            'text',
            'time_format',
            array(
                'label'     =>  'Time format',
                'required'  =>  true,
                'value'     => $cfg->get('timeFormat', 'g:i A')
            )
        );
        $this->addElement(new Note(array(
            'name'  =>  'noteTimeFormat',
            'value' =>  'Display times according to this format. See ' . $phpUrl . ' for possible values'
        )));
    }

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
                'label'         => 'User preference storage type',
                'required'      => true,
                'value'         => $backend,
                'multiOptions'  => array(
                    'ini'   => 'File system (ini files)',
                    'db'    => 'Database'
                )
            )
        );

        $txtPreferencesIniPath = new Zend_Form_Element_Text(
            array(
                'name'      =>  'preferences_ini_path',
                'label'     =>  'Path to store user preference files',
                'required'  =>  $backend === 'ini',
                'condition' =>  $backend === 'ini',
                'value'     =>  $cfg->get('configPath')
            )
        );
        $backends = array();
        foreach ($this->getResources() as $name => $resource)
        {
            if ($resource['type'] !== 'db') {
                continue;
            }
            $backends[$name] = $name;
        }

        $txtPreferencesDbResource = new Zend_Form_Element_Select(
            array(
                'name'          =>  'preferences_db_resource',
                'label'         =>  'Database connection',
                'required'      =>  $backend === 'db',
                'condition'     =>  $backend === 'db',
                'value'         =>  $cfg->get('resource'),
                'multiOptions'  =>  $backends
            )
        );

        $this->addElement($txtPreferencesIniPath);
        $this->addElement($txtPreferencesDbResource);

        $txtPreferencesIniPath->addDecorator(new ConditionalHidden());
        $txtPreferencesDbResource->addDecorator(new ConditionalHidden());
        $this->enableAutoSubmit(array(
            'preferences_type'
        ));
    }

    /**
     * Create the general form, using the provided configuration
     *
     * @see Form::create()
     */
    public function create()
    {
        if ($this->config === null) {
            $this->config = new Zend_Config(array());
        }
        $global = $this->config->global;
        if ($global === null) {
            $global = new Zend_Config(array());
        }
        $preferences = $this->config->preferences;
        if ($preferences === null) {
            $preferences = new Zend_Config(array());
        }

        $this->addDevelopmentCheckbox($global);
        $this->addTimezoneSelection($global);
        $this->addModuleSettings($global);
        $this->addDateFormatSettings($global);
        $this->addUserPreferencesDialog($preferences);

        $this->setSubmitLabel('Save changes');
    }

    public function getConfig()
    {
        if ($this->config === null) {
            $this->config = new Zend_Config(array());
        }
        if ($this->config->global === null) {
            $this->config->global = new Zend_Config(array());
        }
        if ($this->config->preferences === null) {
            $this->config->preferences = new Zend_Config(array());
        }

        $values = $this->getValues();
        $cfg = clone $this->config;
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