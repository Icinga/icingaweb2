<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Forms\DashboardsForm;
use Icinga\Web\Notification;

class EditDashletForm extends DashboardsForm
{
    use Database;

    /** @var object $dashlet of the selected dashboard */
    protected $dashlet;

    protected $userDashlet;

    /**
     * get a dashlet based on the current dashboard / the activated dashboard
     *
     * and populate it's details to the dashlet form to be edited dashlet or dashboard
     *
     * @param null $dashlet
     *
     * @param null $userDashlet
     */
    public function __construct($dashlet = null, $userDashlet = null)
    {
        $this->dashlet = $dashlet;
        $this->userDashlet = $userDashlet;

        if (! empty($dashlet)) {
            $this->populate([
                'url' => $dashlet->url,
                'name' => $dashlet->name
            ]);
        }

        if (! empty($userDashlet)) {
            $this->populate([
                'url' => $userDashlet->url,
                'name' => $userDashlet->name,
            ]);
        }
    }

    /**
     * Display the FormElement for editing dashboards and dashlets
     */
    public function editAction()
    {
        $this->displayForm();

        $this->addElement('submit', 'submit', [
            'label' => 'Update Dashboard'
        ]);
    }

    protected function assemble()
    {
        $this->add($this->editAction());
    }

    protected function onSuccess()
    {
        if (! empty($this->userDashlet) && $this->checkForPrivateDashboard($this->getValue('dashboard'))) {
            if (! empty($this->getValue('new-dashboard-name'))) {
                $this->getDb()->update('dashlet', [
                    'dashboard_id' => $this->fetchUserDashboardId($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ], ['id = ?' => $this->userDashlet->id]);

                $this->getDb()->update('user_dashlet', [
                    'dashlet_id' => $this->userDashlet->id,
                    'user_dashboard_id' => $this->fetchUserDashboardId($this->getValue('new-dashboard-name'))
                ]);

                Notification::success('Private Dashboard created & dashlet updated');
            } else {
                $this->getDb()->update('dashlet', [
                    'dashboard_id' => $this->fetchUserDashboardId($this->getValue('dashboard')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url'),
                ], ['id = ?' => $this->userDashlet->id]);

                Notification::success('Private dashlet updated');
            }
        }

        if (! empty($this->dashlet)) {
            if (! empty($this->getValue('new-dashboard-name'))) {
                $this->getDb()->update('dashlet', [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ], ['id = ?' => $this->dashlet->id]);

                Notification::success('Dashboard created & dashlet updated');
            } else {
                $this->getDb()->update('dashlet', [
                    'dashboard_id' => $this->getValue('dashboard'),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url'),
                ], ['id = ?' => $this->dashlet->id]);

                Notification::success('Dashlet updated');
            }
        }
    }
}
