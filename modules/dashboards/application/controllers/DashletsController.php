<?php

namespace Icinga\Module\Dashboards\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Dashboards\Form\DashletForm;
use Icinga\Module\Dashboards\Web\Controller;

class DashletsController extends Controller
{
    public function newAction()
    {
        $this->setTitle('New Dashlet');

        $dashletForm = new DashletForm();

        $dashletForm->on($dashletForm::ON_SUCCESS, function () {
            $this->redirectNow('dashboards');
        });

        $dashletForm->handleRequest(ServerRequest::fromGlobals());
        $this->addContent($dashletForm);
    }
}
