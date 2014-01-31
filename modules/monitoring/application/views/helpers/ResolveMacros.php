<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2014 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2014 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Zend_View_Helper_Abstract;
use Icinga\Module\Monitoring\Object\AbstractObject;

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
     * @param   AbstractObject|stdClass     $object     The host or service used to resolve macros
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
     * @param   AbstractObject|stdClass     $object     The object used to resolve the macro
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
