<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Forms\Command\Instance\DisableNotificationsExpireCommandForm;
use Icinga\Module\Monitoring\Forms\Command\Instance\ToggleInstanceFeaturesCommandForm;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;

/**
 * Display process and performance information of the monitoring host and program-wide commands
 */
class HealthController extends Controller
{
    /**
     * Add tabs
     *
     * @see \Icinga\Web\Controller\ActionController::init()
     */
    public function init()
    {
        $this
            ->getTabs()
            ->add(
                'info',
                array(
                    'title' => $this->translate(
                        'Show information about the current monitoring instance\'s process'
                        . ' and it\'s performance as well as available features'
                    ),
                    'label' => $this->translate('Process Information'),
                    'url'   =>'monitoring/health/info'
                )
            )
            ->add(
                'stats',
                array(
                    'title' => $this->translate(
                        'Show statistics about the monitored objects'
                    ),
                    'label' => $this->translate('Stats'),
                    'url'   =>'monitoring/health/stats'
                )
            )
            ->extend(new DashboardAction())->extend(new MenuAction());
    }

    /**
     * Display process information and program-wide commands
     */
    public function infoAction()
    {
        $this->view->title = $this->translate('Process Information');
        $this->getTabs()->activate('info');
        $this->setAutorefreshInterval(10);
        $this->view->backendName = $this->backend->getName();
        $programStatus = $this->backend
            ->select()
            ->from(
                'programstatus',
                array(
                    'is_currently_running',
                    'process_id',
                    'endpoint_name',
                    'program_start_time',
                    'status_update_time',
                    'program_version',
                    'last_command_check',
                    'last_log_rotation',
                    'global_service_event_handler',
                    'global_host_event_handler',
                    'notifications_enabled',
                    'disable_notif_expire_time',
                    'active_service_checks_enabled',
                    'passive_service_checks_enabled',
                    'active_host_checks_enabled',
                    'passive_host_checks_enabled',
                    'event_handlers_enabled',
                    'obsess_over_services',
                    'obsess_over_hosts',
                    'flap_detection_enabled',
                    'process_performance_data'
                )
            )
            ->getQuery();
        $this->handleFormatRequest($programStatus);
        $programStatus = $programStatus->fetchRow();
        if ($programStatus === false) {
            return $this->render('not-running', true, null);
        }
        $this->view->programStatus = $programStatus;
        $toggleFeaturesForm = new ToggleInstanceFeaturesCommandForm();
        $toggleFeaturesForm
            ->setBackend($this->backend)
            ->setStatus($programStatus)
            ->load($programStatus)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;

        $this->view->runtimevariables = (object) $this->backend->select()
            ->from('runtimevariables', array('varname', 'varvalue'))
            ->getQuery()->fetchPairs();

        $this->view->checkperformance = $this->backend->select()
            ->from('runtimesummary')
            ->getQuery()->fetchAll();
    }

    /**
     * Display stats about current checks and monitored objects
     */
    public function statsAction()
    {
        $this->getTabs()->activate('stats');

        $servicestats = $this->backend->select()->from('servicestatussummary', array(
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
        $this->applyRestriction('monitoring/filter/objects', $servicestats);
        $this->view->servicestats = $servicestats->fetchRow();
        $this->view->unhandledServiceProblems = $this->view->servicestats->services_critical_unhandled
            + $this->view->servicestats->services_unknown_unhandled
            + $this->view->servicestats->services_warning_unhandled;

        $hoststats = $this->backend->select()->from('hoststatussummary', array(
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
        $this->applyRestriction('monitoring/filter/objects', $hoststats);
        $this->view->hoststats = $hoststats->fetchRow();
        $this->view->unhandledhostProblems = $this->view->hoststats->hosts_down_unhandled
            + $this->view->hoststats->hosts_unreachable_unhandled;

        $this->view->unhandledProblems = $this->view->unhandledhostProblems
            + $this->view->unhandledServiceProblems;

        $this->view->runtimevariables  = (object) $this->backend->select()
            ->from('runtimevariables', array('varname', 'varvalue'))
            ->getQuery()->fetchPairs();

        $this->view->checkperformance = $this->backend->select()
            ->from('runtimesummary')
            ->getQuery()->fetchAll();
    }

    /**
     * Disable notifications w/ an optional expire time
     */
    public function disableNotificationsAction()
    {
        $this->assertPermission('monitoring/command/feature/instance');
        $this->view->title = $this->translate('Disable Notifications');
        $programStatus = $this->backend
            ->select()
            ->from(
                'programstatus',
                array(
                    'notifications_enabled',
                    'disable_notif_expire_time'
                )
            )
            ->getQuery()
            ->fetchRow();
        $this->view->programStatus = $programStatus;
        if ((bool) $programStatus->notifications_enabled === false) {
            return;
        } else {
            $form = new DisableNotificationsExpireCommandForm();
            $form
                ->setRedirectUrl('monitoring/health/info')
                ->handleRequest();
            $this->view->form = $form;
        }
    }
}
