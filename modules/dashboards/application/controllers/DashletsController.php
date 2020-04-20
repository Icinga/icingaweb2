<?php

namespace Icinga\Module\Dashboards\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Form\DashletForm;
use Icinga\Module\Dashboards\Form\EditDashletForm;
use Icinga\Module\Dashboards\Web\Controller;
use ipl\Sql\Select;

class DashletsController extends Controller
{
    use Database;

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

    public function editAction()
    {
        $this->tabs->disableLegacyExtensions();

        $dashletId = $this->params->get('dashletId');

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where(['dashboard_id = ?' => $dashletId]);

        $dashlet = $this->getDb()->fetchRow($select);

        $this->setTitle($this->translate('Edit Dashlet: %s'), $dashlet->name);

        $form = (new EditDashletForm($dashlet))
            ->on(EditDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }
}
