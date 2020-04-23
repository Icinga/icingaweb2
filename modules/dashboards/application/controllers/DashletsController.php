<?php

namespace Icinga\Module\Dashboards\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Form\DashletForm;
use Icinga\Module\Dashboards\Form\DeleteDashboardForm;
use Icinga\Module\Dashboards\Form\DeleteDashletForm;
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

    /**
     * Edit dashboard with the selected dashlet
     *
     * @throws \Icinga\Exception\MissingParameterException  If the param $dashletId doesn't exist
     */
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

    /**
     * Delete single dashboard with all its dashlets
     *
     * @throws \Icinga\Exception\MissingParameterException  If the parameter $dashboardId doesn't exist
     */
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

    /**
     * Remove individual dashlets from the given dashboard
     *
     * @throws \Icinga\Exception\MissingParameterException  If the parameter $dashletId doesn't exist
     */
    public function removeAction()
    {
        $dashletId = $this->params->getRequired('dashletId');
        $this->tabs->disableLegacyExtensions();

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where(['id = ?' => $dashletId]);

        $dashlet = $this->getDb()->select($select)->fetch();

        $this->setTitle($this->translate('Delete Dashlet: %s'), $dashlet->name);

        $form = (new DeleteDashletForm($dashlet))
            ->on(DeleteDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards/settings');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }
}
