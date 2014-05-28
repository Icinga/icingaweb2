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

$this->addRoute('doc/module/chapter', $docModuleChapter);
