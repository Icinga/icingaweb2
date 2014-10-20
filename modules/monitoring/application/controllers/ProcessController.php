<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Form\Command\Instance\DisableNotificationsExpireCommandForm;
use Icinga\Module\Monitoring\Form\Command\Instance\ToggleInstanceFeaturesCommandForm;

/**
 * Display process and performance information of the monitoring host and program-wide commands
 */
class Monitoring_ProcessController extends Controller
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
                    'title' => $this->translate('Process Info'),
                    'url'   =>'monitoring/process/info'
                )
            )
            ->add(
                'performance',
                array(
                    'title' => $this->translate('Performance Info'),
                    'url'   => 'monitoring/process/performance'
                )
            );
    }

    /**
     * Display process information and program-wide commands
     */
    public function infoAction()
    {
        $this->view->title = $this->translate('Process Info');
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
                    'program_start_time',
                    'status_update_time',
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
            ->getQuery()
            ->fetchRow();
        $this->view->programStatus = $programStatus;
        $toggleFeaturesForm = new ToggleInstanceFeaturesCommandForm();
        $toggleFeaturesForm
            ->setStatus($programStatus)
            ->load($programStatus)
            ->handleRequest();
        $this->view->toggleFeaturesForm = $toggleFeaturesForm;
    }

    /**
     * Disable notifications w/ an optional expire time
     */
    public function disableNotificationsAction()
    {
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
                ->setRedirectUrl('monitoring/process/info')
                ->handleRequest();
            $this->view->form = $form;
        }
    }

    public function performanceAction()
    {
        $this->getTabs()->activate('performance');
        $this->setAutorefreshInterval(10);
        $this->view->runtimevariables = (object) $this->backend->select()
            ->from('runtimevariables', array('varname', 'varvalue'))
            ->getQuery()->fetchPairs();

        $this->view->checkperformance = $this->backend->select()
            ->from('runtimesummary')
            ->getQuery()->fetchAll();
    }
}
