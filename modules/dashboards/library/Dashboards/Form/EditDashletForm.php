<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Module\Dashboards\Common\Database;
use Icinga\Web\Notification;

class EditDashletForm extends DashletForm
{
    use Database;

    /** @var object $dashlet of the selected dashboard */
    protected $dashlet;

    /**
     * get a dashlet based on the current dashboard / the activated dashboard
     *
     * and populate it's details to the dashlet form to be edited dashlet or dashboard
     *
     * @param $dashlet
     */
    public function __construct($dashlet)
    {
        $this->dashlet = $dashlet;

        $this->populate($dashlet);
    }

    /**
     * Display the FormElement for editing dashboards and dashlets
     */
    public function editAction()
    {
        $this->newAction();
    }

    protected function assemble()
    {
        $this->add($this->editAction());
    }

    protected function onSuccess()
    {
        if (!empty($this->getValue('new-dashboard-name'))) {
            $this->getDb()->update('dashlet', [
                'dashboard_id' => $this->createDashboard($this->getValue('new-dashboard-name')),
                'name' => $this->getValue('name'),
                'url' => $this->getValue('url')
            ]);

            Notification::success('Dashboard created & dashlet updated');
        } else {
            $this->getDb()->update('dashlet', [
                'dashboard_id' => $this->getValue('dashboard'),
                'name' => $this->getValue('name'),
                'url' => $this->getValue('url'),
            ]);

            Notification::success('Dashlet updated');
        }
    }
}
