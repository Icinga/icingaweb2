<?php

class TestInit
{
    public static function bootstrap()
    {
        $libaryPath = realpath(dirname(__FILE__) . '/../../library/');

        if (!defined('ICINGA_APPDIR')) {
            define('ICINGA_APPDIR', realpath($libaryPath . '/../application'));
        }

        $configPath = realpath($libaryPath . '/../config');

        $modulePath = realpath(dirname(__FILE__) . '/../../modules/');

        $applicationPath = realpath(dirname(__FILE__) . '/../../application/');

        set_include_path(
            $libaryPath . ':' . get_include_path()
        );

        require_once('Icinga/Application/Loader.php');

        $loader = new \Icinga\Application\Loader();

        $loader->registerNamespace('Icinga', $libaryPath . '/Icinga');
        $loader->registerNamespace('Icinga\\Form', $applicationPath . '/forms');


        $modules = scandir($modulePath);

        foreach ($modules as $module) {
            if ($module === '.' || $module === '..') {
                continue;
            }

            $baseNs = 'Icinga\\Module\\' . ucfirst($module);
            $moduleLibraryPath = $modulePath . '/' . $module . '/library/' . ucfirst($module);

            if (is_dir($moduleLibraryPath)) {
                $loader->registerNamespace($baseNs, $moduleLibraryPath);
            }

            $moduleTestPath = $modulePath . '/' . $module . '/test';
            if (is_dir($moduleTestPath)) {
                $testNs = $baseNs .= '\\Test';
                $loader->registerNamespace($testNs, $moduleTestPath);
            }

            $moduleFormPath = $modulePath . '/' . $module . '/application/forms';
            if (is_dir($moduleFormPath)) {
                $formNs = $baseNs .= '\\Form';
                $loader->registerNamespace($formNs, $moduleFormPath);
            }
        }

        $loader->register();

        require_once 'Zend/Loader/Autoloader.php';
        \Zend_Loader_Autoloader::getInstance();
        
        Icinga\Application\Config::$configDir = $configPath;
    }
}

TestInit::bootstrap();
