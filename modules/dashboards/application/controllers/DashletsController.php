<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Form\NewDashletsForm;

class DashletsController extends Controller
{
    public function indexAction()
    {

    }

    public function newAction()
    {
        $this->setTitle('New Dashlet');

        $dashletsForm = new NewDashletsForm();
        $this->addContent($dashletsForm);
    }
}
