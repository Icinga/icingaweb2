<?php

namespace Icinga\Module\Dashboards\Controllers;

use Icinga\Module\Dashboards\Model\DashboardsModel;
use Icinga\Module\Dashboards\Model\Database;
use Icinga\Module\Dashboards\Web\Controller;
use Icinga\Module\Dashboards\Web\Widget\DashboardsWidget;

class DashboardsController extends Controller
{
    use Database;

    private $compact;

    public function indexAction()
    {
        $this->getTabs()->add(uniqid(), [
            'label' => 'Current Incidents',
            'active' => true,
            'url' => $this->getRequest()->getUrl()
        ]);

        $divContent = $this->content;
        $divContent->setAttribute('class', 'dashboard content');

        $dashlets = DashboardsModel::on($this->getDb());
        $dashlets->getSelectBase()->join('dashboards d', 'dashlet.dashboard_id= d.id WHERE d.name="Icinga"');

        $dashlet = new DashboardsWidget($dashlets);
        $this->addContent($dashlet);
    }

    public function updateAction()
    {
        // Here we pass parameter $postId that we have defined in module.js
        $postId = $_POST['postIds'];
        // now create an array of our ids with the function explode()
        $ids = explode(',', $postId);
        $priority = count($ids);
        if ($priority > 0) {
            foreach ($ids as $id) {
                $this->getDb()->update(
                    'dashlet',
                    ['priority' => $priority--],
                    ['id = ?' => $id]
                );
            }
        }
    }

    public function styleAction()
    {
        $id = $_POST['ids'];
        $savedWidth = $_POST['DB_width'];

        if ($savedWidth > 66.6) {
            $this->getDb()->update(
                'dashlet',
                ['style_width' => 99.9],
                ['id = ?' => $id]
            );
        } elseif ($savedWidth > 33.3 && $savedWidth < 66.6) {
            $this->getDb()->update(
                'dashlet',
                ['style_width' => 66.6],
                ['id = ?' => $id]
            );
        } elseif ($savedWidth <= 33.3) {
            $this->getDb()->update(
                'dashlet',
                ['style_width' => 33.3],
                ['id = ?' => $id]
            );
        } else {
            echo 'style_width could not be saved Successfully';
        }
    }
}
