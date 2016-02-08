<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Icinga;
use Icinga\Application\Version;
use Icinga\Web\Controller;

class AboutController extends Controller
{
    public function indexAction()
    {
        $this->view->version = Version::get();
        $this->view->modules = Icinga::app()->getModuleManager()->getLoadedModules();
        $this->view->tabs = $this->getTabs()->add(
            'about',
            array(
                'label' => $this->translate('About'),
                'title' => $this->translate('About Icinga Web 2'),
                'url'   => 'about'
            )
        )->activate('about');
    }
}
