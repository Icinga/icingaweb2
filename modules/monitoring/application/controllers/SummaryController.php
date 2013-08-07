<?php

use Icinga\Web\ModuleActionController;
use Icinga\Backend;

class Monitoring_SummaryController extends ModuleActionController
{
    protected $backend;
    protected $host;
    protected $service;

    public function init()
    {
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $this->view->compact = $this->_getParam('view') === 'compact';
        $this->view->tabs = $this->getTabs();
    }

    protected function getTabs()
    {
        $tabs = $this->widget('tabs');
        $tabs->add('hostgroup', array(
            'title'     => 'Hostgroups',
            'url'       => 'monitoring/summary/group',
            'urlParams' => array('by' => 'hostgroup'),
        ));
        $tabs->add('servicegroup', array(
            'title'     => 'Servicegroups',
            'url'       => 'monitoring/summary/group',
            'urlParams' => array('by' => 'servicegroup'),
        ));
        $tabs->activate($this->_getParam('by', 'hostgroup'));
        return $tabs;
    }

    public function historyAction()
    {
        $this->_helper->viewRenderer('history');
    }

    public function groupAction()
    {
        if ($this->_getParam('by') === 'servicegroup') {
            $view = 'servicegroupsummary';
        } else {
            $view = 'hostgroupsummary';
        }
        if (! $this->backend->hasView($view)) {
            $this->view->backend = $this->backend;
            $this->view->view_name = $view;
            $this->_helper->viewRenderer('backend-is-missing');
            return;
        }

        $this->view->preserve = array(
            'problems' => $this->_getParam('problems') ? 'true' : 'false',
            'search'   => $this->_getParam('search')
        );
        $query = $this->backend->select()->from($view);
        $query->where('problems', $this->_getParam('problems') ? 'true' : 'false');
        //$query->where('ss.current_state > 0');
        $query->where('search', $this->_getParam('search'));

        // echo '<pre>' . $query->dump() . '</pre>'; exit;
        $this->view->summary = $query->paginate();

    }

}
