<?php

namespace Icinga\Application\Modules;

use Icinga\Application\ApplicationBootstrap;
use Icinga\Application\Icinga;
use Icinga\Web\Hook;
use Zend_Controller_Router_Route as Route;

class Module
{
    protected $name;
    protected $basedir;
    protected $cssdir;
    protected $libdir;
    protected $localedir;
    protected $controllerdir;
    protected $registerscript;
    protected $app;

    public function __construct(ApplicationBootstrap $app, $name, $basedir)
    {
        $this->app            = $app;
        $this->name           = $name;
        $this->basedir        = $basedir;
        $this->cssdir         = $basedir . '/public/css';
        $this->libdir         = $basedir . '/library';
        $this->configdir      = $basedir . '/config';
        $this->localedir      = $basedir . '/application/locale';
        $this->controllerdir  = $basedir . '/application/controllers';
        $this->registerscript = $basedir . '/register.php';
    }

    public function register()
    {
        $this->registerLibrary()
             ->registerWebIntegration()
             ->runRegisterScript();
        return true;
    }

    public static function exists($name)
    {
        return Icinga::app()->moduleManager()->hasEnabled($name);
    }

    public static function get($name, $autoload = false)
    {
        $manager = Icinga::app()->moduleManager();
        if (! $manager->hasLoaded($name)) {
            if ($autoload === true && $manager->hasEnabled($name)) {
                $manager->loadModule($name);
            }
        }
        // @throws ProgrammingError:
        return $manager->getModule($name);
    }

    public function hasCss()
    {
        return file_exists($this->getCssFilename());
    }

    public function getCssFilename()
    {
        return $this->cssdir . '/module.less';
    }

    public function getBaseDir()
    {
        return $this->basedir;
    }

    public function getConfigDir()
    {
        return $this->configdir;
    }

    public function getConfig($file = null)
    {
        return $this->app
            ->getConfig()
            ->module($this->name, $file);
    }

    protected function registerLibrary()
    {
        if (file_exists($this->libdir) && is_dir($this->libdir)) {
            $this->app->getLoader()->addModule($this->name, $this->libdir);
        }
        return $this;
    }

    protected function registerLocales()
    {
        if (file_exists($this->localedir) && is_dir($this->localedir)) {
            bindtextdomain($this->name, $this->localedir);
        }
        return $this;
    }

    protected function registerWebIntegration()
    {
        if (! $this->app->isWeb()) {
            return $this;
        }

        if (file_exists($this->controllerdir) && is_dir($this->controllerdir)) {
            $this->app->frontController()->addControllerDirectory(
                $this->controllerdir,
                $this->name
            );
        }

        $this->registerLocales()
             ->registerRoutes()
             ->registerMenuEntries();
        return $this;
    }

    protected function registerMenuEntries()
    {
        $cfg = $this->app
            ->getConfig()
            ->module($this->name, 'menu');
        $view = $this->app->getView();
        if ($cfg) {
            $view->view->navigation = $cfg->merge($view->view->navigation);
        }
        return $this;
    }

    protected function registerRoutes()
    {
        $this->app->frontController()->getRouter()->addRoute(
            $this->name . '_jsprovider',
            new Route(
                'js/' . $this->name . '/:file',
                array(
                    'controller'    => 'static',
                    'action'        =>'javascript',
                    'moduleName'    => $this->name
                )
            )
        );
        $this->app->frontController()->getRouter()->addRoute(
            $this->name . '_img',
            new Route(
                'img/' . $this->name . '/:file',
                array(
                    'controller'    => 'static',
                    'action'        => 'img',
                    'moduleName'    => $this->name
                )
            )
        );
        return $this;
    }

    protected function runRegisterScript()
    {
        if (file_exists($this->registerscript)
         && is_readable($this->registerscript)) {
            include($this->registerscript);
        }
        return $this;
    }

    protected function registerHook($name, $class)
    {
        Hook::register($name, $class);
        return $this;
    }
}
