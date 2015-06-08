<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Backend;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabs;
use Icinga\Data\Filter\Filter;
use Icinga\Web\Widget;
use Icinga\Module\Monitoring\Forms\StatehistoryForm;
use Icinga\Module\Monitoring\DataView\DataView;

class Monitoring_ListController extends Controller
{
    /**
     * @see ActionController::init
     */
    public function init()
    {
        parent::init();
        $this->createTabs();
    }

    /**
     * @deprecated DO NOT USE. THIS IS A HACK. This is removed once we fix the eventhistory action w/ filters.
     */
    protected function applyFilter($query)
    {
        $params = clone $this->params;
        $params->shift('format');
        $params->shift('limit');
        $params->shift('page');
        $params->shift('view');
        if ($sort = $params->shift('sort')) {
            $query->order($sort, $params->shift('dir'));
        }
        $query->applyFilter(Filter::fromQuerystring((string) $params));
        $this->handleFormatRequest($query);
        return $query;
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
        // Handle soft and hard states
        if (strtolower($this->params->shift('stateType', 'soft')) === 'hard') {
            $stateColumn = 'host_hard_state';
            $stateChangeColumn = 'host_last_hard_state_change';
        } else {
            $stateColumn = 'host_state';
            $stateChangeColumn = 'host_last_state_change';
        }

        $this->addTitleTab('hosts', $this->translate('Hosts'), $this->translate('List hosts'));
        $this->setAutorefreshInterval(10);
        $query = $this->backend->select()->from('hostStatus', array_merge(array(
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_display_name',
            'host_state' => $stateColumn,
            'host_address',
            'host_acknowledged',
            'host_output',
            'host_attempt',
            'host_in_downtime',
            'host_is_flapping',
            'host_state_type',
            'host_handled',
            'host_last_check',
            'host_last_state_change' => $stateChangeColumn,
            'host_notifications_enabled',
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
        ), $this->addColumns()));
        $this->filterQuery($query);
        $this->applyRestriction('monitoring/hosts/filter', $query);
        $this->view->hosts = $query;

        $this->view->stats = $this->backend->select()->from('statusSummary', array(
            'hosts_total',
            'hosts_up',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_pending',
        ))->getQuery()->fetchRow();

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->hosts);
        $this->setupSortControl(array(
            'host_severity'     => $this->translate('Severity'),
            'host_state'        => $this->translate('Current State'),
            'host_display_name' => $this->translate('Hostname'),
            'host_address'      => $this->translate('Address'),
            'host_last_check'   => $this->translate('Last Check')
        ), $query);
    }

    /**
     * Display service list
     */
    public function servicesAction()
    {
        // Handle soft and hard states
        if (strtolower($this->params->shift('stateType', 'soft')) === 'hard') {
            $stateColumn = 'service_hard_state';
            $stateChangeColumn = 'service_last_hard_state_change';
        } else {
            $stateColumn = 'service_state';
            $stateChangeColumn = 'service_last_state_change';
        }

        $this->addTitleTab('services', $this->translate('Services'), $this->translate('List services'));
        $this->view->showHost = true;
        if (strpos($this->params->get('host_name', '*'), '*') === false) {
            $this->view->showHost = false;
        }
        $this->setAutorefreshInterval(10);

        $columns = array_merge(array(
            'host_name',
            'host_display_name',
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
            'service_icon_image_alt',
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
        ), $this->addColumns());
        $query = $this->backend->select()->from('serviceStatus', $columns);
        $this->filterQuery($query);
        $this->applyRestriction('monitoring/services/filter', $query);
        $this->view->services = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->services);
        $this->setupSortControl(array(
            'service_severity'      => $this->translate('Service Severity'),
            'service_state'         => $this->translate('Current Service State'),
            'service_display_name'  => $this->translate('Service Name'),
            'service_last_check'    => $this->translate('Last Service Check'),
            'host_severity'         => $this->translate('Host Severity'),
            'host_state'            => $this->translate('Current Host State'),
            'host_display_name'     => $this->translate('Hostname'),
            'host_address'          => $this->translate('Host Address'),
            'host_last_check'       => $this->translate('Last Host Check')
        ), $query);

        $this->view->stats = $this->backend->select()->from('statusSummary', array(
            'services_total',
            'services_ok',
            'services_problem',
            'services_problem_handled',
            'services_problem_unhandled',
            'services_critical',
            'services_critical_unhandled',
            'services_critical_handled',
            'services_warning',
            'services_warning_unhandled',
            'services_warning_handled',
            'services_unknown',
            'services_unknown_unhandled',
            'services_unknown_handled',
            'services_pending',
        ))->getQuery()->fetchRow();
    }

    /**
     * Fetch the current downtimes and put them into the view property `downtimes`
     */
    public function downtimesAction()
    {
        $this->addTitleTab('downtimes', $this->translate('Downtimes'), $this->translate('List downtimes'));
        $this->setAutorefreshInterval(12);

        $query = $this->backend->select()->from('downtime', array(
            'id'              => 'downtime_internal_id',
            'objecttype'      => 'downtime_objecttype',
            'comment'         => 'downtime_comment',
            'author_name'     => 'downtime_author_name',
            'start'           => 'downtime_start',
            'scheduled_start' => 'downtime_scheduled_start',
            'scheduled_end'   => 'downtime_scheduled_end',
            'end'             => 'downtime_end',
            'duration'        => 'downtime_duration',
            'is_flexible'     => 'downtime_is_flexible',
            'is_fixed'        => 'downtime_is_fixed',
            'is_in_effect'    => 'downtime_is_in_effect',
            'entry_time'      => 'downtime_entry_time',
            'host_state'      => 'downtime_host_state',
            'service_state'   => 'downtime_service_state',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ));
        $this->filterQuery($query);
        $this->view->downtimes = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->downtimes);
        $this->setupSortControl(array(
            'downtime_is_in_effect'     => $this->translate('Is In Effect'),
            'host_display_name'         => $this->translate('Host'),
            'service_display_name'      => $this->translate('Service'),
            'downtime_entry_time'       => $this->translate('Entry Time'),
            'downtime_author'           => $this->translate('Author'),
            'downtime_start'            => $this->translate('Start Time'),
            'downtime_end'              => $this->translate('End Time'),
            'downtime_scheduled_start'  => $this->translate('Scheduled Start'),
            'downtime_scheduled_end'    => $this->translate('Scheduled End'),
            'downtime_duration'         => $this->translate('Duration')
        ), $query);

        if ($this->Auth()->hasPermission('monitoring/command/downtime/delete')) {
            $this->view->delDowntimeForm = new DeleteDowntimeCommandForm();
            $this->view->delDowntimeForm->handleRequest();
        }
    }

    /**
     * Display notification overview
     */
    public function notificationsAction()
    {
        $this->addTitleTab(
            'notifications',
            $this->translate('Notifications'),
            $this->translate('List notifications')
        );
        $this->setAutorefreshInterval(15);

        $query = $this->backend->select()->from('notification', array(
            'host_name',
            'service_description',
            'notification_output',
            'notification_contact_name',
            'notification_start_time',
            'notification_state',
            'host_display_name',
            'service_display_name'
        ));
        $this->filterQuery($query);
        $this->view->notifications = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->notifications);
        $this->setupSortControl(array(
            'notification_start_time' => $this->translate('Notification Start')
        ), $query);
    }

    public function contactsAction()
    {
        $this->addTitleTab('contacts', $this->translate('Contacts'), $this->translate('List contacts'));

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
        $this->filterQuery($query);
        $this->view->contacts = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->contacts);
        $this->setupSortControl(array(
            'contact_name' => $this->translate('Name'),
            'contact_alias' => $this->translate('Alias'),
            'contact_email' => $this->translate('Email'),
            'contact_pager' => $this->translate('Pager Address / Number'),
            'contact_notify_service_timeperiod' => $this->translate('Service Notification Timeperiod'),
            'contact_notify_host_timeperiod' => $this->translate('Host Notification Timeperiod')
        ), $query);
    }

    public function eventgridAction()
    {
        $this->addTitleTab('eventgrid', $this->translate('Event Grid'), $this->translate('Show the Event Grid'));

        $form = new StatehistoryForm();
        $form->setEnctype(Zend_Form::ENCTYPE_URLENCODED);
        $form->setMethod('get');
        $form->setTokenDisabled();
        $form->setUidDisabled();
        $form->render();
        $this->view->form = $form;

        $this->params->remove('view');
        $orientation = $this->params->shift('vertical', 0) ? 'vertical' : 'horizontal';
/*
        $orientationBox = new SelectBox(
            'orientation',
            array(
                '0' => mt('monitoring', 'Vertical'),
                '1' => mt('monitoring', 'Horizontal')
            ),
            mt('monitoring', 'Orientation'),
            'horizontal'
        );
        $orientationBox->applyRequest($this->getRequest());
*/
        $query = $this->backend->select()->from(
            'eventgrid',
            array('day', $form->getValue('state'))
        );
        $this->params->remove(array('objecttype', 'from', 'to', 'state', 'btn_submit'));
        $this->view->filter = Filter::fromQuerystring((string) $this->params);
        $query->applyFilter($this->view->filter);
        $this->view->summary = $query->getQuery()->fetchAll();
        $this->view->column = $form->getValue('state');
//        $this->view->orientationBox = $orientationBox;
        $this->view->orientation = $orientation;
    }

    public function contactgroupsAction()
    {
        $this->addTitleTab(
            'contactgroups',
            $this->translate('Contact Groups'),
            $this->translate('List contact groups')
        );

        $query = $this->backend->select()->from('contactgroup', array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
        ));
        $this->filterQuery($query);

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

        $this->setupSortControl(array(
            'contactgroup_name'     => $this->translate('Contactgroup Name'),
            'contactgroup_alias'    => $this->translate('Contactgroup Alias')
        ), $query);
    }

    public function commentsAction()
    {
        $this->addTitleTab('comments', $this->translate('Comments'), $this->translate('List comments'));
        $this->setAutorefreshInterval(12);

        $query = $this->backend->select()->from('comment', array(
            'id'         => 'comment_internal_id',
            'objecttype' => 'comment_objecttype',
            'comment'    => 'comment_data',
            'author'     => 'comment_author_name',
            'timestamp'  => 'comment_timestamp',
            'type'       => 'comment_type',
            'persistent' => 'comment_is_persistent',
            'expiration' => 'comment_expiration',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ));
        $this->filterQuery($query);
        $this->view->comments = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->comments);
        $this->setupSortControl(
            array(
                'comment_timestamp'     => $this->translate('Comment Timestamp'),
                'host_display_name'     => $this->translate('Host'),
                'service_display_name'  => $this->translate('Service'),
                'comment_type'          => $this->translate('Comment Type'),
                'comment_expiration'    => $this->translate('Expiration')
            ),
            $query
        );

        if ($this->Auth()->hasPermission('monitoring/command/comment/delete')) {
            $this->view->delCommentForm = new DeleteCommentCommandForm();
            $this->view->delCommentForm->handleRequest();
        }
    }

    public function servicegroupsAction()
    {
        $this->addTitleTab(
            'servicegroups',
            $this->translate('Service Groups'),
            $this->translate('List service groups')
        );
        $this->setAutorefreshInterval(12);

        $query = $this->backend->select()->from('servicegroupsummary', array(
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up',
            'servicegroup_alias',
            'servicegroup_name',
            'services_critical_handled',
            'services_critical_last_state_change_handled',
            'services_critical_last_state_change_unhandled',
            'services_critical_unhandled',
            'services_ok',
            'services_ok_last_state_change',
            'services_pending',
            'services_pending_last_state_change',
            'services_total',
            'services_unknown_handled',
            'services_unknown_last_state_change_handled',
            'services_unknown_last_state_change_unhandled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_last_state_change_handled',
            'services_warning_last_state_change_unhandled',
            'services_warning_unhandled'
        ));
        $this->filterQuery($query);
        $this->view->servicegroups = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->servicegroups);
        $this->setupSortControl(array(
            'services_severity'     => $this->translate('Severity'),
            'servicegroup_alias'    => $this->translate('Service Group Name'),
            'services_total'        => $this->translate('Total Services')
        ), $query);
    }

    public function hostgroupsAction()
    {
        $this->addTitleTab('hostgroups', $this->translate('Host Groups'), $this->translate('List host groups'));
        $this->setAutorefreshInterval(12);

        $query = $this->backend->select()->from('hostgroupsummary', array(
            'hostgroup_alias',
            'hostgroup_name',
            'hosts_down_handled',
            'hosts_down_last_state_change_handled',
            'hosts_down_last_state_change_unhandled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_pending_last_state_change',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_last_state_change_handled',
            'hosts_unreachable_last_state_change_unhandled',
            'hosts_unreachable_unhandled',
            'hosts_up',
            'hosts_up_last_state_change',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_total',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning_handled',
            'services_warning_unhandled'
        ));
        $this->filterQuery($query);
        $this->view->hostgroups = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->hostgroups);
        $this->setupSortControl(array(
            'hosts_severity'    => $this->translate('Severity'),
            'hostgroup_alias'   => $this->translate('Host Group Name'),
            'hosts_total'       => $this->translate('Total Hosts'),
            'services_total'    => $this->translate('Total Services')
        ), $query);
    }

    public function eventhistoryAction()
    {
        $this->addTitleTab(
            'eventhistory',
            $this->translate('Event Overview'),
            $this->translate('List event records')
        );

        $query = $this->backend->select()->from('eventHistory', array(
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            'object_type',
            'timestamp',
            'state',
            'attempt',
            'max_attempts',
            'output',
            'type'
        ));

        $this->filterQuery($query);
        $this->view->history = $query;

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->history);
        $this->setupSortControl(array(
            'timestamp' => $this->translate('Occurence')
        ), $query);
    }

    public function servicegridAction()
    {
        $this->addTitleTab('servicegrid', $this->translate('Service Grid'), $this->translate('Show the Service Grid'));
        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from('serviceStatus', array(
            'host_name',
            'service_description',
            'service_state',
            'service_output',
            'service_handled'
        ));
        $this->filterQuery($query);
        $this->setupSortControl(array(
            'host_name'           => $this->translate('Hostname'),
            'service_description' => $this->translate('Service description')
        ), $query);
        $pivot = $query->pivot('service_description', 'host_name');
        $this->view->pivot = $pivot;
        $this->view->horizontalPaginator = $pivot->paginateXAxis();
        $this->view->verticalPaginator   = $pivot->paginateYAxis();
    }

    /**
     * Apply filters on a DataView
     *
     * @param DataView  $dataView       The DataView to apply filters on
     *
     * @return DataView $dataView
     */
    protected function filterQuery(DataView $dataView)
    {
        $editor = Widget::create('filterEditor')
            ->setQuery($dataView)
            ->preserveParams(
                'limit', 'sort', 'dir', 'format', 'view', 'backend',
                'stateType', 'addColumns', '_dev'
            )
            ->ignoreParams('page')
            ->setSearchColumns($dataView->getSearchColumns())
            ->handleRequest($this->getRequest());
        $dataView->applyFilter($editor->getFilter());

        $this->setupFilterControl($editor);
        $this->view->filter = $editor->getFilter();

        $this->handleFormatRequest($dataView);
        return $dataView;
    }

    /**
     * Get columns to be added from URL parameter 'addColumns'
     * and assign to $this->view->addColumns (as array)
     *
     * @return array
     */
    protected function addColumns()
    {
        $columns = preg_split(
            '~,~',
            $this->params->shift('addColumns', ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        );
        $this->view->addColumns = $columns;
        return $columns;
    }

    protected function addTitleTab($action, $title, $tip)
    {
        $this->getTabs()->add($action, array(
            'title' => $tip,
            'label' => $title,
            'url'   => Url::fromRequest()
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
        $this->getTabs()->extend(new OutputFormat())->extend(new DashboardAction());
    }
}
