<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Modules;

use Exception;
use Zend_Controller_Router_Route as Route;
use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Config;
use Icinga\Application\Icinga;
use Icinga\Logger\Logger;
use Icinga\Util\Translator;
use Icinga\Web\Hook;

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
     * Icinga application
     *
     * @var \Icinga\Application\Web
     */
    private $app;

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
        $this->configdir      = $basedir . '/config';
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
        $this->registerAutoloader()
             ->registerWebIntegration();
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
        return true;
    }

    /**
     * Test for an enabled module by name
     *
     * @param   string $name
     *
     * @return  boolean
     */
    public static function exists($name)
    {
        return Icinga::app()->getModuleManager()->hasEnabled($name);
    }

    /**
     * Get module by name
     *
     * @param   string  $name
     * @param   bool    $autoload
     *
     * @return  mixed
     *
     * @throws  \Icinga\Exception\ProgrammingError When the module is not yet loaded
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

                $fh = fopen($this->metadataFile, 'r');
                $key = null;

                while (false !== ($line = fgets($fh))) {
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
     * @param   string $file
     *
     * @return  Config
     */
    public function getConfig($file = null)
    {
        return $this->app
            ->getConfig()
            ->module($this->name, $file);
    }

    /**
     * Retrieve provided permissions
     *
     * @param  string  $name Permission name
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
     * @param  string  $name Permission name
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
     * @param  string  $name Restriction name
     * @return bool
     */
    public function providesRestriction($name)
    {
        $this->launchConfigScript();
        return array_key_exists($name, $this->restrictionList);
    }

    /**
     * Provide a named permission
     *
     * @param   string $name Unique permission name
     * @param   string $name Permission description
     * @return  void
     */
    protected function providePermission($name, $description)
    {
        if ($this->providesPermission($name)) {
            throw new Exception(
                sprintf('Cannot provide permission "%s" twice', $name)
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
     * @param   string $name Unique restriction name
     * @param   string $name Restriction description
     * @return  void
     */
    protected function provideRestriction($name, $description)
    {
        if ($this->providesRestriction($name)) {
            throw new Exception(
                sprintf('Cannot provide restriction "%s" twice', $name)
            );
        }
        $this->restrictionList[$name] = (object) array(
            'name'        => $name,
            'description' => $description
        );
    }

    /**
     * Register new namespaces on the autoloader
     *
     * @return self
     */
    protected function registerAutoloader()
    {
        $moduleName = ucfirst($this->getName());
        $moduleLibraryDir = $this->getLibDir(). '/'. $moduleName;
        if (is_dir($this->getBaseDir()) && is_dir($this->getLibDir()) && is_dir($moduleLibraryDir)) {
            $this->app->getLoader()->registerNamespace('Icinga\\Module\\' . $moduleName, $moduleLibraryDir);
            if (is_dir($this->getFormDir())) {
                $this->app->getLoader()->registerNamespace(
                    'Icinga\\Module\\' . $moduleName. '\\Form',
                    $this->getFormDir()
                );
            }
        }

        return $this;
    }

    /**
     * Bind text domain for i18n
     *
     * @return self
     */
    protected function registerLocales()
    {
        if (file_exists($this->localedir) && is_dir($this->localedir)) {
            Translator::registerDomain($this->name, $this->localedir);
        }
        return $this;
    }

    /**
     * Register web integration
     *
     * Add controller directory to mvc
     *
     * @return self
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
     * Register routes for web access
     *
     * @return self
     */
    protected function registerRoutes()
    {
        $this->app->getFrontController()->getRouter()->addRoute(
            $this->name . '_jsprovider',
            new Route(
                'js/' . $this->name . '/:file',
                array(
                    'controller'    => 'static',
                    'action'        =>'javascript',
                    'module_name'    => $this->name
                )
            )
        );
        $this->app->getFrontController()->getRouter()->addRoute(
            $this->name . '_img',
            new Route(
                'img/' . $this->name . '/:file',
                array(
                    'controller'    => 'static',
                    'action'        => 'img',
                    'module_name'   => $this->name
                )
            )
        );
        return $this;
    }

    /**
     * Run module bootstrap script
     *
     * @return self
     */
    protected function launchRunScript()
    {
        return $this->includeScript($this->runScript);
    }

    /**
     * Include a php script if it is readable
     *
     * @param   string  $file File to include
     *
     * @return  self
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
        if ($this->triedToLaunchConfigScript) {
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
     * @param   string $name
     * @param   string $class
     * @param   string $key
     *
     * @return  self
     */
    protected function registerHook($name, $class, $key = null)
    {
        if ($key === null) {
            $key = $this->name;
        }

        Hook::register($name, $key, $class);

        return $this;
    }
}
