<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

/* use Icinga\Module\Monitoring\Object\MonitoredObject; */

/**
 * Rendering helper for object's properties which may be either enabled or disabled
 */
class Zend_View_Helper_MonitoringFlags extends Zend_View_Helper_Abstract
{
    /**
     * Object's properties which may be either enabled or disabled and their human readable description
     *
     * @var string[]
     */
    private static $flags = array(
        'passive_checks_enabled'    => 'Passive Checks',
        'active_checks_enabled'     => 'Active Checks',
        'obsessing'                 => 'Obsessing',
        'notifications_enabled'     => 'Notifications',
        'event_handler_enabled'     => 'Event Handler',
        'flap_detection_enabled'    => 'Flap Detection',
    );

    /**
     * Retrieve flags as array with either true or false as value
     *
     * @param   MonitoredObject $object
     *
     * @return  array
     */
    public function monitoringFlags(/*MonitoredObject*/ $object)
    {
        $flags = array();
        foreach (self::$flags as $column => $description) {
            $flags[$description] = (bool) $object->{$column};
        }
        return $flags;
    }
}
