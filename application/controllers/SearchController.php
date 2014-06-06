<?php
// @codeCoverageIgnoreStart

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
        $this->setAutorefreshInterval(10);
        $search = $this->_request->getParam('q');
        if (! $search) {
            $this->view->tabs = Widget::create('tabs')->add(
                'search',
                array(
                    'title' => $this->translate('Search'),
                    'url'   => '/search',
                )
            )->activate('search');
            $this->render('hint');
            return;
        }
        $dashboard = Widget::create('dashboard')->createPane($this->translate('Search'));
        $pane = $dashboard->getPane($this->translate('Search'));
        $suffix = strlen($search) ? ': ' . rtrim($search, '*') . '*' : '';
        $pane->addComponent(
            $this->translate('Hosts') . $suffix,
            Url::fromPath('monitoring/list/hosts', array(
                'host_name' => $search . '*',
                'sort' => 'host_severity',
                'limit' => 10,
            )
        ));
        $pane->addComponent(
            $this->translate('Services') . $suffix,
            Url::fromPath('monitoring/list/services', array(
                'service_description' => $search . '*',
                'sort' => 'service_severity',
                'limit' => 10,
            )
        ));
        $pane->addComponent('Hostgroups' . $suffix, Url::fromPath('monitoring/list/hostgroups', array(
            'hostgroup' => $search . '*',
            'limit' => 10,
        )));
        $pane->addComponent('Servicegroups' . $suffix, Url::fromPath('monitoring/list/servicegroups', array(
            'servicegroup' => $search . '*',
            'limit' => 10,
        )));
        $dashboard->activate($this->translate('Search'));
        $this->view->dashboard = $dashboard;
        $this->view->tabs = $dashboard->getTabs();
    }
}
// @codeCoverageIgnoreEnd
