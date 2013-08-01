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

/**
 * A ini file adapter that preserves comments in the existing ini file, when writing changes to it
 */
class PreservingIniWriter extends \Zend_Config_Writer
{
    /**
     * The file that is written to
     *
     * @var string
     */
    private $filename;

    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    function __construct(array $options)
    {
        parent::__construct($options);
    }

    /**
     * Write the config to the file
     */
    public function write()
    {
        if (empty($this->filename)) {
            throw new Exception('No filename for configuration provided');
        }
        $oldConfig = parse_ini_file($this->filename);
        $newConfig = $this->_config;
        $diff = $this->createPropertyDiff($oldConfig,$newConfig);
    }

    /**
     * Create a diff between the properties of two Zend_Config objects
     *
     * @param \Zend_Config $oldConfig
     * @param \Zend_Config $newConfig
     */
    private function createConfigDiff(\Zend_Config $oldConfig, \Zend_Config $newConfig)
    {
        // TODO: Find deleted sections in old
    }

    private function updateIniWithDiff($fileDiff)
    {
        $iniReader = new IniKeyPositionReader();
        $editor = new FileEditor($this->filename);
        foreach ($fileDiff as $key => $diff) {

        }
    }

    private function unwrapKeys($parents,$diffs)
    {

    }
}

/**
 * Can read information about the position of ini-file keys and values
 * from
 */
class IniKeyPositionReader
{
    public function getKeyStart(array $parents,String $key)
    {
        // return line
    }

    public function getKeyContainerFirstEmpty(String $section)
    {
        // return line
    }

    public function getKeyLine(String $section,String $key)
    {
        // return line
    }
}

/**
 * Edit a file line by line
 *
 * The functions delete, insert and update can be applied to certain lines
 * of the file and are written to it, once applyChanges is called. Line inserts and deletes
 * are handled automatically and the changes in line numbers don't need to be respected when
 * calling the edit functions.
 */
class FileEditor
{
    /**
     * @var String
     */
    private $filename;

    /**
     * The symbol that delimits a comment.
     *
     * @var string
     */
    private $commentDelimiter = '';

    /**
     * Set a new comment delimiter
     */
    public function setCommentDelimiter(String $delimiter)
    {
        $this->commentDelimiter = $delimiter;
        return $this;
    }

    /**
     * Get the current comment delimiter
     *
     * @return string The comment delimiter
     */
    public function getCommentDelimiter()
    {
        return $this->commentDelimiter;
    }

    /**
     * Create a new FileEditor
     *
     * @param $filename The file that should be edited.
     */
    public function __constructor($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Delete a line
     *
     * @param $line The line
     */
    public function delete($line)
    {

    }

    /**
     * Insert a text into the file
     *
     * @param $line The line where the text should be inserted
     * @param $text The text
     */
    public function insert($line,$text)
    {

    }

    /**
     * Update the given line and insert $text
     *
     * Update the line but ignore text separated by a comment delimiter.
     *
     * @param $line The line number
     * @param $text The text that will be inserted
     */
    public function update($line,$text)
    {

    }

    /**
     * Write changes to the file
     */
    public function applyChanges()
    {

    }
}

/**
 * A diff that describes the change of an object property
 */
class PropertyDiff {

    /**
     * Create the property diff between two objects
     *
     * @param stdClass $oldObject The object representing the state before the change
     * @param stdClass $newObject The object representing the state after the change
     *
     * @return array An associative array mapping all changed properties to a property diff
     * describing the change
     */
    public static function createObjectDiff(stdClass $oldObject,stdClass $newObject)
    {
        $diffs = array();
        /*
         * Search inserted or updated properties
         */
        foreach ($newObject as $key => $value) {
            $newProperty = $value;
            $oldProperty = $oldObject->{$key};
            if (is_array($newProperty)) {
                if (empty($oldProperty)) {
                    $diffs[$key] = new PropertyDiff(
                        PropertyDiff::ACTION_INSERT,
                        PropertyDiff::createObjectDiff(new \stdClass(),$newObject));
                } else {
                    $diffs[$key] = new PropertyDiff(
                        PropertyDiff::ACTION_NONE,
                        PropertyDiff::createObjectDiff($oldObject,$newObject)
                    );
                }
            } else {
                if (empty($oldProperty)) {
                    $diffs[$key] =
                        new PropertyDiff(PropertyDiff::ACTION_INSERT,$newProperty);
                } elseif (strcasecmp($newProperty,$oldProperty) != 0) {
                    $diffs[$key] =
                        new PropertyDiff(PropertyDiff::ACTION_UPDATE,$newProperty);
                }
            }
        }
        /*
         * Search deleted properties
         */
        foreach ($oldObject as $key => $value) {
            if (empty($newObject->{$key})) {
                $oldProperty = $value;
                if (is_array($oldProperty)){
                    $diffs[key] = new PropertyDiff(
                        PropertyDiff::ACTION_DELETE,
                        PropertyDiff::createObjectDiff($oldObject,new \stdClass())
                    );
                } else {
                    $diffs[$key] =
                        new PropertyDiff(PropertyDiff::ACTION_DELETE,null);
                }
            }
        }
    }

    /**
     * The available action types
     */
    const ACTION_INSERT = 0;
    const ACTION_UPDATE = 1;
    const ACTION_DELETE = 2;
    const ACTION_NONE = 3;

    /**
     * The action described by this diff
     *
     * @var String
     */
    public $action;

    /**
     * The value after the change
     *
     * @var StdClass
     */
    public $value;

    /**
     * Create a new PropertyDiff
     *
     * @param int $action The action described by this diff
     * @param string $value The value after the change
     */
    public function Diff($action, $value)
    {
        if (action != ACTION_CREATE &&
            action != ACTION_UPDATE &&
            action != ACTION_DELETE) {
            throw new \Exception('Invalid action code: '.$action);
        }
        $this->action = $action;
        $this->value = $value;
    }
}

