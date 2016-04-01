<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

use Icinga\Application\Icinga;

if (Icinga::app()->isCli()) {
    return;
}

$docModuleChapter = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/chapter/:chapter',
    array(
        'controller'    => 'module',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$docIcingaWebChapter = new Zend_Controller_Router_Route(
    'doc/icingaweb/chapter/:chapter',
    array(
        'controller'    => 'icingaweb',
        'action'        => 'chapter',
        'module'        => 'doc'
    )
);

$docModuleToc = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/toc',
    array(
        'controller'    => 'module',
        'action'        => 'toc',
        'module'        => 'doc'
    )
);

$docModulePdf = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/pdf',
    array(
        'controller'    => 'module',
        'action'        => 'pdf',
        'module'        => 'doc'
    )
);

$docModuleImg = new Zend_Controller_Router_Route_Regex(
    'doc/module/([^/]+)/image/(.+)',
    array(
        'controller'    => 'module',
        'action'        => 'image',
        'module'        => 'doc'
    ),
    array(
        'moduleName'    => 1,
        'image'         => 2
    ),
    'doc/module/%s/image/%s'
);

$this->addRoute('doc/module/chapter', $docModuleChapter);
$this->addRoute('doc/icingaweb/chapter', $docIcingaWebChapter);
$this->addRoute('doc/module/toc', $docModuleToc);
$this->addRoute('doc/module/pdf', $docModulePdf);
$this->addRoute('doc/module/img', $docModuleImg);
