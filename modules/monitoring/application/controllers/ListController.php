<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Web\Url;
use Icinga\Web\Hook;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabs;
use Icinga\Web\Widget\SortBox;
use Icinga\Web\Widget\FilterBox;
use Icinga\Web\Widget\Chart\HistoryColorGrid;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Widget;

class Monitoring_ListController extends Controller
{
    protected $url;

    /**
     * Retrieve backend and hooks for this controller
     *
     * @see ActionController::init
     */
    public function init()
    {
        $this->createTabs();
        $this->view->compact = $this->_request->getParam('view') === 'compact';
        $this->url = Url::fromRequest();
    }

    protected function hasBetterUrl()
    {
        $url = clone($this->url);
        if ($this->getRequest()->isPost()) {
            $q = $this->getRequest()->getPost('q');
        } else {
            $q = $url->shift('q');
        }
        if ($q) {
            list($k, $v) = preg_split('/=/', $q);
            $url->addParams(array($k => $v));
            return $url;
        }
        return false;
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
        if ($url = $this->hasBetterUrl()) {
            return $this->redirectNow($url);
        }

        // Handle soft and hard states
        $stateType = $this->params->shift('stateType', 'soft');
        if ($stateType == 'hard') {
            $stateColumn = 'host_hard_state';
            $stateChangeColumn = 'host_last_hard_state_change';
        } else {
            $stateColumn = 'host_state';
            $stateChangeColumn = 'host_last_state_change';
            $stateType = 'soft';
        }

        $this->addTitleTab('hosts');
        $this->setAutorefreshInterval(10);
        $query = $this->backend->select()->from('hostStatus', array_merge(array(
            'host_icon_image',
            'host_name',
            'host_state' => $stateColumn,
            'host_address',
            'host_acknowledged',
            'host_output',
            'host_in_downtime',
            'host_is_flapping',
            'host_state_type',
            'host_handled',
            'host_last_check',
            'host_last_state_change' => $stateChangeColumn,
            'host_notifications_enabled',
            // 'host_unhandled_service_count',
            'host_unhandled_services',
            'host_action_url',
            'host_notes_url',
            'host_last_comment',
            'host_last_ack',
            'host_last_downtime',
            'host_active_checks_enabled',
            'host_passive_checks_enabled',
            'host_current_check_attempt',
            'host_max_check_attempts'
        ), $this->extraColumns()));

        $this->applyFilters($query);

        $this->setupSortControl(array(
            'host_last_check'   => 'Last Check',
            'host_severity'     => 'Severity',
            'host_name'         => 'Hostname',
            'host_address'      => 'Address',
            'host_state'        => 'Current State',
            'host_state'        => 'Hard State'
        ));
        $this->view->hosts = $query->paginate();
    }

    /**
     * Display service list
     */
    public function servicesAction()
    {
        if ($url = $this->hasBetterUrl()) {
            return $this->redirectNow($url);
        }

        // Handle soft and hard states
        $stateType = $this->params->shift('stateType', 'soft');
        if ($stateType == 'hard') {
            $stateColumn = 'service_hard_state';
            $stateChangeColumn = 'service_last_hard_state_change';
        } else {
            $stateColumn = 'service_state';
            $stateChangeColumn = 'service_last_state_change';
            $stateType = 'soft';
        }

        $this->addTitleTab('services');
        $this->view->showHost = true;
        if ($host = $this->_getParam('host')) {
            if (strpos($host, '*') === false) {
                $this->view->showHost = false;
            }
        }
        $this->setAutorefreshInterval(10);
        
        $columns = array_merge(array(
            'host_name',
            'host_state',
            'host_state_type',
            'host_last_state_change',
            'host_address',
            'host_handled',
            'service_description',
            'service_display_name',
            'service_state' => $stateColumn,
            'service_in_downtime',
            'service_acknowledged',
            'service_handled',
            'service_output',
            'service_perfdata',
            'service_attempt',
            'service_last_state_change' => $stateChangeColumn,
            'service_icon_image',
            'service_is_flapping',
            'service_state_type',
            'service_handled',
            'service_severity',
            'service_last_check',
            'service_notifications_enabled',
            'service_action_url',
            'service_notes_url',
            'service_last_comment',
            'service_last_ack',
            'service_last_downtime',
            'service_active_checks_enabled',
            'service_passive_checks_enabled',
            'current_check_attempt' => 'service_current_check_attempt',
            'max_check_attempts'    => 'service_max_check_attempts'
        ), $this->extraColumns());
        $query = $this->backend->select()->from('serviceStatus', $columns);

        $this->applyFilters($query);
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
        $limit = $this->params->get('limit');
        $this->view->limit = $limit;
        if ($limit === 0) {
            $this->view->services = $query->getQuery()->fetchAll();
        } else {
            // TODO: Workaround, paginate should be able to fetch limit from new params
            $this->view->services = $query->paginate($this->params->get('limit'));
        }
    }

    /**
     * Fetch the current downtimes and put them into the view property `downtimes`
     */
    public function downtimesAction()
    {
        $this->addTitleTab('downtimes');
        $this->setAutorefreshInterval(12);
        $query = $this->backend->select()->from('downtime', array(
            'id'              => 'downtime_internal_id',
            'objecttype'      => 'downtime_objecttype',
            'comment'         => 'downtime_comment',
            'author'          => 'downtime_author',
            'start'           => 'downtime_start',
            'scheduled_start' => 'downtime_scheduled_start',
            'end'             => 'downtime_end',
            'duration'        => 'downtime_duration',
            'is_flexible'     => 'downtime_is_flexible',
            'is_fixed'        => 'downtime_is_fixed',
            'is_in_effect'    => 'downtime_is_in_effect',
            'entry_time'      => 'downtime_entry_time',
            'host'            => 'downtime_host',
            'service'         => 'downtime_service'
        ))->order('downtime_is_in_effect', 'DESC')
          ->order('downtime_scheduled_start', 'DESC');

        $this->applyFilters($query);
        $this->view->downtimes = $query->paginate();
        $this->setupSortControl(array(
            'downtime_is_in_effect'    => 'Is In Effect',
            'downtime_host'            => 'Host / Service',
            'downtime_entry_time'      => 'Entry Time',
            'downtime_author'          => 'Author',
            'downtime_start'           => 'Start Time',
            'downtime_start'           => 'End Time',
            'downtime_scheduled_start' => 'Scheduled Start',
            'downtime_scheduled_end'   => 'Scheduled End',
            'downtime_duration'        => 'Duration',
        ));
    }

    /**
     * Display notification overview
     */
    public function notificationsAction()
    {
        $this->addTitleTab('notifications');
        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from('notification', array(
            'host',
            'service',
            'notification_output',
            'notification_contact',
            'notification_start_time',
            'notification_state'
        ));
        $this->applyFilters($query);
        $this->view->notifications = $query->paginate();
        $this->setupSortControl(array(
            'notification_start_time' => 'Notification Start'
        ));
    }

    public function contactsAction()
    {
        $this->addTitleTab('contacts');
        $query = $this->backend->select()->from('contact', array(
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
        ));
        $this->applyFilters($query);
        $this->view->contacts = $query->paginate();

        $this->setupSortControl(array(
            'contact_name' => 'Name',
            'contact_alias' => 'Alias',
            'contact_email' => 'Email',
            'contact_pager' => 'Pager Address / Number',
            'contact_notify_service_timeperiod' => 'Service Notification Timeperiod',
            'contact_notify_host_timeperiod' => 'Host Notification Timeperiod'
        ));
    }

    public function statehistorysummaryAction()
    {
        $this->addTitleTab('statehistorysummary', 'Critical Events');
        $query = $this->backend->select()->from(
            'stateHistorySummary',
            array('day', 'cnt_critical')
        )->getQuery()->order('day');
        $query->limit(365);
        $this->view->summary = $query->fetchAll();
        $this->view->grid = new HistoryColorGrid();
        $this->handleFormatRequest($query);
    }

    public function contactgroupsAction()
    {
        $this->addTitleTab('contactgroups');
        $query = $this->backend->select()->from('contactgroup', array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
        ))->order('contactgroup_alias');
        $this->applyFilters($query);

        // Fetch and prepare all contact groups:
        $contactgroups = $query->getQuery()->fetchAll();
        $groupData = array();
        foreach ($contactgroups as $c) {
            if (!array_key_exists($c->contactgroup_name, $groupData)) {
                $groupData[$c->contactgroup_name] = array(
                    'alias'     => $c->contactgroup_alias,
                    'contacts'  => array()
                );
            }
            $groupData[$c->contactgroup_name]['contacts'][] = $c;
        }
        // TODO: Find a better naming
        $this->view->groupData = $groupData;
    }

    public function commentsAction()
    {
        $this->addTitleTab('comments');
        $this->setAutorefreshInterval(12);
        $query = $this->backend->select()->from('comment', array(
            'id'         => 'comment_internal_id',
            'objecttype' => 'comment_objecttype',
            'comment'    => 'comment_data',
            'author'     => 'comment_author',
            'timestamp'  => 'comment_timestamp',
            'type'       => 'comment_type',
            'persistent' => 'comment_is_persistent',
            'expiration' => 'comment_expiration',
            'host'       => 'comment_host',
            'service'    => 'comment_service'
        ));
        $this->applyFilters($query);
        $this->view->comments = $query->paginate();

        $this->setupSortControl(
            array(
                'comment_timestamp'  => 'Comment Timestamp',
                'comment_host'       => 'Host / Service',
                'comment_type'       => 'Comment Type',
                'comment_expiration' => 'Expiration',
            )
        );
    }

    public function servicegroupsAction()
    {
        $this->addTitleTab('servicegroups');
        $this->setAutorefreshInterval(12);
        $query = $this->backend->select()->from('groupsummary', array(
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
        ));
        $this->applyFilters($query);
        $this->view->servicegroups = $query->paginate();
        $this->setupSortControl(array(
            'servicegroup_name' => 'Servicegroup Name'
        ));
    }

    public function hostgroupsAction()
    {
        $this->addTitleTab('hostgroups');
        $this->setAutorefreshInterval(12);
        $query = $this->backend->select()->from('groupsummary', array(
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
        ));
        $this->applyFilters($query);
        $this->view->hostgroups = $query->paginate();
        $this->setupSortControl(array(
            'hostgroup_name' => 'Hostgroup Name'
        ));
    }

    public function eventhistoryAction()
    {
        $this->addTitleTab('eventhistory');
        $query = $this->backend->select()->from('eventHistory', array(
            'host_name',
            'service_description',
            'object_type',
            'timestamp',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type',
            'host',
            'service'
        ));

        $this->setupSortControl(array(
            'raw_timestamp' => 'Occurence'
        ));
        $this->applyFilters($query);
        $this->view->history = $query->paginate();
    }

    public function servicematrixAction()
    {
        $this->addTitleTab('servicematrix');
        $this->setAutorefreshInterval(15);
        $dataview = $this->backend->select()->from('serviceStatus', array(
            'host_name',
            'service_description',
            'service_state',
            'service_output',
            'service_handled'
        ));
        $this->applyFilters($dataview);
        $this->setupSortControl(array(
            'host_name'           => 'Hostname',
            'service_description' => 'Service description'
        ));
        $pivot = $dataview->pivot('service_description', 'host_name');
        $this->view->pivot = $pivot;
        $this->view->horizontalPaginator = $pivot->paginateXAxis();
        $this->view->verticalPaginator   = $pivot->paginateYAxis();
    }

    protected function applyFilters($query)
    {
        $params = clone $this->params;
        $request = $this->getRequest();

        $limit   = $params->shift('limit');

        $sort = null;
        $dir = null;
        if ($request->isPost()) {
            $sort = $request->getPost('sort', null);
            $dir  = $request->getPost('dir', null);
        }
        $sort    = $params->shift('sort', $sort);
        $dir     = $params->shift('dir', $dir);
        $page    = $params->shift('page');
        $format  = $params->shift('format');
        $view    = $params->shift('view');
        $backend = $params->shift('backend');
        $modifyFilter = $params->shift('modifyFilter', false);
        $removeFilter = $params->shift('removeFilter', false);

        $filter = Filter::fromQueryString((string) $params);
        $this->view->filterPreview = Widget::create('filterWidget', $filter);

        if ($removeFilter) {
            $redirect = $this->url->without('page');
            if ($filter->getById($removeFilter)->isRootNode()) {
                $redirect->setQueryString('');
            } else {
                $filter->removeId($removeFilter);
                $redirect->setQueryString($filter->toQueryString())
                    ->getParams()->add('modifyFilter');
            }
            $this->redirectNow($redirect);
        }

        if ($modifyFilter) {
            if ($this->_request->isPost()) {
                $filter = $filter->applyChanges($_POST);
                $this->redirectNow($this->url->without('page')->setQueryString($filter->toQueryString()));
            }
            $this->view->filterEditor = Widget::create('filterEditor', array(
                'filter' => $filter,
                'query'  => $query
            ));
        }
        if (! $filter->isEmpty()) {
            $query->applyFilter($filter);
        }
        $this->view->filter = $filter;
        if ($sort) {
            $query->order($sort, $dir);
        }
        $this->applyRestrictions($query);
        $this->handleFormatRequest($query);
        return $query;
    }

    /**
     * Apply current user's `monitoring/filter' restrictions on the given data view
     *
     * @param   DataView $dataView
     *
     * @return  DataView
     */
    protected function applyRestrictions($query)
    {
        foreach ($this->getRestrictions('monitoring/filter') as $restriction) {
            // TODO: $query->applyFilter(Filter::fromQueryString());
        }
        return $query;
    }

    protected function extraColumns()
    {
        $columns = preg_split(
            '~,~',
            $this->params->shift('addcolumns', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $this->view->extraColumns = $columns;
        return $columns;
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

    protected function addTitleTab($action, $title = false)
    {
        $title = $title ?: ucfirst($action);
        $this->getTabs()->add($action, array(
            'title' => $title,
            // 'url' => Url::fromPath('monitoring/list/' . $action)
            'url' => $this->url
        ))->activate($action);
        $this->view->title = $title;
    }

    /**
     * Return all tabs for this controller
     *
     * @return Tabs
     */
    private function createTabs()
    {
        $tabs = $this->getTabs();
        if (in_array($this->_request->getActionName(), array(
            'hosts',
            'services',
            'eventhistory',
            'notifications'
        ))) {
            $tabs->extend(new OutputFormat())->extend(new DashboardAction());
        }
    }
}
