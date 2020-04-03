<?php

namespace Icinga\Module\Dashboards\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Dashboards\Form\DashletForm;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Web\Notification;

class DashletsController extends Controller
{
    public function newAction()
    {
        $this->setTitle('New Dashlet');

        $dashletForm = new DashletForm();

        $dashletForm->on($dashletForm::ON_SUCCESS, function () {
            Notification::success('Dashlet created');
            $this->redirectNow('dashboards/dashboards');
        });

        $dashletForm->handleRequest(ServerRequest::fromGlobals());
        $this->addContent($dashletForm);
    }
}
