<?php
/**
 * Icinga (http://www.icinga.org)
 *
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.icinga.org/license/gpl2 GPL, version 2
 */

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Icinga;
use Icinga\Web\Widget;
use Icinga\Web\Url;

/**
 * Search controller
 */
class SearchController extends ActionController
{
    public function indexAction()
    {
        $search = $this->_request->getParam('q');
        $dashboard = Widget::create('dashboard')->createPane('Search');
        $pane = $dashboard->getPane('Search');
        $pane->addComponent('Hosts', Url::fromPath('monitoring/list/hosts', array(
            'host_name' => $search . '*',
            'sort' => 'host_severity',
            'limit' => 10,
        )));
        $pane->addComponent('Services', Url::fromPath('monitoring/list/services', array(
            'service_description' => '*' . $search . '*',
            'sort' => 'service_severity',
            'limit' => 10,
        )));
        $pane->addComponent('Hostgroups', Url::fromPath('monitoring/list/hostgroups', array(
            'hostgroup' => '*' . $search . '*',
            'limit' => 10,
        )));
        $dashboard->activate('Search');
        $this->view->dashboard = $dashboard;
        $this->view->tabs = $dashboard->getTabs();
    }
}
