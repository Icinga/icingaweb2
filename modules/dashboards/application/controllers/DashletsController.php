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
     * @throws \Icinga\Exception\MissingParameterException  If the param $dashletId|$dashboardId doesn't exist
     */
    public function editAction()
    {
        $dashletId = $this->params->getRequired('dashletId');
        $dashboardId = $this->params->getRequired('dashboardId');

        $this->tabs->disableLegacyExtensions();

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->join('user_dashlet ud', 'ud.dashlet_id = dashlet.id')
            ->where([
                'id = ?' => $dashletId,
                'ud.user_dashboard_id = ?' => $dashboardId
            ]);

        $userDashlet = $this->getDb()->select($select)->fetch();

        $query = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where(['id = ?' => $dashletId]);

        $dashlet = $this->getDb()->select($query)->fetch();

        $selectPrivateDashboard = (new Select())
            ->from('dashboard')
            ->columns('*')
            ->where(['type = ?' => 'private', 'id = ?' => $dashboardId]);

        $dashboard = $this->getDb()->select($selectPrivateDashboard)->fetch();

        if ($dashboard) {
            $this->setTitle($this->translate('Edit Dashlet: %s'), $userDashlet->name);
        } else {
            $this->setTitle($this->translate('Edit Dashlet: %s'), $dashlet->name);
        }

        $form = (new EditDashletForm($dashlet, $userDashlet))
            ->on(EditDashletForm::ON_SUCCESS, function () {
                $this->redirectNow('dashboards/settings');
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addContent($form);
    }

    /**
     * Delete single dashboard with all it's dashlets and it's references
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
     * Remove individual dashlets and it's references from the given dashboard
     *
     * @throws \Icinga\Exception\MissingParameterException  If the parameter $dashletId|$dashboardId doesn't exist
     */
    public function removeAction()
    {
        $dashletId = $this->params->getRequired('dashletId');
        $dashboardId = $this->params->getRequired('dashboardId');

        $this->tabs->disableLegacyExtensions();

        $select = (new Select())
            ->from('dashlet')
            ->columns('*')
            ->where([
                'id = ?' => $dashletId,
                'dashboard_id = ?' => $dashboardId
            ]);

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
