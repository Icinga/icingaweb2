<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Benchmark;
use Icinga\Data\Db\Query;
use Icinga\File\Csv;
use Icinga\Module\Monitoring\Controller as MonitoringController;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabs;
use Icinga\Module\Monitoring\Backend;
use Icinga\Web\Widget\SortBox;
use Icinga\Web\Widget\FilterBox;
use Icinga\Application\Config as IcingaConfig;

use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Module\Monitoring\DataView\Notification as NotificationView;
use Icinga\Module\Monitoring\DataView\Downtime as DowntimeView;
use Icinga\Module\Monitoring\DataView\Contact as ContactView;
use Icinga\Module\Monitoring\DataView\Contactgroup as ContactgroupView;
use Icinga\Module\Monitoring\DataView\HostStatus as HostStatusView;
use Icinga\Module\Monitoring\DataView\ServiceStatus as ServiceStatusView;
use Icinga\Module\Monitoring\DataView\Comment as CommentView;
use Icinga\Module\Monitoring\DataView\Groupsummary as GroupsummaryView;
use Icinga\Module\Monitoring\DataView\EventHistory as EventHistoryView;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Module\Monitoring\DataView\ServiceStatus;
use Icinga\Filter\Filterable;

class Monitoring_ListController extends MonitoringController
{
    /**
     * The backend used for this controller
     *
     * @var Backend
     */
    protected $backend;
    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->backend = Backend::createBackend($this->_getParam('backend'));
        $this->view->grapher = Hook::get('grapher');
        $this->createTabs();
        $this->view->activeRowHref = $this->getParam('detail');

        /*
        $contextSwitch = $this->_helper->getHelper('DataFormatSwitch');
        $contextSwitch = $this->_helper->getHelper('ContextSwitch');
        $contextSwitch->addActionContext(true, 'json');
        $contextSwitch->setAutoJsonSerialization(true);
        $contextSwitch->initContext();
        $contextSwitch->addActionContext(true, 'xml');
        $contextSwitch->addActionContext(true, 'pdf');
        */
    }

    /**
     * Overwrite the backend to use (used for testing)
     *
     * @param Backend $backend      The Backend that should be used for querying
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Display host list
     */
    public function hostsAction()
    {

        $this->compactView = 'hosts-compact';
        $dataview = HostStatusView::fromRequest(
            $this->_request,
            array(
                'host_icon_image',
                'host_name',
                'host_state',
                'host_address',
                'host_acknowledged',
                'host_output',
                'host_long_output',
                'host_in_downtime',
                'host_is_flapping',
                'host_state_type',
                'host_handled',
                'host_last_check',
                'host_last_state_change',
                'host_notifications_enabled',
                'host_unhandled_service_count',
                'host_action_url',
                'host_notes_url',
                'host_last_comment',
                'host_active_checks_enabled',
                'host_passive_checks_enabled',
                'host_current_check_attempt',
                'host_max_check_attempts'
            )
        );
        $query = $dataview->getQuery();
        $this->applyRestrictions($query);

        $this->setupFilterControl($dataview, 'host');

        $this->setupSortControl(array(
            'host_last_check'   => 'Last Host Check',
            'host_severity'     => 'Host Severity',
            'host_state'        => 'Current Host State',
            'host_name'         => 'Host Name',
            'host_address'      => 'Address',
            'host_state'        => 'Hard State'
        ));
        $this->handleFormatRequest($query);
        $this->view->hosts = $query->paginate();
    }

    /**
     * Display service list
     */
    public function servicesAction()
    {
        $this->compactView = 'services-compact';
        $query = $this->fetchServices();
        $this->applyRestrictions($query);
        $this->view->services = $query->paginate();

        $this->setupFilterControl(ServiceStatus::fromRequest($this->getRequest()), 'service');
        $this->setupSortControl(array(
            'service_last_check'    =>  'Last Service Check',
            'service_severity'      =>  'Severity',
            'service_state'         =>  'Current Service State',
            'service_description'   =>  'Service Name',
            'service_state_type'    =>  'Hard State',
            'host_severity'         =>  'Host Severity',
            'host_state'            =>  'Current Host State',
            'host_name'             =>  'Host Name',
            'host_address'          =>  'Host Address',
            'host_last_check'       =>  'Last Host Check'
        ));

    }

    /**
     * Fetch the current downtimes and put them into the view property `downtimes`
     */
    public function downtimesAction()
    {
        $query = DowntimeView::fromRequest($this->_request)->getQuery();

        $this->view->downtimes = $query->paginate();
        $this->setupSortControl(array(
            'downtime_is_in_effect'         => 'Is In Effect',
            'object_type'                   => 'Service/Host',
            'host_name'                     => 'Host Name',
            'service_description'           => 'Service Name',
            'downtime_entry_time'           => 'Entry Time',
            'downtime_author_name'          => 'Author',
            'downtime_comment_data'         => 'Comment',
            'downtime_scheduled_start_time' => 'Start',
            'downtime_scheduled_end_time'   => 'End',
            'downtime_trigger_time'         => 'Trigger Time',
            'downtime_internal_downtime_id' => 'Downtime ID',
            'downtime_duration'             => 'Duration',
        ));
        $this->handleFormatRequest($query);
    }

    /**
     * Display notification overview
     */
    public function notificationsAction()
    {
        $query = NotificationView::fromRequest($this->_request)->getQuery();
        $this->view->notifications = $query->paginate();
        $this->setupSortControl(array(
            'notification_start_time' => 'Notification Start'
        ));
        $this->handleFormatRequest($query);
    }

    public function contactsAction()
    {
        $query = ContactView::fromRequest(
            $this->_request,
            array(
                'contact_name',
                'contact_id',
                'contact_alias',
                'contact_email',
                'contact_pager',
                'contact_notify_service_timeperiod',
                'contact_notify_service_recovery',
                'contact_notify_service_warning',
                'contact_notify_service_critical',
                'contact_notify_service_unknown',
                'contact_notify_service_flapping',
                'contact_notify_service_downtime',
                'contact_notify_host_timeperiod',
                'contact_notify_host_recovery',
                'contact_notify_host_down',
                'contact_notify_host_unreachable',
                'contact_notify_host_flapping',
                'contact_notify_host_downtime',
            )
        )->getQuery();
        $this->view->contacts = $query->paginate();
        $this->setupSortControl(array(
            'contact_name' => 'Name',
            'contact_alias' => 'Alias',
            'contact_email' => 'Email',
            'contact_pager' => 'Pager Address / Number',
            'contact_notify_service_timeperiod' => 'Service Notification Timeperiod',
            'contact_notify_host_timeperiod' => 'Host Notification Timeperiod'
        ));
        $this->handleFormatRequest($query);
    }

    public function contactgroupsAction()
    {
         $query = ContactgroupView::fromRequest(
            $this->_request,
            array(
                'contactgroup_name',
                'contactgroup_alias',
                'contact_name'
            )
        )->getQuery();
        $this->view->contactgroups = $query->paginate();
        $this->setupSortControl(array(
            'contactgroup_name' => 'Group Name',
            'contactgroup_alias' => 'Group Alias'
        ));
        $this->handleFormatRequest($query);
    }

    public function commentsAction()
    {
        $query = CommentView::fromRequest(
            $this->_request,
            array(
                'comment_objecttype_id',
                'comment_id',
                'comment_data',
                'comment_author',
                'comment_timestamp',
                'comment_type',
                'comment_is_persistent',
                'comment_expiration_timestamp',
                'host_name',
                'service_name'
            )
        )->getQuery();

        $this->view->comments = $query->paginate();

        $this->setupSortControl(
            array(
                'comment_timestamp' => 'Comment Timestamp',
                'host_service'      => 'Host and Service',
                'comment_id'        => 'Comment Id',
                'comment_expires'   => 'Expiration Timestamp',
                'comment_type'      => 'Comment Type'
            )
        );
        $this->handleFormatRequest($query);
    }

    public function servicegroupsAction()
    {
        $query = GroupsummaryView::fromRequest(
            $this->_request,
            array(
                'servicegroup',
                'hosts_up',
                'hosts_unreachable_handled',
                'hosts_unreachable_unhandled',
                'hosts_down_handled',
                'hosts_down_unhandled',
                'hosts_pending',
                'services_ok',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_pending'
            )
        )->getQuery();
        $this->handleFormatRequest($query);
        $this->view->servicegroups = $query->paginate();
        $this->setupSortControl(array(
            'servicegroup_name' => 'Servicegroup Name'
        ));
    }

    public function hostgroupsAction()
    {
        $query = GroupsummaryView::fromRequest(
            $this->_request,
            array(
                'hostgroup',
                'hosts_up',
                'hosts_unreachable_handled',
                'hosts_unreachable_unhandled',
                'hosts_down_handled',
                'hosts_down_unhandled',
                'hosts_pending',
                'services_ok',
                'services_unknown_handled',
                'services_unknown_unhandled',
                'services_critical_handled',
                'services_critical_unhandled',
                'services_warning_handled',
                'services_warning_unhandled',
                'services_pending'
            )
        )->getQuery();
        $this->applyRestrictions($query);
        $this->handleFormatRequest($query);
        $this->view->hostgroups = $query->paginate();
        $this->setupSortControl(array(
            'hostgroup_name' => 'Hostgroup Name'
        ));
    }

    public function eventhistoryAction()
    {
        $query = EventHistoryView::fromRequest(
            $this->getRequest(),
            array(
                'host_name',
                'service_description',
                'object_type',
                'timestamp',
                'raw_timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type',
                'host',
                'service'
            )
        )->getQuery();
        $this->handleFormatRequest($query);
        $this->view->history = $query->paginate();
        $this->setupSortControl(
            array()
        );
    }

    /**
     * Apply current users monitoring/filter restrictions to the given query
     *
     * @param $query  Filterable  Query that should be filtered
     * @return Filterable
     */
    protected function applyRestrictions(Filterable $query)
    {
        foreach ($this->getRestrictions('monitoring/filter') as $restriction) {
            parse_str($restriction, $filter);
            foreach ($filter as $k => $v) {
                if ($query->isValidFilterTarget($k)) {
                    // TODO: This is NOT enough. We need to fix filters and get
                    // applyAuthFilters back.
                    $query->where($k, $v);
                }
            }
        }
        return $query;
    }

    /**
     * Create a sort control box at the 'sortControl' view parameter
     *
     * @param array $columns    An array containing the sort columns, with the
     *                          submit value as the key and the value as the label
     */
    private function setupSortControl(array $columns)
    {
        $this->view->sortControl = new SortBox(
            $this->getRequest()->getActionName(),
            $columns
        );
        $this->view->sortControl->applyRequest($this->getRequest());
    }

    private function setupFilterControl(Filterable $dataview, $domain)
    {
        $parser = new UrlViewFilter($dataview);
        $this->view->filterBox = new FilterBox(
            $parser->fromRequest($this->getRequest()),
            $domain,
            'monitoring'
        );

    }

    /**
     * Return all tabs for this controller
     *
     * @return Tabs
     */
    private function createTabs()
    {
        $tabs = $this->getTabs();
        $tabs->extend(new OutputFormat())
             ->extend(new DashboardAction());
    }
}
// @codingStandardsIgnoreEnd
