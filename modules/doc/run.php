<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

use Icinga\Application\Icinga;

if (Icinga::app()->isCli()) {
    return;
}

$docModuleChapter = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/chapter/:chapter',
    [
        'controller'    => 'module',
        'action'        => 'chapter',
        'module'        => 'doc'
    ]
);

$docIcingaWebChapter = new Zend_Controller_Router_Route(
    'doc/icingaweb/chapter/:chapter',
    [
        'controller'    => 'icingaweb',
        'action'        => 'chapter',
        'module'        => 'doc'
    ]
);

$docModuleToc = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/toc',
    [
        'controller'    => 'module',
        'action'        => 'toc',
        'module'        => 'doc'
    ]
);

$docModulePdf = new Zend_Controller_Router_Route(
    'doc/module/:moduleName/pdf',
    [
        'controller'    => 'module',
        'action'        => 'pdf',
        'module'        => 'doc'
    ]
);

$docModuleImg = new Zend_Controller_Router_Route_Regex(
    'doc/module/([^/]+)/image/(.+)',
    [
        'controller'    => 'module',
        'action'        => 'image',
        'module'        => 'doc'
    ],
    [
        'moduleName'    => 1,
        'image'         => 2
    ],
    'doc/module/%s/image/%s'
);

$this->addRoute('doc/module/chapter', $docModuleChapter);
$this->addRoute('doc/icingaweb/chapter', $docIcingaWebChapter);
$this->addRoute('doc/module/toc', $docModuleToc);
$this->addRoute('doc/module/pdf', $docModulePdf);
$this->addRoute('doc/module/img', $docModuleImg);
