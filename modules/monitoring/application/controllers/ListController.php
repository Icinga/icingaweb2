<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Security\SecurityException;
use Icinga\Util\GlobFilter;
use Icinga\Web\Form;
use Zend_Form;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\DataView\DataView;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteCommentCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Object\DeleteDowntimeCommandForm;
use Icinga\Module\Monitoring\Forms\StatehistoryForm;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;
use Icinga\Web\Widget\Tabs;

class ListController extends Controller
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
     * Overwrite the backend to use (used for testing)
     *
     * @param MonitoringBackend $backend      The Backend that should be used for querying
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * List hosts
     */
    public function hostsAction()
    {
        $this->addTitleTab(
            'hosts',
            $this->translate('Hosts'),
            $this->translate('List hosts')
        );

        $this->setAutorefreshInterval(10);

        // Handle soft and hard states
        if (strtolower($this->params->shift('stateType', 'soft')) === 'hard') {
            $stateColumn = 'host_hard_state';
            $stateChangeColumn = 'host_last_hard_state_change';
        } else {
            $stateColumn = 'host_state';
            $stateChangeColumn = 'host_last_state_change';
        }

        $hosts = $this->backend->select()->from('hoststatus', array_merge(array(
            'host_icon_image',
            'host_icon_image_alt',
            'host_name',
            'host_display_name',
            'host_state' => $stateColumn,
            'host_acknowledged',
            'host_output',
            'host_attempt',
            'host_in_downtime',
            'host_is_flapping',
            'host_state_type',
            'host_handled',
            'host_last_state_change' => $stateChangeColumn,
            'host_notifications_enabled',
            'host_active_checks_enabled',
            'host_passive_checks_enabled',
            'host_check_command',
            'host_next_update'
        ), $this->addColumns()));

        $this->setupPaginationControl($hosts);
        $this->setupSortControl(array(
            'host_severity'             => $this->translate('Severity'),
            'host_state'                => $this->translate('Current State'),
            'host_display_name'         => $this->translate('Hostname'),
            'host_address'              => $this->translate('Address'),
            'host_last_check'           => $this->translate('Last Check'),
            'host_last_state_change'    => $this->translate('Last State Change')
        ), $hosts);
        $this->filterQuery($hosts);
        $this->setupLimitControl();

        $stats = $this->backend->select()->from('hoststatussummary', array(
            'hosts_total',
            'hosts_up',
            'hosts_down',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_unreachable',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_pending',
        ));
        $this->applyRestriction('monitoring/filter/objects', $stats);

        $this->view->hosts = $hosts;
        $this->view->stats = $stats;
    }

    /**
     * List services
     */
    public function servicesAction()
    {
        $this->addTitleTab(
            'services',
            $this->translate('Services'),
            $this->translate('List services')
        );

        // Handle soft and hard states
        if (strtolower($this->params->shift('stateType', 'soft')) === 'hard') {
            $stateColumn = 'service_hard_state';
            $stateChangeColumn = 'service_last_hard_state_change';
        } else {
            $stateColumn = 'service_state';
            $stateChangeColumn = 'service_last_state_change';
        }

        $this->setAutorefreshInterval(10);

        $services = $this->backend->select()->from('servicestatus', array_merge(array(
            'host_name',
            'host_display_name',
            'host_state',
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
            'service_notifications_enabled',
            'service_active_checks_enabled',
            'service_passive_checks_enabled',
            'service_check_command',
            'service_next_update'
        ), $this->addColumns()));

        $this->setupPaginationControl($services);
        $this->setupSortControl(array(
            'service_severity'          => $this->translate('Service Severity'),
            'service_state'             => $this->translate('Current Service State'),
            'service_display_name'      => $this->translate('Service Name'),
            'service_last_check'        => $this->translate('Last Service Check'),
            'service_last_state_change' => $this->translate('Last State Change'),
            'host_severity'             => $this->translate('Host Severity'),
            'host_state'                => $this->translate('Current Host State'),
            'host_display_name'         => $this->translate('Hostname'),
            'host_address'              => $this->translate('Host Address'),
            'host_last_check'           => $this->translate('Last Host Check')
        ), $services);
        $this->filterQuery($services);
        $this->setupLimitControl();

        $stats = $this->backend->select()->from('servicestatussummary', array(
            'services_critical',
            'services_critical_handled',
            'services_critical_unhandled',
            'services_ok',
            'services_pending',
            'services_total',
            'services_unknown',
            'services_unknown_handled',
            'services_unknown_unhandled',
            'services_warning',
            'services_warning_handled',
            'services_warning_unhandled'
        ));
        $this->applyRestriction('monitoring/filter/objects', $stats);

        $this->view->services = $services;
        $this->view->stats = $stats;
        if (strpos($this->params->get('host_name', '*'), '*') === false) {
            $this->view->showHost = false;
        } else {
            $this->view->showHost = true;
        }
    }

    /**
     * List downtimes
     */
    public function downtimesAction()
    {
        $this->addTitleTab(
            'downtimes',
            $this->translate('Downtimes'),
            $this->translate('List downtimes')
        );

        $this->setAutorefreshInterval(12);

        $downtimes = $this->backend->select()->from('downtime', array(
            'id'              => 'downtime_internal_id',
            'objecttype'      => 'object_type',
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
            'name'            => 'downtime_name',
            'host_state',
            'service_state',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ));

        $this->setupPaginationControl($downtimes);
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
        ), $downtimes);
        $this->filterQuery($downtimes);
        $this->setupLimitControl();

        $this->view->downtimes = $downtimes;

        if ($this->Auth()->hasPermission('monitoring/command/downtime/delete')) {
            $this->view->delDowntimeForm = new DeleteDowntimeCommandForm();
            $this->view->delDowntimeForm->handleRequest();
        }
    }

    /**
     * List notifications
     */
    public function notificationsAction()
    {
        $this->addTitleTab(
            'notifications',
            $this->translate('Notifications'),
            $this->translate('List notifications')
        );

        $this->setAutorefreshInterval(15);

        $notifications = $this->backend->select()->from('notification', array(
            'id',
            'host_display_name',
            'host_name',
            'notification_contact_name',
            'notification_output',
            'notification_state',
            'notification_timestamp',
            'service_description',
            'service_display_name'
        ));

        $this->setupPaginationControl($notifications);
        $this->setupSortControl(array(
            'notification_timestamp' => $this->translate('Notification Start')
        ), $notifications);
        $this->filterQuery($notifications);
        $this->setupLimitControl();

        $this->view->notifications = $notifications;
    }

    /**
     * List contacts
     */
    public function contactsAction()
    {
        if (! $this->hasPermission('*') && $this->hasPermission('no-monitoring/contacts')) {
            throw new SecurityException('No permission for %s', 'monitoring/contacts');
        }

        $this->addTitleTab(
            'contacts',
            $this->translate('Contacts'),
            $this->translate('List contacts')
        );

        $contacts = $this->backend->select()->from('contact', array(
            'contact_name',
            'contact_alias',
            'contact_email',
            'contact_pager',
            'contact_notify_service_timeperiod',
            'contact_notify_host_timeperiod'
        ));

        $this->setupPaginationControl($contacts);
        $this->setupSortControl(array(
            'contact_name'  => $this->translate('Name'),
            'contact_alias' => $this->translate('Alias'),
            'contact_email' => $this->translate('Email'),
            'contact_pager' => $this->translate('Pager Address / Number')
        ), $contacts);
        $this->filterQuery($contacts);
        $this->setupLimitControl();

        $this->view->contacts = $contacts;
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

        $this->params
            ->remove('showCompact')
            ->remove('format');
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
        $objectType = $form->getValue('objecttype');
        $from = $form->getValue('from');
        $query = $this->backend->select()->from(
            'eventgrid' . $objectType,
            array('day', $form->getValue('state'))
        );
        $this->params->remove(array('objecttype', 'from', 'to', 'state', 'btn_submit'));
        $this->view->filter = Filter::fromQueryString((string) $this->params);
        $query->applyFilter($this->view->filter);
        $query->applyFilter(Filter::fromQueryString('timestamp>=' . $from));
        $this->applyRestriction('monitoring/filter/objects', $query);
        $this->view->summary = $query;
        $this->view->column = $form->getValue('state');
//        $this->view->orientationBox = $orientationBox;
        $this->view->orientation = $orientation;
    }

    /**
     * List contact groups
     */
    public function contactgroupsAction()
    {
        if (! $this->hasPermission('*') && $this->hasPermission('no-monitoring/contacts')) {
            throw new SecurityException('No permission for %s', 'monitoring/contacts');
        }

        $this->addTitleTab(
            'contactgroups',
            $this->translate('Contact Groups'),
            $this->translate('List contact groups')
        );

        $contactGroups = $this->backend->select()->from('contactgroup', array(
            'contactgroup_name',
            'contactgroup_alias',
            'contact_count'
        ));

        $this->setupPaginationControl($contactGroups);
        $this->setupSortControl(array(
            'contactgroup_name'     => $this->translate('Contactgroup Name'),
            'contactgroup_alias'    => $this->translate('Contactgroup Alias')
        ), $contactGroups);
        $this->filterQuery($contactGroups);
        $this->setupLimitControl();

        $this->view->contactGroups = $contactGroups;
    }

    /**
     * List all comments
     */
    public function commentsAction()
    {
        $this->addTitleTab(
            'comments',
            $this->translate('Comments'),
            $this->translate('List comments')
        );

        $this->setAutorefreshInterval(12);

        $comments = $this->backend->select()->from('comment', array(
            'id'         => 'comment_internal_id',
            'objecttype' => 'object_type',
            'comment'    => 'comment_data',
            'author'     => 'comment_author_name',
            'timestamp'  => 'comment_timestamp',
            'type'       => 'comment_type',
            'persistent' => 'comment_is_persistent',
            'expiration' => 'comment_expiration',
            'name'       => 'comment_name',
            'host_name',
            'service_description',
            'host_display_name',
            'service_display_name'
        ));

        $this->setupPaginationControl($comments);
        $this->setupSortControl(
            array(
                'comment_timestamp'     => $this->translate('Comment Timestamp'),
                'host_display_name'     => $this->translate('Host'),
                'service_display_name'  => $this->translate('Service'),
                'comment_type'          => $this->translate('Comment Type'),
                'comment_expiration'    => $this->translate('Expiration')
            ),
            $comments
        );
        $this->filterQuery($comments);
        $this->setupLimitControl();

        $this->view->comments = $comments;

        if ($this->Auth()->hasPermission('monitoring/command/comment/delete')) {
            $this->view->delCommentForm = new DeleteCommentCommandForm();
            $this->view->delCommentForm->handleRequest();
        }
    }

    /**
     * List service groups
     */
    public function servicegroupsAction()
    {
        $this->addTitleTab(
            'servicegroups',
            $this->translate('Service Groups'),
            $this->translate('List service groups')
        );

        $this->setAutorefreshInterval(12);

        $serviceGroups = $this->backend->select()->from('servicegroupsummary', array(
            'servicegroup_alias',
            'servicegroup_name',
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

        $this->setupPaginationControl($serviceGroups);
        $this->setupSortControl(array(
            'servicegroup_alias'    => $this->translate('Service Group Name'),
            'services_severity'     => $this->translate('Severity'),
            'services_total'        => $this->translate('Total Services')
        ), $serviceGroups);
        $this->filterQuery($serviceGroups);
        $this->setupLimitControl();

        $this->view->serviceGroups = $serviceGroups;
    }

    /**
     * List service groups
     */
    public function servicegroupGridAction()
    {
        $this->addTitleTab(
            'servicegroup-grid',
            $this->translate('Service Group Grid'),
            $this->translate('Show the Service Group Grid')
        );

        $this->setAutorefreshInterval(15);

        $serviceGroups = $this->backend->select()->from('servicegroupsummary', array(
            'servicegroup_alias',
            'servicegroup_name',
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
        $this->filterQuery($serviceGroups);

        $this->setupSortControl(array(
            'servicegroup_alias'    => $this->translate('Service Group Name'),
            'services_severity'     => $this->translate('Severity'),
            'services_total'        => $this->translate('Total Services')
        ), $serviceGroups, ['services_severity' => 'desc']);

        $this->view->serviceGroups = $serviceGroups;
    }

    /**
     * List host groups
     */
    public function hostgroupsAction()
    {
        $this->addTitleTab(
            'hostgroups',
            $this->translate('Host Groups'),
            $this->translate('List host groups')
        );

        $this->setAutorefreshInterval(12);

        $hostGroups = $this->backend->select()->from('hostgroupsummary', array(
            'hostgroup_alias',
            'hostgroup_name',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up',
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

        $this->setupPaginationControl($hostGroups);
        $this->setupSortControl(array(
            'hostgroup_alias'   => $this->translate('Host Group Name'),
            'hosts_severity'    => $this->translate('Severity'),
            'hosts_total'       => $this->translate('Total Hosts'),
            'services_total'    => $this->translate('Total Services')
        ), $hostGroups);
        $this->filterQuery($hostGroups);
        $this->setupLimitControl();

        $this->view->hostGroups = $hostGroups;
    }

    /**
     * List host groups
     */
    public function hostgroupGridAction()
    {
        $this->addTitleTab(
            'hostgroup-grid',
            $this->translate('Host Group Grid'),
            $this->translate('Show the Host Group Grid')
        );

        $this->setAutorefreshInterval(15);

        $hostGroups = $this->backend->select()->from('hostgroupsummary', [
            'hostgroup_alias',
            'hostgroup_name',
            'hosts_down_handled',
            'hosts_down_unhandled',
            'hosts_pending',
            'hosts_total',
            'hosts_unreachable_handled',
            'hosts_unreachable_unhandled',
            'hosts_up'
        ]);
        $this->filterQuery($hostGroups);

        $this->setupSortControl([
            'hosts_severity'    => $this->translate('Severity'),
            'hostgroup_alias'   => $this->translate('Host Group Name'),
            'hosts_total'       => $this->translate('Total Hosts'),
            'services_total'    => $this->translate('Total Services')
        ], $hostGroups, ['hosts_severity' => 'desc']);

        $this->view->hostGroups = $hostGroups;
    }

    public function eventhistoryAction()
    {
        $this->addTitleTab(
            'eventhistory',
            $this->translate('Event Overview'),
            $this->translate('List event records')
        );

        $query = $this->backend->select()->from('eventhistory', array(
            'id',
            'host_name',
            'host_display_name',
            'service_description',
            'service_display_name',
            'object_type',
            'timestamp',
            'state',
            'output',
            'type'
        ));

        $this->view->history = $query;

        $this->setupSortControl(array(
            'timestamp' => $this->translate('Occurence')
        ), $query);
        $this->filterQuery($query);
        $this->setupLimitControl();
    }

    public function servicegridAction()
    {
        if ($this->params->has('noscript_apply')) {
            $this->redirectNow($this->getRequest()->getUrl()->without('noscript_apply'));
        }

        $this->addTitleTab('servicegrid', $this->translate('Service Grid'), $this->translate('Show the Service Grid'));
        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from('servicestatus', array(
            'host_display_name',
            'host_name',
            'service_description',
            'service_display_name',
            'service_handled',
            'service_output',
            'service_state'
        ));
        $this->filterQuery($query);
        $filter = (bool) $this->params->shift('problems', false) ? Filter::where('service_problem', 1) : null;

        $this->view->problemToggle = $problemToggle = new Form(['method' => 'GET']);
        $problemToggle->setUidDisabled();
        $problemToggle->setTokenDisabled();
        $problemToggle->setAttrib('class', 'filter-toggle inline icinga-controls');
        $problemToggle->addElement('checkbox', 'problems', [
            'disableHidden' => true,
            'autosubmit'    => true,
            'value'         => $filter !== null,
            'label'         => $this->translate('Problems Only'),
            'decorators'    => ['ViewHelper', ['Label', ['placement' => 'APPEND']]]
        ]);


        $sortByDns = (bool) $this->params->get('sortbydnshierarchy', false) ? true : null;
        $this->view->sortByDnsToggle = $sortByDnsToggle = new Form(['method' => 'GET']);
        $sortByDnsToggle->setUidDisabled();
        $sortByDnsToggle->setTokenDisabled();
        $sortByDnsToggle->setAttrib('class', 'filter-toggle inline icinga-controls');
        $sortByDnsToggle->addElement('checkbox', 'sortbydnshierarchy', [
            'disableHidden' => true,
            'autosubmit'    => true,
            'value'         => $sortByDns !== null,
            'label'         => $this->translate('Sort by DNS hierarchy'),
            'decorators'    => ['ViewHelper', ['Label', ['placement' => 'APPEND']]]
        ]);

        if ($this->params->get('flipped', false)) {
            $pivot = $query
                ->pivot(
                    'host_name',
                    'service_description',
                    $filter,
                    $filter ? clone $filter : null
                )
                ->setYAxisHeader('service_display_name')
                ->setXAxisHeader('host_display_name');
        } else {
            $pivot = $query
                ->pivot(
                    'service_description',
                    'host_name',
                    $filter,
                    $filter ? clone $filter : null
                )
                ->setXAxisHeader('service_display_name')
                ->setYAxisHeader('host_display_name');
        }
        $this->setupSortControl(array(
            'host_display_name'     => $this->translate('Hostname'),
            'service_display_name'  => $this->translate('Service Name')
        ), $pivot);
        $this->view->horizontalPaginator = $pivot->paginateXAxis();
        $this->view->verticalPaginator = $pivot->paginateYAxis();
        list($pivotData, $pivotHeader) = $pivot->toArray();

        $sortByDns = (bool) $this->params->get('sortbydnshierarchy', false) ? !true : null;
        if ($sortByDns !== null) {
            $keySort = ($this->params->get('flipped', false) ? 'cols' : 'rows');

            uksort($pivotData, [ $this, "compareByDnsHierarchy" ]);
            uksort($pivotHeader[$keySort], [ $this, "compareByDnsHierarchy" ]);

            $pivotHeader[$keySort] = $this->addGroupHeader($pivotHeader[$keySort]);

        }

        $this->view->pivotData = $pivotData;
        $this->view->pivotHeader = $pivotHeader;
        if ($this->params->get('flipped', false)) {
            $this->render('servicegrid-flipped');
        }
    }

    /**
     * Add group headers to the header array
     * The group name will be the FQDN without the the part before the first dot.
     */

    private function addGroupHeader($header) {
        $newHeader = [];
        $previousGroup = null;
        foreach ($header as $key => $val) {
            $hostnameElements = explode('.', $key);
            if (count($hostnameElements) > 2) {
                if (filter_var($key, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    array_pop($hostnameElements);
                }
                else {
                    array_shift($hostnameElements);
                }
                $group = join('.', $hostnameElements);
                if (is_null($previousGroup) || $previousGroup != $group) {
                    $newHeader['GROUP:' . $group] = $group;
                    $previousGroup = $group;
                }
            }
            $newHeader[$key] = $val;
        }
        return $newHeader;
    }

    /**
     * Split a FQDN by dots and compare each part beginning from top level
     */

    private function compareByDnsHierarchy($a, $b) {
        $keysA = explode('.', $a);
        if (!filter_var($a, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $keysA = array_reverse($keysA);
        }

        $keysB = explode('.', $b);
        if (!filter_var($b, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $keysB = array_reverse($keysB);
        }

        $cnt = min([$cntA = count($keysA), $cntB = count($keysB)]);

        $firstDiff = -1;
        $cmp = [ -1 => 0 ];

        for ($i = 0; $i <= $cnt; $i++) {
            $ka = isset($keysA[$i]) ? $keysA[$i] : "";
            $kb = isset($keysB[$i]) ? $keysB[$i] : "";

            if ($cmp[$i] = $ka <=> $kb) {
                if ($firstDiff === -1) {
                    $firstDiff = $i;
                }
            }
        }

        if (count($keysA) != count($keysB)) {
            return count($keysA) <=> count($keysB);
        }
        else {
            return ($cmp[$firstDiff]);
        }
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
        $this->setupFilterControl($dataView, null, null, array(
            'format', // handleFormatRequest()
            'stateType', // hostsAction() and servicesAction()
            'addColumns', // addColumns()
            'problems', // servicegridAction()
            'flipped', // servicegridAction()
            'sortbydnshierarchy' // servicegridAction()
        ));

        if ($this->params->get('format') !== 'sql' || $this->hasPermission('config/authentication/roles/show')) {
            $this->applyRestriction('monitoring/filter/objects', $dataView);
        }

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

        $customVars = [];
        $additionalCols = [];
        foreach ($columns as $column) {
            if (preg_match('~^_(host|service)_([a-zA-Z0-9_]+)$~', $column, $m)) {
                $customVars[$m[1]]['vars'][$m[2]] = null;
            } else {
                $additionalCols[] = $column;
            }
        }

        if (! empty($customVars)) {
            $blacklistedProperties = new GlobFilter(
                $this->getRestrictions('monitoring/blacklist/properties')
            );
            $customVars = $blacklistedProperties->removeMatching($customVars);
            foreach ($customVars as $type => $vars) {
                foreach ($vars['vars'] as $var => $_) {
                    $additionalCols[] = '_' . $type . '_' . $var;
                }
            }
        }

        $this->view->addColumns = $additionalCols;
        return $additionalCols;
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
        return $this->getTabs()->extend(new OutputFormat())->extend(new DashboardAction())->extend(new MenuAction());
    }
}
