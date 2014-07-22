<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config;

use Icinga\Application\Config as IcingaConfig;
use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;
use Icinga\Util\Translator;
use Icinga\Web\Form\Validator\WritablePathValidator;
use Icinga\Web\Form\Decorator\ConditionalHidden;
use DateTimeZone;
use Zend_Form_Element_Select;
use Zend_Config;

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
            return ResourceFactory::getResourceConfigs()->toArray();
        } else {
            return $this->resources;
        }
    }

    /**
     * Add a select field for setting the default language
     *
     * Possible values are determined by Translator::getAvailableLocaleCodes.
     *
     * @param   Zend_Config     $cfg    The "global" section of the config.ini
     */
    private function addLanguageSelection(Zend_Config $cfg)
    {
        $languages = array();
        foreach (Translator::getAvailableLocaleCodes() as $language) {
            $languages[$language] = $language;
        }
        $languages[Translator::DEFAULT_LOCALE] = Translator::DEFAULT_LOCALE;

        $this->addElement(
            'select',
            'language',
            array(
                'label'         => t('Default Language'),
                'required'      => true,
                'multiOptions'  => $languages,
                'helptext'      => t(
                    'Select the language to use by default. Can be overwritten by a user in his preferences.'
                ),
                'value'         => $cfg->get('language', Translator::DEFAULT_LOCALE)
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
            'module_path',
            array(
                'label'     => 'Module Path',
                'required'  => true,
                'helptext'  => 'Contains the directories that will be searched for available modules, separated by ' .
                    ' colons. Modules  that don\'t exist in these directories can still be symlinked in the module ' .
                    ' folder, but won\'t show up in the list of disabled modules.',
                'value'     => $cfg->get('modulePath', realpath(ICINGAWEB_APPDIR . '/../modules'))
            )
        );
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
                    'ini'   => 'File System (INI Files)',
                    'db'    => 'Database',
                    'null'  => 'Don\'t Store Preferences'
                )
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
        $this->addElement($txtPreferencesDbResource);

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
        $this->addLanguageSelection($global);
        $this->addTimezoneSelection($global);
        $this->addModuleSettings($global);
        $this->addUserPreferencesDialog($preferences);

        $this->setSubmitLabel('Save Changes');
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
            $config->global = new Zend_Config(array(), true);
        }
        if ($config->preferences === null) {
            $config->preferences = new Zend_Config(array(), true);
        }

        $values = $this->getValues();
        $cfg = clone $config;
        $cfg->global->language     = $values['language'];
        $cfg->global->timezone     = $values['timezone'];
        $cfg->global->modulePath   = $values['module_path'];
        $cfg->preferences->type = $values['preferences_type'];
        if ($cfg->preferences->type === 'db') {
            $cfg->preferences->resource = $values['preferences_db_resource'];
        }

        return $cfg;
    }
}
