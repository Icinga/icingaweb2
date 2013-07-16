<?php

use Icinga\Web\ModuleActionController;
use Icinga\Web\Hook;
use Icinga\File\Csv;
use Monitoring\Backend;
use Icinga\Application\Benchmark;

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
        Benchmark::measure("hostsAction::query()");
        $this->view->hosts = $this->backend->select()->from(
            'status',
            array(
                'host_icon_image',
                'host_name',
                'host_state',
                'host_address',
                'host_acknowledged',
                'host_output',
                'host_in_downtime',
                'host_is_flapping',
                'host_state_type',
                'host_handled',
                'host_last_state_change',
                'host_notifications_enabled',
                'host_unhandled_service_count',
                'host_action_url',
                'host_notes_url',
                'host_last_comment'
            )
        );
          
        if ($search = $this->_getParam('search')) {
            $this->_setParam('search', null);
            if (strpos($search, '=') === false) {
                $this->_setParam('host_name', $search);
            } else {
                list($key, $val) = preg_split('~\s*=\s*~', $search, 2);
                if ($this->view->hosts->isValidFilterColumn($key) || $key[0] === '_') {
                    $this->_setParam($key, $val);
                }
            }
        }

        //$this->view->hosts->getQuery()->group('host_id');
        if ($this->_getParam('dump') === 'sql') {
            echo '<pre>' . htmlspecialchars(wordwrap($this->view->hosts->getQuery()->dump())) . '</pre>';
            exit;
        }

        if ($this->_getParam('view') === 'compact') {
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
