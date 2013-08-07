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

namespace Icinga\Config;

use Zend_Config;
use Zend_Config_Ini;

/**
 * A ini file adapter that respects the file structure and the comments of already
 * existing ini files
 */
class PreservingIniWriter extends \Zend_Config_Writer_FileAbstract
{
    /**
     * Create a new instance of PreservingIniWriter
     *
     * @param array $options The options passed to the base class
     */
    function __construct(array $options)
    {
        parent::__construct($options);
    }

    /**
     * Render the Zend_Config into a config file string
     *
     * @return string
     */
    public function render()
    {
        $oldconfig = new Zend_Config_Ini($this->_filename);
        $newconfig = $this->_config;
        $editor = new IniEditor(file_get_contents($this->_filename));
        $this->diffConfigs($oldconfig,$newconfig,$editor);
        return $editor->getText();
    }

    /**
     * Create a property diff and apply the changes to the editor
     *
     * @param Zend_Config   $oldconfig    The config representing the state before the change
     * @param Zend_Config   $newconfig    The config representing the state after the change
     * @param IniEditor     $eeditor      The editor that should be used to edit the old config file
     * @param array         $parents      The parent keys that should be respected when editing the config
     */
    private function diffConfigs(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        $this->diffPropertyUpdates($oldconfig,$newconfig,$editor,$parents);
        $this->diffPropertyDeletions($oldconfig,$newconfig,$editor,$parents);
    }

    /**
     * Search for created and updated properties and use the editor to create or update these entries
     *
     * @param Zend_Config   $oldconfig    The config representing the state before the change
     * @param Zend_Config   $newconfig    The config representing the state after the change
     * @param IniEditor     $eeditor      The editor that should be used to edit the old config file
     * @param array         $parents      The parent keys that should be respected when editing the config
     */
    private function diffPropertyUpdates(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        /*
         * The current section. This value is null when processing
         * the section-less root element
         */
        $section = empty($parents) ? null : $parents[0];

        /*
         * Iterate over all properties in the new configuration file and search for changes
         */
        foreach ($newconfig as $key => $value) {
            $oldvalue = $oldconfig->get($key);
            $nextParents = array_merge($parents,array($key));
            $keyIdentifier = empty($parents) ?
                array($key) : array_slice($nextParents,1,null,true);

            if ($value instanceof Zend_Config) {
                /*
                 * The value is a nested Zend_Config, handle it recursively
                 */
                if (!isset($section)) {
                    /*
                     * Update the section declaration
                     */
                    $extends = $newconfig->getExtends();
                    $extend = array_key_exists($key,$extends) ?
                        $extends[$key] : null;
                    $editor->setSection($key,$extend);
                }
                if (!isset($oldvalue)) {
                    $oldvalue = new Zend_Config(array());
                }
                $this->diffConfigs($oldvalue,$value,$editor,$nextParents);
            } else {
                /*
                 * The value is a plain value, use the editor to set it
                 */
                if (is_numeric($key)){
                    $editor->setArrayElement($keyIdentifier,$value,$section);
                } else {
                    $editor->set($keyIdentifier,$value,$section);
                }
            }
        }
    }

    /**
     * Search for deleted properties and use the editor to delete these entries
     *
     * @param Zend_Config   $oldconfig    The config representing the state before the change
     * @param Zend_Config   $newconfig    The config representing the state after the change
     * @param IniEditor     $eeditor      The editor that should be used to edit the old config file
     * @param array         $parents      The parent keys that should be respected when editing the config
     */
    private function diffPropertyDeletions(
        Zend_Config $oldconfig,
        Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array()
    ) {
        /*
         * The current section. This value is null when processing
         * the section-less root element
         */
        $section = empty($parents) ? null : $parents[0];

        /*
         * Iterate over all properties in the old configuration file and search for
         * deleted properties
         */
        foreach ($oldconfig as $key => $value) {
            $nextParents = array_merge($parents,array($key));
            $newvalue = $newconfig->get($key);
            $keyIdentifier = empty($parents) ?
                array($key) : array_slice($nextParents,1,null,true);

            if (!isset($newvalue)) {
                if ($value instanceof Zend_Config) {
                    /*
                     * The deleted value is a nested Zend_Config, handle it recursively
                     */
                    $this->diffConfigs($value,new Zend_Config(array()),$editor,$nextParents);
                    if (!isset($section)) {
                        $editor->removeSection($key);
                    }
                } else {
                    /*
                     * The deleted value is a plain value, use the editor to delete it
                     */
                    if (is_numeric($key)) {
                        $editor->resetArrayElement($keyIdentifier,$section);
                    } else {
                        $editor->reset($keyIdentifier,$section);
                    }
                }
            }
        }
    }
}

