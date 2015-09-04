<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/** @var $this \Icinga\Application\Modules\Module */

$this->providePermission(
    'monitoring/command/*',
    $this->translate('Allow all commands')
);
$this->providePermission(
    'monitoring/command/schedule-check',
    $this->translate('Allow scheduling host and service checks')
);
$this->providePermission(
    'monitoring/command/acknowledge-problem',
    $this->translate('Allow acknowledging host and service problems')
);
$this->providePermission(
    'monitoring/command/remove-acknowledgement',
    $this->translate('Allow removing problem acknowledgements')
);
$this->providePermission(
    'monitoring/command/comment/*',
    $this->translate('Allow adding and deleting host and service comments')
);
$this->providePermission(
    'monitoring/command/comment/add',
    $this->translate('Allow commenting on hosts and services')
);
$this->providePermission(
    'monitoring/command/comment/delete',
    $this->translate('Allow deleting host and service comments')
);
$this->providePermission(
    'monitoring/command/downtime/*',
    $this->translate('Allow scheduling and deleting host and service downtimes')
);
$this->providePermission(
    'monitoring/command/downtime/schedule',
    $this->translate('Allow scheduling host and service downtimes')
);
$this->providePermission(
    'monitoring/command/downtime/delete',
    $this->translate('Allow deleting host and service downtimes')
);
$this->providePermission(
    'monitoring/command/process-check-result',
    $this->translate('Allow processing host and service check results')
);
$this->providePermission(
    'monitoring/command/feature/instance',
    $this->translate('Allow processing commands for toggling features on an instance-wide basis')
);
$this->providePermission(
    'monitoring/command/feature/object',
    $this->translate('Allow processing commands for toggling features on host and service objects')
);
$this->providePermission(
    'monitoring/command/send-custom-notification',
    $this->translate('Allow sending custom notifications for hosts and services')
);

$this->provideRestriction(
    'monitoring/filter/objects',
    $this->translate('Restrict views to the Icinga objects that match the filter')
);

$this->provideConfigTab('backends', array(
    'title' => $this->translate('Configure how to retrieve monitoring information'),
    'label' => $this->translate('Backends'),
    'url' => 'config'
));
$this->provideConfigTab('security', array(
    'title' => $this->translate('Configure how to protect your monitoring environment against prying eyes'),
    'label' => $this->translate('Security'),
    'url' => 'config/security'
));
$this->provideSetupWizard('Icinga\Module\Monitoring\MonitoringWizard');

/*
 * Available Search Urls
 */
$this->provideSearchUrl($this->translate('Hosts'), 'monitoring/list/hosts?sort=host_severity&limit=10', 99);
$this->provideSearchUrl($this->translate('Services'), 'monitoring/list/services?sort=service_severity&limit=10', 98);
$this->provideSearchUrl($this->translate('Hostgroups'), 'monitoring/list/hostgroups?limit=10', 97);
$this->provideSearchUrl($this->translate('Servicegroups'), 'monitoring/list/servicegroups?limit=10', 96);

/*
 * Problems Section
 */
$section = $this->menuSection($this->translate('Problems'), array(
    'renderer' => array(
        'SummaryMenuItemRenderer',
        'state' => 'critical'
    ),
    'icon'      => 'block',
    'priority'  => 20
));
$section->add($this->translate('Unhandled Hosts'), array(
    'renderer'  => array(
        'Icinga\Module\Monitoring\Web\Menu\MonitoringBadgeMenuItemRenderer',
        'columns' => array(
            'hosts_down_unhandled' => $this->translate('%d unhandled hosts down')
        ),
        'state'    => 'critical',
        'dataView' => 'statussummary'
    ),
    'url'       => 'monitoring/list/hosts?host_problem=1&host_handled=0',
    'priority'  => 30
));
$section->add($this->translate('Unhandled Services'), array(
    'renderer'  => array(
        'Icinga\Module\Monitoring\Web\Menu\MonitoringBadgeMenuItemRenderer',
        'columns' => array(
            'services_critical_unhandled' => $this->translate('%d unhandled services critical')
        ),
        'state'    => 'critical',
        'dataView' => 'statussummary'
    ),
    'url'       => 'monitoring/list/services?service_problem=1&service_handled=0&sort=service_severity',
    'priority'  => 40
));
$section->add($this->translate('Host Problems'), array(
    'url'       => 'monitoring/list/hosts?host_problem=1&sort=host_severity',
    'priority'  => 50
));
$section->add($this->translate('Service Problems'), array(
    'url'       => 'monitoring/list/services?service_problem=1&sort=service_severity&dir=desc',
    'priority'  => 60
));
$section->add($this->translate('Service Grid'), array(
    'url'       => 'monitoring/list/servicegrid?problems',
    'priority'  => 70
));
$section->add($this->translate('Current Downtimes'), array(
    'url'       => 'monitoring/list/downtimes?downtime_is_in_effect=1',
    'priority'  => 80
));

/*
 * Overview Section
 */
$section = $this->menuSection($this->translate('Overview'), array(
    'icon'      => 'sitemap',
    'priority'  => 30
));
$section->add($this->translate('Tactical Overview'), array(
    'url'      => 'monitoring/tactical',
    'priority' => 40
));
$section->add($this->translate('Hosts'), array(
    'url'      => 'monitoring/list/hosts',
    'priority' => 50
));
$section->add($this->translate('Services'), array(
    'url'      => 'monitoring/list/services',
    'priority' => 50
));
$section->add($this->translate('Servicegroups'), array(
    'url'      => 'monitoring/list/servicegroups',
    'priority' => 60
));
$section->add($this->translate('Hostgroups'), array(
    'url'      => 'monitoring/list/hostgroups',
    'priority' => 60
));
$section->add($this->translate('Contacts'), array(
    'url'      => 'monitoring/list/contacts',
    'priority' => 70
));
$section->add($this->translate('Contactgroups'), array(
    'url'      => 'monitoring/list/contactgroups',
    'priority' => 70
));
$section->add($this->translate('Comments'), array(
    'url'      => 'monitoring/list/comments?comment_type=(comment|ack)',
    'priority' => 80
));
$section->add($this->translate('Downtimes'), array(
    'url'      => 'monitoring/list/downtimes',
    'priority' => 80
));

/*
 * History Section
 */
$section = $this->menuSection($this->translate('History'), array(
    'icon' => 'rewind'
));
$section->add($this->translate('Event Grid'), array(
    'priority'  => 10,
    'url'       => 'monitoring/list/eventgrid'
));
$section->add($this->translate('Event Overview'), array(
    'priority'  => 20,
    'url'       => 'monitoring/list/eventhistory?timestamp>=-7%20days'
));
$section->add($this->translate('Notifications'), array(
    'priority'  => 30,
    'url'       => 'monitoring/list/notifications',
));
$section->add($this->translate('Timeline'), array(
    'priority'  => 40,
    'url'       => 'monitoring/timeline'
));

/*
 * Reporting Section
 */
$section = $this->menuSection($this->translate('Reporting'), array(
    'icon'      => 'barchart',
    'priority'  => 100
));

$section->add($this->translate('Alert Summary'), array(
   'url'    => 'monitoring/alertsummary/index'
));

/*
 * System Section
 */
$section = $this->menuSection($this->translate('System'));
$section->add($this->translate('Monitoring Health'), array(
    'url'      => 'monitoring/health/info',
    'priority' => 720,
    'renderer' => 'Icinga\Module\Monitoring\Web\Menu\BackendAvailabilityMenuItemRenderer'
));

/*
 * Current Incidents
 */
$dashboard = $this->dashboard($this->translate('Current Incidents'));
$dashboard->add(
    $this->translate('Service Problems'),
    'monitoring/list/services?service_problem=1&limit=10&sort=service_severity'
);
$dashboard->add(
    $this->translate('Recently Recovered Services'),
    'monitoring/list/services?service_state=0&limit=10&sort=service_last_state_change&dir=desc'
);
$dashboard->add(
    $this->translate('Host Problems'),
    'monitoring/list/hosts?host_problem=1&sort=host_severity'
);

/*
 * Overview
 */
$dashboard = $this->dashboard($this->translate('Overview'));
$dashboard->add(
    $this->translate('Service Grid'),
    'monitoring/list/servicegrid?limit=15,18'
);
$dashboard->add(
    $this->translate('Service Groups'),
    '/monitoring/list/servicegroups'
);
$dashboard->add(
    $this->translate('Host Groups'),
    '/monitoring/list/hostgroups'
);

/*
 * Most Overdue
 */
$dashboard = $this->dashboard($this->translate('Overdue'));
$dashboard->add(
    $this->translate('Acknowledgements Active For At Least Three Days'),
    'monitoring/list/comments?comment_type=Ack&comment_timestamp<-3 days&sort=comment_timestamp&dir=asc'
);
$dashboard->add(
    $this->translate('Downtimes Active For More Than Three Days'),
    'monitoring/list/downtimes?downtime_is_in_effect=1&downtime_scheduled_start<-3%20days&sort=downtime_start&dir=asc'
);

/*
 * Muted Objects
 */
$dashboard = $this->dashboard($this->translate('Muted'));
$dashboard->add(
    $this->translate('Disabled Service Notifications'),
    'monitoring/list/services?service_notifications_enabled=0&limit=10'
);
$dashboard->add(
    $this->translate('Disabled Host Notifications'),
    'monitoring/list/hosts?host_notifications_enabled=0&limit=10'
);
$dashboard->add(
    $this->translate('Disabled Service Checks'),
    'monitoring/list/services?service_active_checks_enabled=0&limit=10'
);
$dashboard->add(
    $this->translate('Disabled Host Checks'),
    'monitoring/list/hosts?host_active_checks_enabled=0&limit=10'
);
$dashboard->add(
    $this->translate('Acknowledged Problem Services'),
    'monitoring/list/services?service_acknowledgement_type=2&service_problem=1&sort=service_state&limit=10'
);
$dashboard->add(
    $this->translate('Acknowledged Problem Hosts'),
    'monitoring/list/hosts?host_acknowledgement_type=2&host_problem=1&sort=host_severity&limit=10'
);

/*
 * Activity Stream
 */
$dashboard = $this->dashboard($this->translate('Activity Stream'));
$dashboard->add(
    $this->translate('Recent Events'),
    'monitoring/list/eventhistory?timestamp>=-3%20days&sort=timestamp&dir=desc&limit=8'
);
$dashboard->add(
    $this->translate('Recent Hard State Changes'),
    'monitoring/list/eventhistory?timestamp>=-3%20days&type=hard_state&sort=timestamp&dir=desc&limit=8'
);
$dashboard->add(
    $this->translate('Recent Notifications'),
    'monitoring/list/eventhistory?timestamp>=-3%20days&type=notify&sort=timestamp&dir=desc&limit=8'
);
$dashboard->add(
    $this->translate('Recent Downtimes Started'),
    'monitoring/list/eventhistory?timestamp>=-3%20days&type=dt_start&sort=timestamp&dir=desc&limit=8'
);
$dashboard->add(
    $this->translate('Recent Downtimes Ended'),
    'monitoring/list/eventhistory?timestamp>=-3%20days&type=dt_end&sort=timestamp&dir=desc&limit=8'
);

/*
 * Stats
 */
$dashboard = $this->dashboard($this->translate('Stats'));
$dashboard->add(
    $this->translate('Check Stats'),
    'monitoring/health/stats'
);
$dashboard->add(
    $this->translate('Process Information'),
    'monitoring/health/info'
);

/*
 * CSS
 */
$this->provideCssFile('colors.less');
$this->provideCssFile('service-grid.less');
