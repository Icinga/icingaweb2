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
        $this->tabs->disableLegacyExtensions();

        $dashletForm = (new DashletForm())
            ->on(DashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($dashletForm);
    }
}
