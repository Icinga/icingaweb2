<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Backend\DataView;

/**
 * Class AbstractAccessorStrategy
 * Basic interface for views.
 * The name sound weirder than it is: Views define special get and exists operations for fields
 * that are not directly available in a resultset, but exist under another name or can be
 * accessed by loading an additional object during runtime.
 *
 * @see Icinga\Backend\DataView\ObjectRemappingView  For an implementation of mapping field names
 * to storage specific names, e.g. service_state being status.current_state in status.dat views.
 *
 * @see Icinga\Backend\MonitoringObjectList For the typical usage of this class. It is not wrapped
 * around the monitoring object, so we don't use __get() or __set() and always have to give the
 * item we'd like to access.
 * @package Icinga\Backend\DataView
 */
interface AbstractAccessorStrategy
{
    /**
     * Returns a field for the item, or throws an Exception if the field doesn't exist
     *
     * @param $item The item to access
     * @param $field The field of the item that should be accessed
     * @return string   The content of the field
     *
     * @throws \InvalidArgumentException when the field does not exist
     */
    public function get(&$item, $field);

    /**
     * Returns the name that the field has in the specific backend. Might not be available for every field/view
     * @param $field    The field name that should be translated
     * @return string   The real name of this field
     */
    public function getNormalizedFieldName($field);

    /**
     * Returns true if the field exists on the specific item, otherwise false
     *
     * @param $item     The item to access
     * @param $field    The field to check on the $item
     * @return bool  True when the field exists, otherwise false
     */
    public function exists(&$item, $field);
}
