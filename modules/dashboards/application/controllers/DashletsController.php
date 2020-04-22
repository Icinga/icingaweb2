<?php

namespace Icinga\Module\Dashboards\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Form\DashletForm;
use Icinga\Module\Dashboards\Form\DeleteDashboardForm;
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
        $dashletId = $this->params->getRequired('dashletId');
        $this->tabs->disableLegacyExtensions();

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where(['id = ?' => $dashletId]);

        $dashlet = $this->getDb()->select($select)->fetch();

        $this->setTitle($this->translate('Edit Dashlet: %s'), $dashlet->name);

        $form = (new EditDashletForm($dashlet))
            ->on(EditDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards/settings');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }

    public function deleteAction()
    {
        $this->tabs->disableLegacyExtensions();

        $select = (new Select())
            ->from('dashboard')
            ->columns('*')
            ->where(['id = ?' => $this->params->getRequired('dashboardId')]);

        $dashboard = $this->getDb()->select($select)->fetch();

        $this->setTitle($this->translate('Delete Dashboard: %s'), $dashboard->name);

        $form = (new DeleteDashboardForm($dashboard))
            ->on(DeleteDashboardForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards/settings');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }
}
