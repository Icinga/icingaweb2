<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Object;

/**
 * Expand macros in string in the context of MonitoredObjects
 */
class Macro
{
    /**
     * Known icinga macros
     *
     * @var array
     */
    private static $icingaMacros = array(
        'HOSTNAME'     => 'host_name',
        'HOSTADDRESS'  => 'host_address',
        'SERVICEDESC'  => 'service_description',
        'host.name'    => 'host_name',
        'host.address' => 'host_address',
        'service.description' => 'service_description'
    );

    /**
     * Return the given string with macros being resolved
     *
     * @param   string                      $input      The string in which to look for macros
     * @param   MonitoredObject|stdClass    $object     The host or service used to resolve macros
     *
     * @return  string                                  The substituted or unchanged string
     */
    public static function resolveMacros($input, $object)
    {
        $matches = array();
        if (preg_match_all('@\$([^\$\s]+)\$@', $input, $matches)) {
            foreach ($matches[1] as $key => $value) {
                $newValue = self::resolveMacro($value, $object);
                if ($newValue !== $value) {
                    $input = str_replace($matches[0][$key], $newValue, $input);
                }
            }
        }

        return $input;
    }

    /**
     * Resolve a macro based on the given object
     *
     * @param   string                      $macro      The macro to resolve
     * @param   MonitoredObject|stdClass    $object     The object used to resolve the macro
     *
     * @return  string                                  The new value or the macro if it cannot be resolved
     */
    public static function resolveMacro($macro, $object)
    {
        if (array_key_exists($macro, self::$icingaMacros) && $object->{self::$icingaMacros[$macro]} !== false) {
            return $object->{self::$icingaMacros[$macro]};
        }
        if (array_key_exists($macro, $object->customvars)) {
            return $object->customvars[$macro];
        }

        return $macro;
    }
}
