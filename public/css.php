<?php

use Icinga\Application\EmbeddedWeb;

require_once dirname(__FILE__) . '/../library/Icinga/Application/EmbeddedWeb.php';
$app = EmbeddedWeb::start();
require_once 'vendor/lessphp/lessc.inc.php';
header('Content-type: text/css');
$less = new lessc;
$cssdir = dirname(__FILE__) . '/css';

echo $less->compileFile($cssdir . '/base.less');
echo $less->compileFile($cssdir . '/icinga.less');
foreach ($app->moduleManager()->getLoadedModules() as $name => $module) {
    if ($module->hasCss()) {
        echo $less->compile(
            '.icinga-module.module-'
            . $name
            . " {\n"
            . file_get_contents($module->getCssFilename())
            . "}\n\n"
        );
    }
}
