<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use Icinga\Module\Dashboards\Forms\DashboardsForm;
use Icinga\Web\Notification;

class DashletForm extends DashboardsForm
{
    use Database;

    /**
     * Display the FormElement for creating a new dashboards and dashlets
     */
    public function newAction()
    {
        $this->displayForm();

        $this->addElement('submit', 'submit', [
            'label' => 'Add To Dashboard'
        ]);
    }

    protected function assemble()
    {
        $this->add($this->newAction());
    }

    protected function onSuccess()
    {
        if ($this->getValue('user-dashboard') !== null) {
            if ($this->getValue('new-dashboard-name') !== null) {
                $data = [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'user_name' => Auth::getInstance()->getUser()->getUsername()
                ];

                $this->getDb()->insert('user_dashboard', $data);

                $this->getDb()->insert('user_dashlet', [
                    'dashlet_id' => $this->createUserDashlet($this->getValue('new-dashboard-name')),
                    'user_dashboard_id' => $this->fetchUserDashboardId($this->getValue('new-dashboard-name'))
                ]);

                Notification::success("Private dashboard and dashlet created");
            } else {
                if (! $this->checkForPrivateDashboard($this->getValue('dashboard'))) {
                    $this->getDb()->insert('user_dashboard', [
                        'dashboard_id' => $this->getValue('dashboard'),
                        'user_name' => Auth::getInstance()->getUser()->getUsername()
                    ]);

                    $this->getDb()->insert('user_dashlet', [
                        'dashlet_id' => $this->createUserDashlet($this->getValue('dashboard')),
                        'user_dashboard_id' => $this->fetchUserDashboardId($this->getValue('dashboard'))
                    ]);

                    Notification::success("Private dashlet in a public dashboard created!");
                } else {
                    $this->getDb()->insert('user_dashlet', [
                        'dashlet_id' => $this->createUserDashlet($this->getValue('dashboard')),
                        'user_dashboard_id' => $this->fetchUserDashboardId($this->getValue('dashboard'))
                    ]);

                    Notification::success("Private dashlet created");
                }
            }
        } elseif ($this->checkForPrivateDashboard($this->getValue('dashboard'))) {
            Notification::error("You can't create public dashlet in a private dashboard!");
        } elseif (Auth::getInstance()->getUser()->isMemberOf('admin')) {
            if ($this->getValue('new-dashboard-name') !== null) {
                $this->getDb()->insert('dashlet', [
                    'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url')
                ]);

                Notification::success('Dashboard and dashlet created');
            } else {
                $this->getDb()->insert('dashlet', [
                    'dashboard_id' => $this->getValue('dashboard'),
                    'name' => $this->getValue('name'),
                    'url' => $this->getValue('url'),
                ]);

                Notification::success('Dashlet created');
            }
        } else {
            Notification::error("You don't have a permission!");
        }
    }
}
