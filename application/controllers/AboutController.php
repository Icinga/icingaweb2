<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

# namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Version;

class AboutController extends ActionController
{
    public function indexAction()
    {
        $this->view->version = Version::get();
    }
}
