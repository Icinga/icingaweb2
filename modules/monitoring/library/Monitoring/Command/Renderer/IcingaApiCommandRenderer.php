<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Command\Renderer;

use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\Monitoring\Command\IcingaApiCommand;
use Icinga\Module\Monitoring\Command\Instance\ToggleInstanceFeatureCommand;
use Icinga\Module\Monitoring\Command\Object\AcknowledgeProblemCommand;
use Icinga\Module\Monitoring\Command\Object\AddCommentCommand;
use Icinga\Module\Monitoring\Command\Object\ApiScheduleHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\DeleteCommentCommand;
use Icinga\Module\Monitoring\Command\Object\DeleteDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ProcessCheckResultCommand;
use Icinga\Module\Monitoring\Command\Object\PropagateHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\RemoveAcknowledgementCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleHostDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceCheckCommand;
use Icinga\Module\Monitoring\Command\Object\ScheduleServiceDowntimeCommand;
use Icinga\Module\Monitoring\Command\Object\SendCustomNotificationCommand;
use Icinga\Module\Monitoring\Command\Object\ToggleObjectFeatureCommand;
use Icinga\Module\Monitoring\Command\IcingaCommand;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use InvalidArgumentException;

/**
 * Icinga command renderer for the Icinga command file
 */
class IcingaApiCommandRenderer implements IcingaCommandRendererInterface
{
    /**
     * Name of the Icinga application object
     *
     * @var string
     */
    protected $app = 'app';

    /**
     * Get the name of the Icinga application object
     *
     * @return string
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Set the name of the Icinga application object
     *
     * @param   string  $app
     *
     * @return  $this
     */
    public function setApp($app)
    {
        $this->app = $app;

        return $this;
    }

    /**
     * Apply filter to query data
     *
     * @param   array           $data
     * @param   MonitoredObject $object
     *
     * @return  array
     */
    protected function applyFilter(array &$data, MonitoredObject $object)
    {
        if ($object->getType() === $object::TYPE_HOST) {
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            $data['host'] = $object->getName();
        } else {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $data['service'] = sprintf('%s!%s', $object->getHost()->getName(), $object->getName());
        }
    }

    /**
     * Render a command
     *
     * @param   IcingaCommand   $command
     *
     * @return  IcingaApiCommand
     */
    public function render(IcingaCommand $command)
    {
        $renderMethod = 'render' . $command->getName();
        if (! method_exists($this, $renderMethod)) {
            die($renderMethod);
        }
        return $this->$renderMethod($command);
    }

    public function renderAddComment(AddCommentCommand $command)
    {
        $endpoint = 'actions/add-comment';
        $data = array(
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment()
        );

        if ($command->getExpireTime() !== null) {
            $data['expiry'] = $command->getExpireTime();
        }

        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderSendCustomNotification(SendCustomNotificationCommand $command)
    {
        $endpoint = 'actions/send-custom-notification';
        $data = array(
            'author'    => $command->getAuthor(),
            'comment'   => $command->getComment(),
            'force'     => $command->getForced()
        );
        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderProcessCheckResult(ProcessCheckResultCommand $command)
    {
        $endpoint = 'actions/process-check-result';
        $data = array(
            'exit_status'       => $command->getStatus(),
            'plugin_output'     => $command->getOutput(),
            'performance_data'  => $command->getPerformanceData()
        );
        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleCheck(ScheduleServiceCheckCommand $command)
    {
        $endpoint = 'actions/reschedule-check';
        $data = array(
            'next_check'    => $command->getCheckTime(),
            'force'         => $command->getForced()
        );
        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderScheduleDowntime(ScheduleServiceDowntimeCommand $command)
    {
        $endpoint = 'actions/schedule-downtime';
        $data = array(
            'author'        => $command->getAuthor(),
            'comment'       => $command->getComment(),
            'start_time'    => $command->getStart(),
            'end_time'      => $command->getEnd(),
            'duration'      => $command->getDuration(),
            'fixed'         => $command->getFixed(),
            'trigger_name'  => $command->getTriggerId()
        );
        $commandData = $data;

        if ($command instanceof PropagateHostDowntimeCommand) {
            $commandData['child_options'] = $command->getTriggered() ? 1 : 2;
        } elseif ($command instanceof ApiScheduleHostDowntimeCommand) {
            // We assume that it has previously been verified that the Icinga version is
            // equal to or greater than 2.11.0
            $commandData['child_options'] = $command->getChildOptions();
        }

        $allServicesCompat = false;
        if ($command instanceof ScheduleHostDowntimeCommand) {
            if ($command->isForAllServicesNative()) {
                // We assume that it has previously been verified that the Icinga version is
                // equal to or greater than 2.11.0
                $commandData['all_services'] = $command->getForAllServices();
            } else {
                $allServicesCompat = $command->getForAllServices();
            }
        }

        $this->applyFilter($commandData, $command->getObject());
        $apiCommand = IcingaApiCommand::create($endpoint, $commandData);

        if ($allServicesCompat) {
            $commandData = $data + [
                'type'        => 'Service',
                'filter'      => 'host.name == host_name',
                'filter_vars' => [
                    'host_name' => $command->getObject()->getName()
                ]
            ];
            $apiCommand->setNext(IcingaApiCommand::create($endpoint, $commandData));
        }

        return $apiCommand;
    }

    public function renderAcknowledgeProblem(AcknowledgeProblemCommand $command)
    {
        $endpoint = 'actions/acknowledge-problem';
        $data = array(
            'author'        => $command->getAuthor(),
            'comment'       => $command->getComment(),
            'sticky'        => $command->getSticky(),
            'notify'        => $command->getNotify(),
            'persistent'    => $command->getPersistent()
        );
        if ($command->getExpireTime() !== null) {
            $data['expiry'] = $command->getExpireTime();
        }
        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleObjectFeature(ToggleObjectFeatureCommand $command)
    {
        if ($command->getEnabled() === true) {
            $enabled = true;
        } else {
            $enabled = false;
        }
        switch ($command->getFeature()) {
            case ToggleObjectFeatureCommand::FEATURE_ACTIVE_CHECKS:
                $attr = 'enable_active_checks';
                break;
            case ToggleObjectFeatureCommand::FEATURE_PASSIVE_CHECKS:
                $attr = 'enable_passive_checks';
                break;
            case ToggleObjectFeatureCommand::FEATURE_NOTIFICATIONS:
                $attr = 'enable_notifications';
                break;
            case ToggleObjectFeatureCommand::FEATURE_EVENT_HANDLER:
                $attr = 'enable_event_handler';
                break;
            case ToggleObjectFeatureCommand::FEATURE_FLAP_DETECTION:
                $attr = 'enable_flapping';
                break;
            default:
                throw new InvalidArgumentException($command->getFeature());
        }
        $endpoint = 'objects/';
        $object = $command->getObject();
        if ($object->getType() === ToggleObjectFeatureCommand::TYPE_HOST) {
            /** @var \Icinga\Module\Monitoring\Object\Host $object */
            $endpoint .= 'hosts';
        } else {
            /** @var \Icinga\Module\Monitoring\Object\Service $object */
            $endpoint .= 'services';
        }
        $data = array(
            'attrs' => array(
                $attr => $enabled
            )
        );
        $this->applyFilter($data, $object);
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteComment(DeleteCommentCommand $command)
    {
        $endpoint = 'actions/remove-comment';
        $data = [
            'author'    => $command->getAuthor(),
            'comment'   => $command->getCommentName()
        ];
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderDeleteDowntime(DeleteDowntimeCommand $command)
    {
        $endpoint = 'actions/remove-downtime';
        $data = [
            'author'    => $command->getAuthor(),
            'downtime'  => $command->getDowntimeName()
        ];
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderRemoveAcknowledgement(RemoveAcknowledgementCommand $command)
    {
        $endpoint = 'actions/remove-acknowledgement';
        $data = ['author' => $command->getAuthor()];
        $this->applyFilter($data, $command->getObject());
        return IcingaApiCommand::create($endpoint, $data);
    }

    public function renderToggleInstanceFeature(ToggleInstanceFeatureCommand $command)
    {
        $endpoint = 'objects/icingaapplications/' . $this->getApp();
        if ($command->getEnabled() === true) {
            $enabled = true;
        } else {
            $enabled = false;
        }
        switch ($command->getFeature()) {
            case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_HOST_CHECKS:
                $attr = 'enable_host_checks';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_ACTIVE_SERVICE_CHECKS:
                $attr = 'enable_service_checks';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_EVENT_HANDLERS:
                $attr = 'enable_event_handlers';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_FLAP_DETECTION:
                $attr = 'enable_flapping';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_NOTIFICATIONS:
                $attr = 'enable_notifications';
                break;
            case ToggleInstanceFeatureCommand::FEATURE_PERFORMANCE_DATA:
                $attr = 'enable_perfdata';
                break;
            default:
                throw new InvalidArgumentException($command->getFeature());
        }
        $data = array(
            'attrs' => array(
                $attr => $enabled
            )
        );
        return IcingaApiCommand::create($endpoint, $data);
    }
}
