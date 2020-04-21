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

        $dashboardId = $this->params->get('dashboardId');

        $db = (new Select())
            ->from('dashboard')
            ->columns(['id', 'name'])
            ->where(['id = ?' => $dashboardId]);

        $dashboard = $this->getDb()->fetchRow($db);

        $this->setTitle($this->translate('Edit Dashboard: %s'), $dashboard->name);

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where(['dashboard_id = ?' => $dashboard->id]);

        $dashlet = $this->getDb()->fetchRow($select);

        $form = (new EditDashletForm($dashlet))
            ->on(EditDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }
}
