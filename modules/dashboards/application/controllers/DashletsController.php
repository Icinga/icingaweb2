<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Form\NewDashletsForm;
use Icinga\Module\Dashboards\Model\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\DashletWidget;
use ipl\Sql\Select;

class DashletsController extends Controller
{
    use Database;

    private $data;

    public function indexAction()
    {
        $selectDashboard = (new Select())
            ->columns('*')
            ->from('dashboard');

        $this->data = $this->getDb()->select($selectDashboard);

        foreach ($this->data as $dashboard) {
            $this->setTitle($this->translate($dashboard['name']));
        }

        $divContent = $this->content;
        $divContent->setAttributes(['class' => 'dashboard content']);

        $selectDashlet = (new Select())
            ->columns('dashlet.name, dashlet.url')
            ->from('dashlet')
            ->join('dashboard d', 'dashlet.dashboard_id= d.id');

        $this->data = $this->getDb()->select($selectDashlet);
        $dashlets = new DashletWidget($this->data);

        $this->addContent($dashlets);
    }

    public function newAction()
    {
        $this->setTitle('New Dashlet');

        $dashletsForm = new NewDashletsForm();
        $this->addContent($dashletsForm);

        if ($this->getRequest()->isPost()) {
            $this->data = [
                'dashboard_id' => $this->getRequest()->getParam('select_dashboard'),
                'name'  => $this->getRequest()->getParam('dashlet_name'),
                'url'   => $this->getRequest()->getParam('dashlet_url'),
            ];

            try {
               $this->getDb()->insert('dashlet', $this->data);

                $this->redirectNow('dashboards/dashlets');
            } catch (\Error $error) {
                echo ("couldn't be insert to dashlet " . $error->getMessage());
            }
        }
    }
}
