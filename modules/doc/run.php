<?php

use \Zend_Controller_Router_Route;
use Icinga\Application\Icinga;

if (Icinga::app()->isCli()) {
    return;
}

$docModuleChapter = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/chapter/:chapterName',
    array(
        'controller'    => 'module',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$docIcingaWebChapter = new Zend_Controller_Router_Route(
    'doc/icingaweb/chapter/:chapterName',
    array(
        'controller'    => 'icingaweb',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$this->addRoute('doc/module/chapter', $docModuleChapter);
$this->addRoute('doc/icingaweb/chapter', $docIcingaWebChapter);

