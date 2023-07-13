<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Test {

    use Icinga\Application\Test;

    $basePath = getenv('ICINGAWEB_BASEDIR') ?: realpath(dirname(__FILE__) . '/../..');
    $libraryPath = getenv('ICINGAWEB_ICINGA_LIB') ?: ($basePath . '/library/Icinga');
    $configPath = $basePath . '/test/config';

    require_once '../../library/vendor/Zend/Loader/Autoloader.php';
    \Zend_Loader_Autoloader::getInstance();

    require $libraryPath . '/Application/Test.php';
    Test::start($basePath, $configPath);
}
