<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Controllers;

use Icinga\Module\Eventdb\Forms\Config\BackendConfigForm;
use Icinga\Web\Controller;

class ConfigController extends Controller
{
    public function init()
    {
        $this->assertPermission('config/modules');
        parent::init();
    }

    public function indexAction()
    {
        $backendConfig = new BackendConfigForm();
        $backendConfig
            ->setIniConfig($this->Config())
            ->handleRequest();
        $this->view->form = $backendConfig;
        $this->view->tabs = $this->Module()->getConfigTabs()->activate('backend');
    }
}
