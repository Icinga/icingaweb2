<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Version;
use Icinga\Web\Controller\ActionController;

class AboutController extends ActionController
{
    public function indexAction()
    {
        $this->view->version = Version::get();
    }
}
