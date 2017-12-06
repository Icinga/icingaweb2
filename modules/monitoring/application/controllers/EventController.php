<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Controllers;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Icinga\Data\Queryable;
use Icinga\Date\DateFormatter;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Util\TimezoneDetect;
use Icinga\Web\Url;
use Icinga\Web\Widget\Tabextension\DashboardAction;
use Icinga\Web\Widget\Tabextension\MenuAction;
use Icinga\Web\Widget\Tabextension\OutputFormat;

class EventController extends Controller
{
    /**
     * @var string[]
     */
    protected $dataViewsByType = array(
        'notify'                => 'notificationevent',
        'comment'               => 'commentevent',
        'comment_deleted'       => 'commentevent',
        'ack'                   => 'commentevent',
        'ack_deleted'           => 'commentevent',
        'dt_comment'            => 'commentevent',
        'dt_comment_deleted'    => 'commentevent',
        'flapping'              => 'flappingevent',
        'flapping_deleted'      => 'flappingevent',
        'hard_state'            => 'statechangeevent',
        'soft_state'            => 'statechangeevent',
        'dt_start'              => 'downtimeevent',
        'dt_end'                => 'downtimeevent'
    );

    /**
     * Cache for {@link time()}
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    public function showAction()
    {
        $type = $this->params->shiftRequired('type');
        $id = $this->params->shiftRequired('id');

        if (! isset($this->dataViewsByType[$type])
            || $this->applyRestriction(
                'monitoring/filter/objects',
                $this->backend->select()->from('eventhistory', array('id'))->where('id', $id)
            )->fetchRow() === false
        ) {
            $this->httpNotFound($this->translate('Event not found'));
        }

        $event = $this->query($type, $id)->fetchRow();

        if ($event === false) {
            $this->httpNotFound($this->translate('Event not found'));
        }

        $this->view->object = $object = $event->service_description === null
            ? new Host($this->backend, $event->host_name)
            : new Service($this->backend, $event->host_name, $event->service_description);
        $object->fetch();

        list($icon, $label) = $this->getIconAndLabel($type);

        $this->view->details = array_merge(
            array(array($this->view->escape($this->translate('Type')), $label)),
            $this->getDetails($type, $event)
        );

        $this->getTabs()
            ->add('event', array(
                'title'     => $label,
                'label'     => $label,
                'icon'      => $icon,
                'url'       => Url::fromRequest(),
                'active'    => true
            ))
            ->extend(new OutputFormat())
            ->extend(new DashboardAction())
            ->extend(new MenuAction());
    }

    /**
     * Return translated and escaped 'Yes' if the given condition is true, 'No' otherwise, 'N/A' if NULL
     *
     * @param   bool|null   $condition
     *
     * @return  string
     */
    protected function yesOrNo($condition)
    {
        if ($condition === null) {
            return $this->view->escape($this->translate('N/A'));
        }

        return $this->view->escape($condition ? $this->translate('Yes') : $this->translate('No'));
    }

    /**
     * Render the given timestamp as human readable HTML in the user agent's timezone or 'N/A' if NULL
     *
     * @param   int|null    $stamp
     *
     * @return  string
     */
    protected function time($stamp)
    {
        if ($stamp === null) {
            return $this->view->escape($this->translate('N/A'));
        }

        if ($this->timeZone === null) {
            $timezoneDetect = new TimezoneDetect();
            $this->timeZone = new DateTimeZone(
                $timezoneDetect->success() ? $timezoneDetect->getTimezoneName() : date_default_timezone_get()
            );
        }

        return $this->view->escape(
            DateTime::createFromFormat('U', $stamp)->setTimezone($this->timeZone)->format('Y-m-d H:i:s')
        );
    }

    /**
     * Render the given duration in seconds as human readable HTML or 'N/A' if NULL
     *
     * @param   int|null    $seconds
     *
     * @return  string
     */
    protected function duration($seconds)
    {
        return $this->view->escape(
            $seconds === null ? $this->translate('N/A') : DateFormatter::formatDuration($seconds)
        );
    }

    /**
     * Render the given percent number as human readable HTML or 'N/A' if NULL
     *
     * @param   float|null  $percent
     *
     * @return  string
     */
    protected function percent($percent)
    {
        return $this->view->escape(
            $percent === null ? $this->translate('N/A') : sprintf($this->translate('%.2f%%'), $percent)
        );
    }

    /**
     * Render the given comment message as HTML or 'N/A' if NULL
     *
     * @param   string|null $message
     *
     * @return  string
     */
    protected function comment($message)
    {
        return $this->view->nl2br($this->view->createTicketLinks($this->view->escapeComment($message)));
    }

    /**
     * Render a link to the given contact or 'N/A' if NULL
     *
     * @param   string|null $name
     *
     * @return  string
     */
    protected function contact($name)
    {
        return $name === null
            ? $this->view->escape($this->translate('N/A'))
            : $this->view->qlink($name, Url::fromPath('monitoring/show/contact', array('contact_name' => $name)));
    }

    /**
     * Render the given monitored object state as human readable HTML or 'N/A' if NULL
     *
     * @param   bool        $isService
     * @param   int|null    $state
     *
     * @return  string
     */
    protected function state($isService, $state)
    {
        if ($state === null) {
            return $this->view->escape($this->translate('N/A'));
        }

        try {
            $stateText = $isService
                ? Service::getStateText($state, true)
                : Host::getStateText($state, true);
        } catch (InvalidArgumentException $e) {
            return $this->view->escape($this->translate('N/A'));
        }

        return '<span class="badge state-' . ($isService ? Service::getStateText($state) : Host::getStateText($state))
            . '">&nbsp;</span><span class="state-label">' . $this->view->escape($stateText) . '</span>';
    }

    /**
     * Render the given plugin output as human readable HTML
     *
     * @param   string  $output
     *
     * @return  string
     */
    protected function pluginOutput($output)
    {
        return $this->view->getHelper('PluginOutput')->pluginOutput($output);
    }

    /**
     * Return the icon and the label for the given event type
     *
     * @param   string  $eventType
     *
     * @return  string[]
     */
    protected function getIconAndLabel($eventType)
    {
        switch ($eventType) {
            case 'notify':
                return array('bell', $this->translate('Notification', 'tooltip'));
            case 'comment':
                return array('comment-empty', $this->translate('Comment', 'tooltip'));
            case 'comment_deleted':
                return array('cancel', $this->translate('Comment removed', 'tooltip'));
            case 'ack':
                return array('ok', $this->translate('Acknowledged', 'tooltip'));
            case 'ack_deleted':
                return array('ok', $this->translate('Acknowledgement removed', 'tooltip'));
            case 'dt_comment':
                return array('plug', $this->translate('Downtime scheduled', 'tooltip'));
            case 'dt_comment_deleted':
                return array('plug', $this->translate('Downtime removed', 'tooltip'));
            case 'flapping':
                return array('flapping', $this->translate('Flapping started', 'tooltip'));
            case 'flapping_deleted':
                return array('flapping', $this->translate('Flapping stopped', 'tooltip'));
            case 'hard_state':
                return array('warning-empty', $this->translate('Hard state change'));
            case 'soft_state':
                return array('spinner', $this->translate('Soft state change'));
            case 'dt_start':
                return array('plug', $this->translate('Downtime started', 'tooltip'));
            case 'dt_end':
                return array('plug', $this->translate('Downtime ended', 'tooltip'));
        }
    }

    /**
     * Return a query for the given event ID of the given type
     *
     * @param   string  $type
     * @param   int     $id
     *
     * @return  Queryable
     */
    protected function query($type, $id)
    {
        switch ($this->dataViewsByType[$type]) {
            case 'downtimeevent':
                return $this->backend->select()
                    ->from('downtimeevent', array(
                        'entry_time'            => 'downtimeevent_entry_time',
                        'author_name'           => 'downtimeevent_author_name',
                        'comment_data'          => 'downtimeevent_comment_data',
                        'is_fixed'              => 'downtimeevent_is_fixed',
                        'scheduled_start_time'  => 'downtimeevent_scheduled_start_time',
                        'scheduled_end_time'    => 'downtimeevent_scheduled_end_time',
                        'was_started'           => 'downtimeevent_was_started',
                        'actual_start_time'     => 'downtimeevent_actual_start_time',
                        'actual_end_time'       => 'downtimeevent_actual_end_time',
                        'was_cancelled'         => 'downtimeevent_was_cancelled',
                        'is_in_effect'          => 'downtimeevent_is_in_effect',
                        'trigger_time'          => 'downtimeevent_trigger_time',
                        'host_name',
                        'service_description'
                    ))
                    ->where('downtimeevent_id', $id);
            case 'commentevent':
                return $this->backend->select()
                    ->from('commentevent', array(
                        'entry_type'            => 'commentevent_entry_type',
                        'comment_time'          => 'commentevent_comment_time',
                        'author_name'           => 'commentevent_author_name',
                        'comment_data'          => 'commentevent_comment_data',
                        'is_persistent'         => 'commentevent_is_persistent',
                        'comment_source'        => 'commentevent_comment_source',
                        'expires'               => 'commentevent_expires',
                        'expiration_time'       => 'commentevent_expiration_time',
                        'deletion_time'         => 'commentevent_deletion_time',
                        'host_name',
                        'service_description'
                    ))
                    ->where('commentevent_id', $id);
            case 'flappingevent':
                return $this->backend->select()
                    ->from('flappingevent', array(
                        'event_time'            => 'flappingevent_event_time',
                        'reason_type'           => 'flappingevent_reason_type',
                        'percent_state_change'  => 'flappingevent_percent_state_change',
                        'low_threshold'         => 'flappingevent_low_threshold',
                        'high_threshold'        => 'flappingevent_high_threshold',
                        'host_name',
                        'service_description'
                    ))
                    ->where('flappingevent_id', $id)
                    ->where('flappingevent_event_type', $type);
            case 'notificationevent':
                return $this->backend->select()
                    ->from('notificationevent', array(
                        'notification_reason'   => 'notificationevent_reason',
                        'start_time'            => 'notificationevent_start_time',
                        'end_time'              => 'notificationevent_end_time',
                        'state'                 => 'notificationevent_state',
                        'output'                => 'notificationevent_output',
                        'long_output'           => 'notificationevent_long_output',
                        'escalated'             => 'notificationevent_escalated',
                        'contacts_notified'     => 'notificationevent_contacts_notified',
                        'host_name',
                        'service_description'
                    ))
                    ->where('notificationevent_id', $id);
            case 'statechangeevent':
                return $this->backend->select()
                    ->from('statechangeevent', array(
                        'state_time'            => 'statechangeevent_state_time',
                        'state'                 => 'statechangeevent_state',
                        'current_check_attempt' => 'statechangeevent_current_check_attempt',
                        'max_check_attempts'    => 'statechangeevent_max_check_attempts',
                        'last_state'            => 'statechangeevent_last_state',
                        'last_hard_state'       => 'statechangeevent_last_hard_state',
                        'output'                => 'statechangeevent_output',
                        'long_output'           => 'statechangeevent_long_output',
                        'host_name',
                        'service_description'
                    ))
                    ->where('statechangeevent_id', $id)
                    ->where('statechangeevent_state_change', 1)
                    ->where('statechangeevent_state_type', $type);
        }
    }

    /**
     * Return the given event's data prepared for a name-value table
     *
     * @param   string      $type
     * @param   \stdClass   $event
     *
     * @return  string[][]
     */
    protected function getDetails($type, $event)
    {
        switch ($type) {
            case 'dt_start':
            case 'dt_end':
                $details = array(array(
                    array($this->translate('Entry time'), $this->time($event->entry_time)),
                    array($this->translate('Is fixed'), $this->yesOrNo($event->is_fixed)),
                    array($this->translate('Is in effect'), $this->yesOrNo($event->is_in_effect)),
                    array($this->translate('Was started'), $this->yesOrNo($event->was_started))
                ));

                if ($type === 'dt_end') {
                    $details[] = array(
                        array($this->translate('Was cancelled'), $this->yesOrNo($event->was_cancelled))
                    );
                }

                $details[] = array(
                    array($this->translate('Trigger time'), $this->time($event->trigger_time)),
                    array($this->translate('Scheduled start time'), $this->time($event->scheduled_start_time)),
                    array($this->translate('Actual start time'), $this->time($event->actual_start_time)),
                    array($this->translate('Scheduled end time'), $this->time($event->scheduled_end_time))
                );

                if ($type === 'dt_end') {
                    $details[] = array(
                        array($this->translate('Actual end time'), $this->time($event->actual_end_time)))
                    ;
                }

                $details[] = array(
                    array($this->translate('Author'), $this->contact($event->author_name)),
                    array($this->translate('Comment'), $this->comment($event->comment_data))
                );

                return call_user_func_array('array_merge', $details);
            case 'comment':
            case 'comment_deleted':
            case 'ack':
            case 'ack_deleted':
            case 'dt_comment':
            case 'dt_comment_deleted':
                switch ($event->entry_type) {
                    case 'comment':
                        $entryType = $this->translate('User comment');
                        break;
                    case 'downtime':
                        $entryType = $this->translate('Scheduled downtime');
                        break;
                    case 'flapping':
                        $entryType = $this->translate('Flapping');
                        break;
                    case 'ack':
                        $entryType = $this->translate('Acknowledgement');
                        break;
                    default:
                        $entryType = $this->translate('N/A');
                }

                switch ($event->comment_source) {
                    case 'icinga':
                        $commentSource = $this->translate('Icinga');
                        break;
                    case 'user':
                        $commentSource = $this->translate('User');
                        break;
                    default:
                        $commentSource = $this->translate('N/A');
                }

                return array(
                    array($this->translate('Time'), $this->time($event->comment_time)),
                    array($this->translate('Source'), $this->view->escape($commentSource)),
                    array($this->translate('Entry type'), $this->view->escape($entryType)),
                    array($this->translate('Author'), $this->contact($event->author_name)),
                    array($this->translate('Is persistent'), $this->yesOrNo($event->is_persistent)),
                    array($this->translate('Expires'), $this->yesOrNo($event->expires)),
                    array($this->translate('Expiration time'), $this->time($event->expiration_time)),
                    array($this->translate('Deletion time'), $this->time($event->deletion_time)),
                    array($this->translate('Message'), $this->comment($event->comment_data))
                );
            case 'flapping':
            case 'flapping_deleted':
                switch ($event->reason_type) {
                    case 'stopped':
                        $reasonType = $this->translate('Flapping stopped normally');
                        break;
                    case 'disabled':
                        $reasonType = $this->translate('Flapping was disabled');
                        break;
                    default:
                        $reasonType = $this->translate('N/A');
                }

                return array(
                    array($this->translate('Event time'), $this->time($event->event_time)),
                    array($this->translate('Reason'), $this->view->escape($reasonType)),
                    array($this->translate('State change'), $this->percent($event->percent_state_change)),
                    array($this->translate('Low threshold'), $this->percent($event->low_threshold)),
                    array($this->translate('High threshold'), $this->percent($event->high_threshold))
                );
            case 'notify':
                switch ($event->notification_reason) {
                    case 'normal_notification':
                        $notificationReason = $this->translate('Normal notification');
                        break;
                    case 'ack':
                        $notificationReason = $this->translate('Problem acknowledgement');
                        break;
                    case 'flapping_started':
                        $notificationReason = $this->translate('Flapping started');
                        break;
                    case 'flapping_stopped':
                        $notificationReason = $this->translate('Flapping stopped');
                        break;
                    case 'flapping_disabled':
                        $notificationReason = $this->translate('Flapping was disabled');
                        break;
                    case 'dt_start':
                        $notificationReason = $this->translate('Downtime started');
                        break;
                    case 'dt_end':
                        $notificationReason = $this->translate('Downtime ended');
                        break;
                    case 'dt_cancel':
                        $notificationReason = $this->translate('Downtime was cancelled');
                        break;
                    case 'custom_notification':
                        $notificationReason = $this->translate('Custom notification');
                        break;
                    default:
                        $notificationReason = $this->translate('N/A');
                }

                $details = array(
                    array($this->translate('Start time'), $this->time($event->start_time)),
                    array($this->translate('End time'), $this->time($event->end_time)),
                    array($this->translate('Reason'), $this->view->escape($notificationReason)),
                    array(
                        $this->translate('State'),
                        $this->state($event->service_description !== null, $event->state)
                    ),
                    array($this->translate('Escalated'), $this->yesOrNo($event->escalated)),
                    array($this->translate('Contacts notified'), (int) $event->contacts_notified),
                    array(
                        $this->translate('Output'),
                        $this->pluginOutput($event->output) .  $this->pluginOutput($event->long_output)
                    )
                );

                return $details;
            case 'hard_state':
            case 'soft_state':
                $isService = $event->service_description !== null;

                $details = array(
                    array($this->translate('State time'), $this->time($event->state_time)),
                    array($this->translate('State'), $this->state($isService, $event->state)),
                    array($this->translate('Check attempt'), $this->view->escape(sprintf(
                        $this->translate('%d of %d'),
                        (int) $event->current_check_attempt,
                        (int) $event->max_check_attempts
                    ))),
                    array($this->translate('Last state'), $this->state($isService, $event->last_state)),
                    array($this->translate('Last hard state'), $this->state($isService, $event->last_hard_state)),
                    array(
                        $this->translate('Output'),
                        $this->pluginOutput($event->output) .  $this->pluginOutput($event->long_output)
                    )
                );

                return $details;
        }
    }
}
