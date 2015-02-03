<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

$applicationPath = realpath(dirname(__FILE__) . '/../../application/');
$modulePath = realpath(dirname(__FILE__) . '/../../modules/');
$libraryPath = realpath(dirname(__FILE__) . '/../../library/');
$testLibraryPath = realpath(dirname(__FILE__) . '/library/');
$configPath = realpath($libraryPath . '/../config');

// Is usually done in the application's bootstrap and is used by some of our internals
if (!defined('ICINGAWEB_APPDIR')) {
    define('ICINGAWEB_APPDIR', $applicationPath);
}
if (!defined('ICINGA_LIBDIR')) {
    define('ICINGA_LIBDIR', $libraryPath);
}

// This is needed to get the Zend Plugin loader working
set_include_path(implode(PATH_SEPARATOR, array($libraryPath, get_include_path())));

require_once 'Mockery/Loader.php';
$mockeryLoader = new \Mockery\Loader;
$mockeryLoader->register();

require_once($libraryPath . '/Icinga/Application/Loader.php');

$loader = new Icinga\Application\Loader();
$loader->registerNamespace('Tests', $testLibraryPath);
$loader->registerNamespace('Icinga', $libraryPath . '/Icinga');
$loader->registerNamespace('Icinga\\Forms', $applicationPath . '/forms');

$modules = scandir($modulePath);
foreach ($modules as $module) {
    if ($module === '.' || $module === '..') {
        continue;
    }

    $moduleNamespace = 'Icinga\\Module\\' . ucfirst($module);
    $moduleLibraryPath = $modulePath . '/' . $module . '/library/' . ucfirst($module);

    if (is_dir($moduleLibraryPath)) {
        $loader->registerNamespace($moduleNamespace, $moduleLibraryPath);
    }

    $moduleTestPath = $modulePath . '/' . $module . '/test/php';
    if (is_dir($moduleTestPath)) {
        $loader->registerNamespace('Tests\\' . $moduleNamespace, $moduleTestPath);
    }

    $moduleFormPath = $modulePath . '/' . $module . '/application/forms';
    if (is_dir($moduleFormPath)) {
        $loader->registerNamespace($moduleNamespace . '\\Forms', $moduleFormPath);
    }
}

$loader->register();

set_include_path(
    implode(
        PATH_SEPARATOR,
        array($libraryPath . '/vendor', get_include_path())
    )
);

require_once 'Zend/Loader/Autoloader.php';
\Zend_Loader_Autoloader::getInstance();

Icinga\Application\Config::$configDir = $configPath;
