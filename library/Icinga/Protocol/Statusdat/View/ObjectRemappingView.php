<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 * 
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Protocol\Statusdat\View;

/**
 * Class ObjectRemappingView
 *
 * Dataview that maps generic field names to storage specific fields or requests them via handlers.
 *
 * When accessing objects, every storage api returns them with other names. You can't simply say
 * $object->service_state, because this field is, e.g. under status.current_state in the status.dat
 * view, while IDO uses servicestate->current_state.
 *
 * This view is intended for normalizing these changes, so a request of service_state returns the
 * right field for the backend. When implementing it, you have to fill the mappedParameters and/or
 * the handlerParameters array. While mappedParameters simply translate logic field names to
 * storage specific ones, handlerParameters determins functions that handle data retrieval for
 * the specific fields.
 *
 */


class ObjectRemappingView implements AccessorStrategy
{

    /**
     * When implementing your own Mapper, this contains the static mapping rules.
     * @see Monitoring\Backend\Statusdat\DataView\StatusdatServiceView for an example
     *
     * @var array
     */
    public static $mappedParameters = array();

    private $functionMap = array(
        "TO_DATE" => "toDateFormat"
    );

    /**
     * When implementing your own Mapper, this contains the handler for specific fields and allows you to lazy load
     * different fields if necessary. The methods are strings that will be mapped to methods of this class
     *
     * @see Icinga\Backend\Statusdat\DataView\StatusdatServiceView for an example
     *
     * @var array
     */
    protected $handlerParameters = array();

    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return The|string
     * @throws \InvalidArgumentException
     */
    public function get(&$item, $field)
    {

        if (isset($item->$field)) {
            return $item->$field;
        }
        if (isset(static::$mappedParameters[$field])) {
            return $this->getMappedParameter($item, $field);
        }

        if (isset($this->handlerParameters[$field])) {
            $hdl = $this->handlerParameters[$field];
            return $this->$hdl($item);
        }
        throw new \InvalidArgumentException("Field $field does not exist for status.dat services");
    }

    private function applyPropertyFunction($function, $value)
    {
        if (!isset($this->functionMap[$function])) {
            return $value;
        }
        $fn = $this->functionMap[$function];

        return $this->$fn($value);
    }

    private function toDateFormat($value)
    {
        if (is_numeric($value)) {
            return date("Y-m-d H:i:s", intval($value));
        } else {
            return $value;
        }
    }

    private function getMappedParameter(&$item, $field)
    {
        $matches = array();
        $fieldDef = static::$mappedParameters[$field];
        $function = false;
        if (preg_match_all('/(?P<FUNCTION>\w+)\((?P<PARAMETER>.*)\)/', $fieldDef, $matches)) {
            $function = $matches["FUNCTION"][0];
            $fieldDef = $matches["PARAMETER"][0];
        }
        $mapped = explode(".", $fieldDef);
        $res = $item;

        foreach ($mapped as $map) {
            if (!isset($res->$map)) {
                return "";
            }
            $res = $res->$map;
        }
        if ($function) {
            return $this->applyPropertyFunction($function, $res);
        }
        return $res;
    }

    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $field
     * @return The|string
     */
    public function getNormalizedFieldName($field)
    {
        if (isset(static::$mappedParameters[$field])) {
            return static::$mappedParameters[$field];
        }
        return $field;
    }

    /**
     *
     * @see Icinga\Backend\DataView\AccessorStrategy
     *
     * @param The $item
     * @param The $field
     * @return bool
     */
    public function exists(&$item, $field)
    {
        return (isset($item->$field)
            || isset(static::$mappedParameters[$field])
            || isset($this->handlerParameters[$field])
        );
    }
}
