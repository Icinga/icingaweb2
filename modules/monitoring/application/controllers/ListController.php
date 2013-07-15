<?php

use Icinga\Web\ModuleActionController;
use Icinga\Web\Hook;
use Icinga\File\Csv;
use Monitoring\Backend;

class Monitoring_ListController extends ModuleActionController
{
    protected $backend;

    public function init()
    {
        $this->view->tabs = $this->getTabs()
             ->activate($this->action_name)
             ->enableSpecialActions();
        $this->backend = Backend::getInstance($this->_getParam('backend'));
        $this->view->grapher = Hook::get('grapher');
    }

    public function hostsAction()
    {
        $this->view->hosts = $this->query('hoststatus', array(
            'host_name',
            'host_state',
            'host_acknowledged',
            'host_output',
            'host_in_downtime',
            'host_handled',
            'host_last_state_change'
        ));
        $this->view->sort = $this->_getParam('sort');
        $this->preserve('sort')->preserve('backend');
        if ($this->view->compact) {
            $this->_helper->viewRenderer('hosts_compact');
        }
    }

    public function servicesAction()
    {
        $state_type = $this->_getParam('_statetype', 'soft');
        if ($state_type = 'soft') {
            $state_column = 'service_state';
            $state_change_column = 'service_last_state_change';
        } else {
            $state_column = 'service_hard_state';
            $state_change_column = 'service_last_hard_state_change';
        }

        $this->view->services = $this->query('status', array(
            'host_name',
            'host_problems',
            'service_description',
            'service_state' => $state_column,
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_last_state_change' => $state_change_column
        ));
        $this->preserve('sort')
             ->preserve('backend')
             ->preserve('extracolumns');
        $this->view->sort = $this->_getParam('sort');
        if ($this->view->compact) {
            $this->_helper->viewRenderer('services-compact');
        }
    }

    public function hostgroupsAction()
    {
        $this->view->hostgroups = $this->backend->select()
            ->from('hostgroup', array(
            'hostgroup_name',
            'hostgroup_alias',
        ))->applyRequest($this->_request);
    }

    public function servicegroupsAction()
    {
        $this->view->servicegroups = $this->backend->select()
            ->from('servicegroup', array(
            'servicegroup_name',
            'servicegroup_alias',
        ))->applyRequest($this->_request);
    }

    public function contactgroupsAction()
    {
        $this->view->contactgroups = $this->backend->select()
            ->from('contactgroup', array(
            'contactgroup_name',
            'contactgroup_alias',
        ))->applyRequest($this->_request);
    }

    public function contactsAction()
    {
        $this->view->contacts = $this->backend->select()
            ->from('contact', array(
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager'
        ))->applyRequest($this->_request);
    }

    // TODO: Search helper playground
    public function searchAction()
    {
        $data = array(
            'service_description',
            'service_state',
            'service_acknowledged',
            'service_handled',
            'service_output',
            // '_host_satellite',
            'service_last_state_change'
            );
        echo json_encode($data);
        exit;
    }

    protected function query($view, $columns)
    {
        $extra = preg_split(
            '~,~',
            $this->_getParam('extracolumns', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $this->view->extraColumns = $extra;
        $query = $this->backend->select()
            ->from($view, array_merge($columns, $extra))
            ->applyRequest($this->_request);
        $this->handleFormatRequest($query);
        return $query;
    }

    protected function handleFormatRequest($query)
    {
        if ($this->_getParam('format') === 'sql') {
            echo '<pre>'
                . htmlspecialchars(wordwrap($query->getQuery()->dump()))
                . '</pre>';
            exit;
        }
        if ($this->_getParam('format') === 'json'
            || $this->_request->getHeader('Accept') === 'application/json')
        {
            header('Content-type: application/json');
            echo json_encode($query->fetchAll());
            exit;
        }
        if ($this->_getParam('format') === 'csv'
            || $this->_request->getHeader('Accept') === 'text/csv') {
            Csv::fromQuery($query)->dump();
            exit;
        }
    }

    protected function getTabs()
    {
        $tabs = $this->widget('tabs');
        $tabs->add('services', array(
            'title'     => 'All services',
            'icon'      => 'img/classic/service.png',
            'url'       => 'monitoring/list/services',
        ));
        $tabs->add('hosts', array(
            'title'     => 'All hosts',
            'icon'      => 'img/classic/server.png',
            'url'       => 'monitoring/list/hosts',
        ));
/*
        $tabs->add('hostgroups', array(
            'title'     => 'Hostgroups',
            'icon'      => 'img/classic/servers-network.png',
            'url'       => 'monitoring/list/hostgroups',
        ));
        $tabs->add('servicegroups', array(
            'title'     => 'Servicegroups',
            'icon'      => 'img/classic/servers-network.png',
            'url'       => 'monitoring/list/servicegroups',
        ));
        $tabs->add('contacts', array(
            'title'     => 'Contacts',
            'icon'      => 'img/classic/servers-network.png',
            'url'       => 'monitoring/list/contacts',
        ));
        $tabs->add('contactgroups', array(
            'title'     => 'Contactgroups',
            'icon'      => 'img/classic/servers-network.png',
            'url'       => 'monitoring/list/contactgroups',
        ));
*/
        return $tabs;
    }
}
