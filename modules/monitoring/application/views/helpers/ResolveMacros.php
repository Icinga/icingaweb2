<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_View_Helper_Abstract;
use Icinga\Module\Monitoring\Object\MonitoredObject;

class Zend_View_Helper_ResolveMacros extends Zend_View_Helper_Abstract
{
    /**
     * Known icinga macros
     *
     * @var array
     */
    private $icingaMacros = array(
        'HOSTNAME'      => 'host_name',
        'HOSTADDRESS'   => 'host_address',
        'SERVICEDESC'   => 'service_description'
    );

    /**
     * Return the given string with macros being resolved
     *
     * @param   string                      $input      The string in which to look for macros
     * @param   MonitoredObject|stdClass     $object     The host or service used to resolve macros
     *
     * @return  string                                  The substituted or unchanged string
     */
    public function resolveMacros($input, $object)
    {
        $matches = array();
        if (preg_match_all('@\$([^\$\s]+)\$@', $input, $matches)) {
            foreach ($matches[1] as $key => $value) {
                $newValue = $this->resolveMacro($value, $object);
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
     * @param   MonitoredObject|stdClass     $object     The object used to resolve the macro
     *
     * @return  string                                  The new value or the macro if it cannot be resolved
     */
    public function resolveMacro($macro, $object)
    {
        if (array_key_exists($macro, $this->icingaMacros) && $object->{$this->icingaMacros[$macro]} !== false) {
            return $object->{$this->icingaMacros[$macro]};
        }
        if (array_key_exists($macro, $object->customvars)) {
            return $object->customvars[$macro];
        }

        return $macro;
    }
}
