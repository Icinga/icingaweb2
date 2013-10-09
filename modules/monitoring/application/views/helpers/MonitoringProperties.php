<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Module\Monitoring\Object\AbstractObject;

/**
 * Class Zend_View_Helper_MonitoringProperties
 */
class Zend_View_Helper_MonitoringProperties extends Zend_View_Helper_Abstract
{
    /**
     * Value for check type active
     */
    const CHECK_ACTIVE = 'ACTIVE';

    /**
     * Value for check type passive
     */
    const CHECK_PASSIVE = 'PASSIVE';

    /**
     * Value for check type disabled
     */
    const CHECK_DISABLED = 'DISABLED';

    /**
     * Return value for not available
     */
    const VALUE_NA = 'N/A';

    /**
     * Return value for "YES"
     */
    const VALUE_YES = 'YES';

    /**
     * Return value for "NO"
     */
    const VALUE_NO = 'NO';

    /**
     * Label / value mapping for object keys
     *
     * Keys can be callables in this object
     *
     * @var array
     */
    private static $keys = array(
        'buildAttempt' => 'Current Attempt',
        'buildCheckType' => 'Check Type',
        'buildLatency' => 'Check Latency / Duration',
        'buildLastStateChange' => 'Last State Change',
        'buildLastNotification' => 'Last Notification',
        'buildFlapping' => 'Is This %s Flapping?',
        'buildScheduledDowntime' => 'In Scheduled Downtime?',
        'status_update_time' => 'Last Update'
    );

    private static $notificationReasons = array(
        0 => 'NORMAL',
        1 => 'ACKNOWLEDGEMENT',
        2 => 'FLAPPING START',
        3 => 'FLAPPING STOP',
        4 => 'FLAPPING DISABLED',
        5 => 'DOWNTIME START',
        6 => 'DOWNTIME END',
        7 => 'DOWNTIME CANCELLED',
        8 => 'CUSTOM',
        9 => 'STALKING'
    );

    /**
     * Return the object type
     * @param stdClass $object
     * @return mixed
     */
    private function getObjectType($object)
    {
        $keys = array_keys(get_object_vars($object));
        $keyParts = explode('_', array_shift($keys), 2);
        return array_shift($keyParts);
    }

    /**
     * Drop all object specific attribute prefixes
     * @param stdClass $object
     * @param $type
     * @return object
     */
    private function dropObjectType($object, $type)
    {
        $vars = get_object_vars($object);
        $out = array();
        foreach ($vars as $name => $value) {
            $name = str_replace($type. '_', '', $name);
            $out[$name] = $value;
        }
        return (object)$out;
    }

    /**
     * Get string for attempt
     * @param stdClass $object
     * @return string
     */
    private function buildAttempt($object)
    {
        return sprintf(
            '%s/%s (%s state)',
            $object->current_check_attempt,
            $object->max_check_attempts,
            ($object->state_type === '1') ? 'HARD' : 'SOFT'
        );
    }

    /**
     * Generic fomatter for float values
     * @param $value
     * @return string
     */
    private function floatFormatter($value)
    {
        return sprintf('%.4f', $value);
    }

    /**
     * Get the string for check type
     * @param stdClass $object
     * @return string
     */
    private function buildCheckType($object)
    {
        if ($object->passive_checks_enabled === '1' && $object->active_checks_enabled === '0') {
            return self::CHECK_PASSIVE;
        } elseif ($object->passive_checks_enabled === '0' && $object->active_checks_enabled === '0') {
            return self::CHECK_DISABLED;
        }

        return self::CHECK_ACTIVE;
    }

    /**
     * Get string for latency
     * @param stdClass $object
     * @return string
     */
    private function buildLatency($object)
    {
        $val = '';
        if ($this->buildCheckType($object) === self::CHECK_PASSIVE) {
            $val .= self::VALUE_NA;
        } else {
            $val .= $this->floatFormatter(
                (isset($object->check_latency)) ? $object->check_latency : 0
            );
        }

        $val .= ' / '. $this->floatFormatter(
            isset($object->check_execution_time) ? $object->check_execution_time : 0
        ). ' seconds';

        return $val;
    }

    /**
     * Get string for next check
     * @param stdClass $object
     * @return string
     */
    private function buildNextCheck($object)
    {
        if ($this->buildCheckType($object) === self::CHECK_PASSIVE) {
            return self::VALUE_NA;
        } else {
            return $object->next_check;
        }
    }

    /**
     * Get date for last state change
     * @param stdClass $object
     * @return string
     */
    private function buildLastStateChange($object)
    {
        return strftime('%Y-%m-%d %H:%M:%S', $object->last_state_change);
    }

    /**
     * Get string for "last notification"
     * @param stdClass $object
     * @return string
     */
    private function buildLastNotification($object)
    {
        $val = '';

        if ($object->last_notification === '0000-00-00 00:00:00') {
            $val .= self::VALUE_NA;
        } else {
            $val .= $object->last_notification;
        }

        $val .= sprintf(' (notification %d)', $object->current_notification_number);

        return $val;
    }

    /**
     * Get string for "is flapping"
     * @param stdClass $object
     * @return string
     */
    private function buildFlapping($object)
    {
        $val = '';

        if ($object->is_flapping === '0') {
            $val .= self::VALUE_NO;
        } else {
            $val .= self::VALUE_YES;
        }

        $val .= sprintf(' (%.2f%% state change)', $object->percent_state_change);

        return $val;
    }

    /**
     * Get string for scheduled downtime
     * @param stdClass $object
     * @return string
     */
    private function buildScheduledDowntime($object)
    {
        if ($object->in_downtime === '1') {
            return self::VALUE_YES;
        }

        return self::VALUE_NO;
    }

    /**
     * Get an array which represent monitoring properties
     *
     * @param stdClass $object
     * @return array
     */
    public function monitoringProperties($object)
    {
        $type = $this->getObjectType($object);
        //$object = $this->dropObjectType($object, $type);

        $out = array();
        foreach (self::$keys as $property => $label) {
            $label = sprintf($label, ucfirst($type));
            if (is_callable(array(&$this, $property))) {
                $out[$label] = $this->$property($object);
            } elseif (isset($object->{$property})) {
                $out[$label] = $object->{$property};
            }
        }

        return $out;
    }

    public function getNotificationType($notification)
    {
        $reason = intval($notification->notification_reason);
        if (!isset(self::$notificationReasons[$reason])) {
            return 'N/A';
        }
        $type = self::$notificationReasons[$reason];
        if ($reason === 8) {
            if (intval($notification->notification_type) === 0) {
                $type .= '(UP)';
            } else {
                $type .= '(OK)';
            }
        }
        return $type;
    }
}
// @codingStandardsIgnoreStop
