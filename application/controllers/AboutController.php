<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Version;
use Icinga\Web\Controller\ActionController;
use Icinga\Application\Icinga;

class AboutController extends ActionController
{
    public function indexAction()
    {
        $this->view->hasPermission = $this->hasPermission('config/modules');
        $this->view->version = Version::get();
        $this->view->modules = Icinga::app()->getModuleManager()->getLoadedModules();
    }
}
