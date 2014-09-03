<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/* @var $this \Icinga\Application\Modules\Module */

// TODO: We need to define a useful permission set for this module, the
//       list provided here is just an example
$this->providePermission('commands/all', 'Allow to send all commands');
$this->providePermission('commands/safe', 'Allow to to send a subset of "safe" commands');
$this->providePermission('log', 'Allow full log access');
$this->provideRestriction('filter', 'Filter accessible object');
$this->provideConfigTab('backends', array(
    'title' => 'Backends',
    'url' => 'config'
));
$this->provideConfigTab('security', array(
    'title' => 'Security',
    'url' => 'config/security'
));

/*
 * Problems Section
 */
$section = $this->menuSection($this->translate('Problems'), array(
    'icon'      => 'img/icons/error.png',
    'priority'  => 20
));
$section->add($this->translate('Unhandled Hosts'), array(
    'url'      => 'monitoring/list/hosts?host_problem=1&host_handled=0',
    'priority' => 40
));
$section->add($this->translate('Unhandled Services'), array(
    'url'      => 'monitoring/list/services?service_problem=1&service_handled=0&sort=service_severity',
    'priority' => 40
));
$section->add($this->translate('Host Problems'), array(
    'url'      => 'monitoring/list/hosts?host_problem=1&sort=host_severity',
    'priority' => 50
));
$section->add($this->translate('Service Problems'), array(
    'url'      => 'monitoring/list/services?service_problem=1&sort=service_severity&dir=desc',
    'priority' => 50
));
$section->add($this->translate('Current Downtimes'))->setUrl('monitoring/list/downtimes?downtime_is_in_effect=1');

/*
 * Overview Section
 */
$section = $this->menuSection($this->translate('Overview'), array(
    'icon'      => 'img/icons/hostgroup.png',
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
$section->add($this->translate('Servicematrix'), array(
    'url'      => 'monitoring/list/servicematrix?service_problem=1',
    'priority' => 51
));
$section->add($this->translate('Servicegroups'), array(
    'url'      => 'monitoring/list/servicegroups',
    'priority' => 60
));
$section->add($this->translate('Hostgroups'), array(
    'url'      => 'monitoring/list/hostgroups',
    'priority' => 60
));
$section->add($this->translate('Contactgroups'), array(
    'url'      => 'monitoring/list/contactgroups',
    'priority' => 61
));
$section->add($this->translate('Downtimes'), array(
    'url'      => 'monitoring/list/downtimes',
    'priority' => 71
));
$section->add($this->translate('Comments'), array(
    'url'      => 'monitoring/list/comments?comment_type=(comment|ack)',
    'priority' => 70
));
$section->add($this->translate('Contacts'), array(
    'url'      => 'monitoring/list/contacts',
    'priority' => 70
));

/*
 * History Section
 */
$section = $this->menuSection($this->translate('History'), array(
    'icon'      => 'img/icons/history.png'
));
$section->add($this->translate('Critical Events'), array(
    'url'      => 'monitoring/list/statehistorysummary',
    'priority' => 50
));
$section->add($this->translate('Notifications'), array(
    'url'      => 'monitoring/list/notifications'
));
$section->add($this->translate('Events'), array(
    'title'    => $this->translate('All Events'),
    'url'      => 'monitoring/list/eventhistory?timestamp>=-7%20days'
));
$section->add($this->translate('Timeline'))->setUrl('monitoring/timeline');

/*
 * System Section
 */
$section = $this->menuSection($this->translate('System'));
$section->add($this->translate('Process Info'), array(
    'url'      => 'monitoring/process/info',
    'priority' => 120
));
$section->add($this->translate('Performance Info'), array(
    'url'      => 'monitoring/process/performance',
    'priority' => 130
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
