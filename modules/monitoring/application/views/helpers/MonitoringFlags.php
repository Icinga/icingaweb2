<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

/**
 * Class Zend_View_Helper_MonitoringFlags
 *
 * Rendering helper for flags depending on objects
 */
class Zend_View_Helper_MonitoringFlags extends Zend_View_Helper_Abstract
{
    /**
     * Key of flags without prefix (e.g. host or service)
     * @var string[]
     */
    private static $keys = array(
        'passive_checks_enabled' => 'Passive checks',
        'active_checks_enabled' => 'Active checks',
        'obsess_over_host' => 'Obsessing',
        'notifications_enabled' => 'Notifications',
        'event_handler_enabled' => 'Event handler',
        'flap_detection_enabled' => 'Flap detection',
    );

    /**
     * Type prefix
     * @param array $vars
     * @return string
     */
    private function getObjectType(array $vars)
    {
        return array_shift(explode('_', array_shift(array_keys($vars)), 2));
    }

    /**
     * Build all existing flags to a readable array
     * @param stdClass $object
     * @return array
     */
    public function monitoringFlags(\stdClass $object)
    {
        $vars = (array)$object;
        $type = $this->getObjectType($vars);
        $out = array();

        foreach (self::$keys as $key => $name) {
            $value = false;
            if (array_key_exists(($realKey = $type. '_'. $key), $vars)) {
                $value = $vars[$realKey] === '1' ? true : false;
            }
            $out[$name] = $value;
        }

        return $out;
    }
}