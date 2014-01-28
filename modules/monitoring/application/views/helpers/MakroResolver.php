<?php
// @codingStandardsIgnoreStart

// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

class Zend_View_Helper_MakroResolver extends Zend_View_Helper_Abstract
{

    private $mapping = array (
        'HOSTADDRESS' => 'host_address',
        'HOSTNAME' => 'host_name',
        'SERVICEDESC' => 'service_description'
    );
    
    private $costumPrefix = array (
        '_HOST' => 'host_',
        '_Service' => 'service_',
        '_CONTACT' => 'contact_'
    );
    
    private $object;
    private $escape;

    public function makroResolver($string, $object, $escape = true)
    {
        $this->object = $object;
        $this->object->host_macaddress = "123.123.123.123";
        $this->escape = $escape;
        $values = explode('$', $string);
        
        foreach ($values as $value){
            $resolved[] = $this->replace($value);
        }
        $string = implode('', $resolved);
        
        die(var_dump($object));
        
        return $string."<br>";
    }
    
    
    private function replace($piece)
    {
        if($piece == 'COSTUM'){
            $piece = $this->mapping["$piece"];
        }
        if(array_key_exists($piece, $this->mapping))
        {
            $var = $this->mapping["$piece"];
            $piece = $this->object->$var;
        } else {
            $piece = $this->checkCostumVars($piece);
        }
        ($this->escape) ? $piece = $this->escapeString($piece) : '' ;
        
        
        //echo $this->object;
        return $piece;
    }
    
    private function checkCostumVars($value)
    {
        if($value != '') {
            foreach ($this->costumPrefix as $prefix => $val) {
                if(strstr($value, $prefix)){    
                    $costumVar = $val.strtolower(str_replace($prefix, '', $value));
                    if(array_key_exists($costumVar, $this->object)) {
                        $value = $this->object->$costumVar;
                    }
                }
            }
        }
        
        return $value;
    }
    
    private function escapeString($string)
    {
        return htmlspecialchars($string);
    }
}


// @codingStandardsIgnoreStop