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
    'monitoring/hosts/filter',
    $this->translate('Restrict hosts view to the hosts that match the filter')
);

$this->provideRestriction(
    'monitoring/services/filter',
    $this->translate('Restrict services view to the services that match the filter')
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
    'renderer'  => 'Icinga\Module\Monitoring\Web\Menu\ProblemMenuItemRenderer',
    'icon'      => 'block',
    'priority'  => 20
));
$section->add($this->translate('Unhandled Hosts'), array(
    'renderer'  => 'Icinga\Module\Monitoring\Web\Menu\UnhandledHostMenuItemRenderer',
    'url'       => 'monitoring/list/hosts?host_problem=1&host_handled=0',
    'priority'  => 30
));
$section->add($this->translate('Unhandled Services'), array(
    'renderer'  => 'Icinga\Module\Monitoring\Web\Menu\UnhandledServiceMenuItemRenderer',
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
    'url'       => 'monitoring/list/servicegrid?service_problem=1',
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
$section->add($this->translate('Notifications'), array(
    'url'      => 'monitoring/list/notifications',
    'priority' => 80
));


/*
 * History Section
 */
$section = $this->menuSection($this->translate('History'), array(
    'icon'      => 'rewind'
));
$section->add($this->translate('Event Grid'), array(
    'url'      => 'monitoring/list/eventgrid',
    'priority' => 50
));
$section->add($this->translate('Events'), array(
    'title'    => $this->translate('Event Overview'),
    'url'      => 'monitoring/list/eventhistory?timestamp>=-7%20days'
));
$section->add($this->translate('Timeline'))->setUrl('monitoring/timeline');

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
    'url'      => 'monitoring/process/info',
    'priority' => 120
));

/*
 * Dashboard
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
