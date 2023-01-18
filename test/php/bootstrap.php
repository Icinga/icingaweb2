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
set_include_path(implode(PATH_SEPARATOR, [
    $libraryPath,
    $basePath . DIRECTORY_SEPARATOR . 'vendor',
    get_include_path()
]));

$vendorAutoload = $basePath . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

require_once($icingaLibPath . '/Test/ClassLoader.php');

$loader = new Icinga\Test\ClassLoader();
$loader->registerNamespace('Tests', $testLibraryPath);
$loader->registerNamespace('Icinga', $icingaLibPath);
$loader->registerNamespace('Icinga\\Forms', $applicationPath . '/forms');

$libraryPaths = getenv('ICINGAWEB_LIBDIR');
if ($libraryPaths !== false) {
    $libraryPaths = array_filter(array_map(
        'realpath',
        explode(':', $libraryPaths)
    ), 'is_dir');
} else {
    $libraryPaths = is_dir('/usr/share/icinga-php')
        ? ['/usr/share/icinga-php']
        : [];
}

foreach ($libraryPaths as $externalLibraryPath) {
    $libPaths = array_flip(scandir($externalLibraryPath));
    unset($libPaths['.']);
    unset($libPaths['..']);
    $libPaths = array_keys($libPaths);
    foreach ($libPaths as $libPath) {
        $libPath = join(DIRECTORY_SEPARATOR, [$externalLibraryPath, $libPath]);
        if (is_dir(realpath($libPath))) {
            $libAutoLoader = join(DIRECTORY_SEPARATOR, [$libPath, 'vendor', 'autoload.php']);
            if (file_exists($libAutoLoader)) {
                require_once $libAutoLoader;
            }
        }
    }
}

$modulePaths = getenv('ICINGAWEB_MODULE_DIRS');

if ($modulePaths) {
    $modulePaths = preg_split('/:/', $modulePaths, -1, PREG_SPLIT_NO_EMPTY);
}

if (! $modulePaths) {
    $modulePaths = array_flip(scandir($modulePath));
    unset($modulePaths['.']);
    unset($modulePaths['..']);
    $modulePaths = array_keys($modulePaths);

    foreach ($modulePaths as &$path) {
        $path = "$modulePath/$path";
    }
    unset($path);
}

foreach ($modulePaths as $path) {
    $module = basename($path);

    $moduleNamespace = 'Icinga\\Module\\' . ucfirst($module);
    $moduleLibraryPath = "$path/library/" . ucfirst($module);

    if (is_dir($moduleLibraryPath)) {
        $loader->registerNamespace($moduleNamespace, $moduleLibraryPath);
    }

    $moduleTestPath = "$path/test/php/Lib";
    if (is_dir($moduleTestPath)) {
        $loader->registerNamespace('Tests\\' . $moduleNamespace . '\\Lib', $moduleTestPath);
    }

    $moduleFormPath = "$path/application/forms";
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
