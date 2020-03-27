<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Form\NewDashletsForm;
use Icinga\Module\Dashboards\Web\Controller;

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
