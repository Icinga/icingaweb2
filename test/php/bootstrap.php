<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

$basePath = getenv('ICINGAWEB_BASEDIR') ?: realpath(dirname(__FILE__) . '/../..');
$applicationPath = $basePath . '/application';
$modulePath = getenv('ICINGAWEB_MODULES_DIR') ?: ($basePath . '/modules');
$icingaLibPath = getenv('ICINGAWEB_ICINGA_LIB') ?: ($basePath . '/library/Icinga');
$libraryPath = $basePath . '/library';
$testLibraryPath = realpath(dirname(__FILE__) . '/library');
$configPath = $basePath . '/../config';

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

require_once($icingaLibPath . '/Test/ClassLoader.php');

if (! class_exists('PHPUnit_Framework_TestCase')) {
    require_once __DIR__ . '/phpunit-compat.php';
}

$loader = new Icinga\Test\ClassLoader();
$loader->registerNamespace('Tests', $testLibraryPath);
$loader->registerNamespace('Icinga', $icingaLibPath);
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
