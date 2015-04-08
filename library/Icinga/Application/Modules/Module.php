<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application\Modules;

use Exception;
use Zend_Controller_Router_Route_Abstract;
use Zend_Controller_Router_Route as Route;
use Zend_Controller_Router_Route_Regex as RegexRoute;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Data\ConfigObject;
use Icinga\Util\Translator;
use Icinga\Web\Hook;
use Icinga\Web\Menu;
use Icinga\Web\Widget;
use Icinga\Web\Widget\Dashboard\Pane;
use Icinga\Module\Setup\SetupWizard;
use Icinga\Util\File;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\IcingaException;

/**
 * Module handling
 *
 * Register modules and initialize it
 */
class Module
{
    /**
     * Module name
     *
     * @var string
     */
    private $name;

    /**
     * Base directory of module
     *
     * @var string
     */
    private $basedir;

    /**
     * Directory for styles
     *
     * @var string
     */
    private $cssdir;

    /**
     * Library directory
     *
     * @var string
     */
    private $libdir;

    /**
     * Directory containing translations
     *
     * @var string
     */
    private $localedir;

    /**
     * Directory where controllers reside
     *
     * @var string
     */
    private $controllerdir;

    /**
     * Directory containing form implementations
     *
     * @var string
     */
    private $formdir;

    /**
     * Module bootstrapping script
     *
     * @var string
     */
    private $runScript;

    /**
     * Module configuration script
     *
     * @var string
     */
    private $configScript;

    /**
     * Module metadata filename
     *
     * @var string
     */
    private $metadataFile;

    /**
     * Module metadata (version...)
     *
     * @var stdClass
     */
    private $metadata;

    /**
     * Whether we already tried to include the module configuration script
     *
     * @var bool
     */
    private $triedToLaunchConfigScript = false;

    /**
     * Whether this module has been registered
     *
     * @var bool
     */
    private $registered = false;

    /**
     * Provided permissions
     *
     * @var array
     */
    private $permissionList = array();

    /**
     * Provided restrictions
     *
     * @var array
     */
    private $restrictionList = array();

    /**
     * Provided config tabs
     *
     * @var array
     */
    private $configTabs = array();

    /**
     * Provided setup wizard
     *
     * @var string
     */
    private $setupWizard;

    /**
     * Icinga application
     *
     * @var \Icinga\Application\Web
     */
    private $app;

    /**
     * Routes to add to the route chain
     *
     * @var array Array of name-route pairs
     *
     * @see addRoute()
     */
    protected $routes = array();

    /**
     * A set of menu elements
     *
     * @var array
     */
    protected $menuItems = array();

    /**
     * A set of Pane elements
     *
     * @var array
     */
    protected $paneItems = array();

    /**
     * @var array
     */
    protected $searchUrls = array();

    /**
     * Provide a search URL
     *
     * @param string    $title
     * @param string    $url
     * @param int       $priority
     */
    public function provideSearchUrl($title, $url, $priority = 0)
    {
        $searchUrl = (object) array(
            'title'     => (string) $title,
            'url'       => (string) $url,
            'priority'  => (int) $priority
        );

        $this->searchUrls[] = $searchUrl;
    }

    public function getSearchUrls()
    {
        $this->launchConfigScript();
        return $this->searchUrls;
    }

    /**
     * Get all Menu Items
     *
     * @return array
     */
    public function getPaneItems()
    {
        $this->launchConfigScript();
        return $this->paneItems;
    }

    /**
     * Add a pane to dashboard
     *
     * @param $name
     * @return Pane
     */
    protected function dashboard($name)
    {
        $this->paneItems[$name] = new Pane($name);
        return $this->paneItems[$name];
    }

    /**
     * Get all Menu Items
     *
     * @return array
     */
    public function getMenuItems()
    {
        $this->launchConfigScript();
        return $this->menuItems;
    }

    /**
     * Add a menu Section to the Sidebar menu
     *
     * @param $name
     * @param array $properties
     * @return mixed
     */
    protected function menuSection($name, array $properties = array())
    {
        if (array_key_exists($name, $this->menuItems)) {
            $this->menuItems[$name]->setProperties($properties);
        } else {
            $this->menuItems[$name] = new Menu($name, new ConfigObject($properties));
        }

        return $this->menuItems[$name];
    }

    /**
     * Create a new module object
     *
     * @param ApplicationBootstrap  $app
     * @param string                $name
     * @param string                $basedir
     */
    public function __construct(ApplicationBootstrap $app, $name, $basedir)
    {
        $this->app            = $app;
        $this->name           = $name;
        $this->basedir        = $basedir;
        $this->cssdir         = $basedir . '/public/css';
        $this->jsdir          = $basedir . '/public/js';
        $this->libdir         = $basedir . '/library';
        $this->configdir      = $app->getConfigDir('modules/' . $name);
        $this->localedir      = $basedir . '/application/locale';
        $this->formdir        = $basedir . '/application/forms';
        $this->controllerdir  = $basedir . '/application/controllers';
        $this->runScript      = $basedir . '/run.php';
        $this->configScript   = $basedir . '/configuration.php';
        $this->metadataFile   = $basedir . '/module.info';
    }

    /**
     * Register module
     *
     * @return bool
     */
    public function register()
    {
        if ($this->registered) {
            return true;
        }

        $this->registerAutoloader();
        try {
            $this->launchRunScript();
        } catch (Exception $e) {
            Logger::warning(
                'Launching the run script %s for module %s failed with the following exception: %s',
                $this->runScript,
                $this->name,
                $e->getMessage()
            );
            return false;
        }

        $this->registerWebIntegration();
        $this->registered = true;
        return true;
    }

    /**
     * Return whether this module has been registered
     *
     * @return  bool
     */
    public function isRegistered()
    {
        return $this->registered;
    }

    /**
     * Test for an enabled module by name
     *
     * @param string $name
     *
     * @return boolean
     */
    public static function exists($name)
    {
        return Icinga::app()->getModuleManager()->hasEnabled($name);
    }

    /**
     * Get module by name
     *
     * @param string $name
     * @param bool   $autoload
     *
     * @return mixed
     *
     * @throws ProgrammingError When the module is not yet loaded
     */
    public static function get($name, $autoload = false)
    {
        $manager = Icinga::app()->getModuleManager();
        if (!$manager->hasLoaded($name)) {
            if ($autoload === true && $manager->hasEnabled($name)) {
                $manager->loadModule($name);
            }
        }
        // Throws ProgrammingError when the module is not yet loaded
        return $manager->getModule($name);
    }

    /**
     * Test if module provides css
     *
     * @return bool
     */
    public function hasCss()
    {
        return file_exists($this->getCssFilename());
    }

    /**
     * Returns the complete less file name
     *
     * @return string
     */
    public function getCssFilename()
    {
        return $this->cssdir . '/module.less';
    }

    /**
     * Test if module provides js
     *
     * @return bool
     */
    public function hasJs()
    {
        return file_exists($this->getJsFilename());
    }

    /**
     * Returns the complete js file name
     *
     * @return string
     */
    public function getJsFilename()
    {
        return $this->jsdir . '/module.js';
    }

    /**
     * Getter for module name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Getter for module version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->metadata()->version;
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->metadata()->description;
    }

    /**
     * Get module title (short description)
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->metadata()->title;
    }

    /**
     * Getter for module version
     *
     * @return Array
     */
    public function getDependencies()
    {
        return $this->metadata()->depends;
    }

    /**
     * Fetch module metadata
     *
     * @return object
     */
    protected function metadata()
    {
        if ($this->metadata === null) {
            $metadata = (object) array(
                'name'        => $this->getName(),
                'version'     => '0.0.0',
                'title'       => null,
                'description' => '',
                'depends'     => array(),
            );

            if (file_exists($this->metadataFile)) {

                $key = null;
                $file = new File($this->metadataFile, 'r');
                foreach ($file as $line) {
                    $line = rtrim($line);

                    if ($key === 'description') {
                        if (empty($line)) {
                            $metadata->description .= "\n";
                            continue;
                        } elseif ($line[0] === ' ') {
                            $metadata->description .= $line;
                            continue;
                        }
                    }

                    list($key, $val) = preg_split('/:\s+/', $line, 2);
                    $key = lcfirst($key);

                    switch ($key) {

                        case 'depends':
                            if (strpos($val, ' ') === false) {
                                $metadata->depends[$val] = true;
                                continue;
                            }

                            $parts = preg_split('/,\s+/', $val);
                            foreach ($parts as $part) {
                                if (preg_match('/^(\w+)\s+\((.+)\)$/', $part, $m)) {
                                    $metadata->depends[$m[1]] = $m[2];
                                } else {
                                    // TODO: FAIL?
                                    continue;
                                }
                            }
                            break;

                        case 'description':
                            if ($metadata->title === null) {
                                $metadata->title = $val;
                            } else {
                                $metadata->description = $val;
                            }
                            break;

                        default:
                            $metadata->{$key} = $val;

                    }
                }
            }

            if ($metadata->title === null) {
                $metadata->title = $this->getName();
            }

            if ($metadata->description === '') {
                // TODO: Check whether the translation module is able to
                //       extract this
                $metadata->description = t(
                    'This module has no description'
                );
            }

            $this->metadata = $metadata;
        }
        return $this->metadata;
    }

    /**
     * Getter for css file name
     *
     * @return string
     */
    public function getCssDir()
    {
        return $this->cssdir;
    }

    /**
     * Getter for base directory
     *
     * @return string
     */
    public function getBaseDir()
    {
        return $this->basedir;
    }

    /**
     * Get the controller directory
     *
     * @return string
     */
    public function getControllerDir()
    {
        return $this->controllerdir;
    }

    /**
     * Getter for library directory
     *
     * @return string
     */
    public function getLibDir()
    {
        return $this->libdir;
    }

    /**
     * Getter for configuration directory
     *
     * @return string
     */
    public function getConfigDir()
    {
        return $this->configdir;
    }

    /**
     * Getter for form directory
     *
     * @return string
     */
    public function getFormDir()
    {
        return $this->formdir;
    }

    /**
     * Getter for module config object
     *
     * @param string $file
     *
     * @return Config
     */
    public function getConfig($file = null)
    {
        return $this->app->getConfig()->module($this->name, $file);
    }

    /**
     * Retrieve provided permissions
     *
     * @param string $name Permission name
     *
     * @return array
     */
    public function getProvidedPermissions()
    {
        $this->launchConfigScript();
        return $this->permissionList;
    }

    /**
     * Retrieve provided restrictions
     *
     * @param  string  $name Restriction name
     * @return array
     */
    public function getProvidedRestrictions()
    {
        $this->launchConfigScript();
        return $this->restrictionList;
    }

    /**
     * Whether the given permission name is supported
     *
     * @param string $name Permission name
     *
     * @return bool
     */
    public function providesPermission($name)
    {
        $this->launchConfigScript();
        return array_key_exists($name, $this->permissionList);
    }

    /**
     * Whether the given restriction name is supported
     *
     * @param string $name Restriction name
     *
     * @return bool
     */
    public function providesRestriction($name)
    {
        $this->launchConfigScript();
        return array_key_exists($name, $this->restrictionList);
    }

    /**
     * Retrieve this modules configuration tabs
     *
     * @return Icinga\Web\Widget\Tabs
     */
    public function getConfigTabs()
    {
        $this->launchConfigScript();
        $tabs = Widget::create('tabs');
        $tabs->add('info', array(
            'url'       => 'config/module',
            'urlParams' => array('name' => $this->getName()),
            'label'     => 'Module: ' . $this->getName()
        ));
        foreach ($this->configTabs as $name => $config) {
            $tabs->add($name, $config);
        }
        return $tabs;
    }

    /**
     * Whether this module provides a setup wizard
     *
     * @return  bool
     */
    public function providesSetupWizard()
    {
        $this->launchConfigScript();
        if (class_exists($this->setupWizard)) {
            $wizard = new $this->setupWizard;
            return $wizard instanceof SetupWizard;
        }

        return false;
    }

    /**
     * Return this module's setup wizard
     *
     * @return  SetupWizard
     */
    public function getSetupWizard()
    {
        return new $this->setupWizard;
    }

    /**
     * Provide a named permission
     *
     * @param string $name Unique permission name
     * @param string $name Permission description
     *
     * @return void
     */
    protected function providePermission($name, $description)
    {
        if ($this->providesPermission($name)) {
            throw new IcingaException(
                'Cannot provide permission "%s" twice',
                $name
            );
        }
        $this->permissionList[$name] = (object) array(
            'name'        => $name,
            'description' => $description
        );
    }

    /**
     * Provide a named restriction
     *
     * @param string $name        Unique restriction name
     * @param string $description Restriction description
     *
     * @return void
     */
    protected function provideRestriction($name, $description)
    {
        if ($this->providesRestriction($name)) {
            throw new IcingaException(
                'Cannot provide restriction "%s" twice',
                $name
            );
        }
        $this->restrictionList[$name] = (object) array(
            'name'        => $name,
            'description' => $description
        );
    }

    /**
     * Provide a module config tab
     *
     * @param string $name   Unique tab name
     * @param string $config Tab config
     *
     * @return $this
     */
    protected function provideConfigTab($name, $config = array())
    {
        if (! array_key_exists('url', $config)) {
            throw new ProgrammingError('A module config tab MUST provide and "url"');
        }
        $config['url'] = $this->getName() . '/' . ltrim($config['url'], '/');
        $this->configTabs[$name] = $config;
        return $this;
    }

    /**
     * Provide a setup wizard
     *
     * @param   string  $className      The name of the class
     *
     * @return  $this
     */
    protected function provideSetupWizard($className)
    {
        $this->setupWizard = $className;
        return $this;
    }

    /**
     * Register new namespaces on the autoloader
     *
     * @return $this
     */
    protected function registerAutoloader()
    {
        $moduleName = ucfirst($this->getName());

        $moduleLibraryDir = $this->getLibDir(). '/'. $moduleName;
        if (is_dir($moduleLibraryDir)) {
            $this->app->getLoader()->registerNamespace('Icinga\\Module\\' . $moduleName, $moduleLibraryDir);
        }

        $moduleFormDir = $this->getFormDir();
        if (is_dir($moduleFormDir)) {
            $this->app->getLoader()->registerNamespace('Icinga\\Module\\' . $moduleName. '\\Forms',  $moduleFormDir);
        }

        return $this;
    }

    /**
     * Bind text domain for i18n
     *
     * @return $this
     */
    protected function registerLocales()
    {
        if ($this->hasLocales()) {
            Translator::registerDomain($this->name, $this->localedir);
        }
        return $this;
    }

    /**
     * return bool Whether this module has translations
     */
    public function hasLocales()
    {
        return file_exists($this->localedir) && is_dir($this->localedir);
    }

    /**
     * List all available locales
     *
     * return array Locale list
     */
    public function listLocales()
    {
        $locales = array();
        if (! $this->hasLocales()) {
            return $locales;
        }

        $dh = opendir($this->localedir);
        while (false !== ($file = readdir($dh))) {
            $filename = $this->localedir . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $file) && is_dir($filename)) {
                $locales[] = $file;
            }
        }
        closedir($dh);
        sort($locales);
        return $locales;
    }

    /**
     * Register web integration
     *
     * Add controller directory to mvc
     *
     * @return $this
     */
    protected function registerWebIntegration()
    {
        if (!$this->app->isWeb()) {
            return $this;
        }

        if (file_exists($this->controllerdir) && is_dir($this->controllerdir)) {
            $this->app->getfrontController()->addControllerDirectory(
                $this->controllerdir,
                $this->name
            );
        }

        $this->registerLocales()
             ->registerRoutes();
        return $this;
    }

    /**
     * Add routes for static content and any route added via addRoute() to the route chain
     *
     * @return  $this
     * @see     addRoute()
     */
    protected function registerRoutes()
    {
        $router = $this->app->getFrontController()->getRouter();
        foreach ($this->routes as $name => $route) {
            $router->addRoute($name, $route);
        }
        $router->addRoute(
            $this->name . '_jsprovider',
            new Route(
                'js/' . $this->name . '/:file',
                array(
                    'controller'    => 'static',
                    'action'        =>'javascript',
                    'module_name'   => $this->name
                )
            )
        );
        $router->addRoute(
            $this->name . '_img',
            new RegexRoute(
                'img/' . $this->name . '/(.+)',
                array(
                    'controller'    => 'static',
                    'action'        => 'img',
                    'module_name'   => $this->name
                ),
                array(
                    1 => 'file'
                )
            )
        );
        return $this;
    }

    /**
     * Run module bootstrap script
     *
     * @return $this
     */
    protected function launchRunScript()
    {
        return $this->includeScript($this->runScript);
    }

    /**
     * Include a php script if it is readable
     *
     * @param string $file File to include
     *
     * @return $this
     */
    protected function includeScript($file)
    {
        if (file_exists($file) && is_readable($file) === true) {
            include($file);
        }

        return $this;
    }

    /**
     * Run module config script
     */
    protected function launchConfigScript()
    {
        if ($this->triedToLaunchConfigScript || !$this->registered) {
            return;
        }
        $this->triedToLaunchConfigScript = true;
        if (! file_exists($this->configScript)
         || ! is_readable($this->configScript)) {
            return;
        }
        include($this->configScript);
    }

    /**
     * Register hook
     *
     * @param string $name
     * @param string $class
     * @param string $key
     *
     * @return $this
     */
    protected function registerHook($name, $class, $key = null)
    {
        if ($key === null) {
            $key = $this->name;
        }

        Hook::register($name, $key, $class);

        return $this;
    }

    /**
     * Add a route which will be added to the route chain
     *
     * @param   string                                  $name   Name of the route
     * @param   Zend_Controller_Router_Route_Abstract   $route  Instance of the route
     *
     * @return  $this
     * @see     registerRoutes()
     */
    protected function addRoute($name, Zend_Controller_Router_Route_Abstract $route)
    {
        $this->routes[$name] = $route;
        return $this;
    }

    /**
     * Translate a string with the global mt()
     *
     * @param $string
     * @param null $context
     *
     * @return mixed|string
     */
    protected function translate($string, $context = null)
    {
        return mt($this->name, $string, $context);
    }

    /**
     * (non-PHPDoc)
     * @see Translator::translatePlural() For the function documentation.
     */
    protected function translatePlural($textSingular, $textPlural, $number, $context = null)
    {
        return mtp($this->name, $textSingular, $textPlural, $number, $context);
    }
}
