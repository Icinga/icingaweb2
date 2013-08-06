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
        $oldconfig = new \Zend_Config_Ini($this->_filename);
        $newconfig = $this->_config;
        $editor = new IniEditor(file_get_contents($this->_filename));
        $this->diffConfigs($oldconfig,$newconfig,$editor);
        return $editor->getText();
    }

    /**
     * Create a property diff and apply the changes to the editor
     *
     * Compare two Zend_Config that represent the state change of an ini file and use the
     * IniEditor to write the changes back to the config, while preserving the structure and
     * the comments of the original file.
     *
     * @param Zend_Config $oldconfig The config representing the state before the change
     * @param Zend_Config $newconfig The config representing the state after the change
     * @param IniEditor $editor The editor that should be used to edit the old config file
     * @param array $parents The parent keys that should be respected when editing the config
     */
    private function diffConfigs(
        \Zend_Config $oldconfig,
        \Zend_Config $newconfig,
        IniEditor $editor,
        array $parents = array())
    {
        foreach ($newconfig as $key => $value) {
            $oldvalue = $oldconfig->get($key);
            $fullKey = array_merge($parents,array($key));
            if ($value instanceof \Zend_Config) {
                if (empty($parents)) {
                    $extends = $newconfig->getExtends();
                    $extend = array_key_exists($key,$extends) ? $extends[$key] : null;
                    $editor->setSection($key,$extend);
                }
                if (!isset($oldvalue)) {
                    $this->diffConfigs(new \Zend_Config(array()),$value,$editor,$fullKey);
                } else {
                    $this->diffConfigs($oldvalue,$value,$editor,$fullKey);
                }
            } else {
                if (is_numeric($key)){
                    $editor->setArrayEl($fullKey,$value);
                } else {
                    $editor->set($fullKey,$value);
                }
            }
        }
        foreach ($oldconfig as $key => $value) {
            $fullKey = array_merge($parents,array($key));
            $o = $newconfig->get($key);
            if (!isset($o)) {
                if ($value instanceof \Zend_Config) {
                    $this->diffConfigs(
                        $value,new \Zend_Config(array()),$editor,$fullKey
                    );
                    $editor->removeSection($key);
                } else {
                    if (is_numeric($key)) {
                        $editor->delArrayEl($fullKey);
                    } else {
                        $editor->reset($fullKey);
                    }
                }
            }
        }
    }
}


/**
 * Edit the sections and keys of an ini in-place
 */
class IniEditor
{
    /**
     * The text that is edited
     *
     * @var string
     */
    private $text;

    /**
     * The symbol that is used
     *
     * @var string
     */
    private $nestSeparator = '.';

    /**
     * Get the nest separator
     *
     * @return string The nest separator
     */
    public function getNestSeparator()
    {
        return $this->nestSeparator;
    }

    /**
     * Set the nest separator
     *
     * @param $separator The nest separator
     * @return mixed The current instance of IniReader
     */
    public function setNestSeparator($separator)
    {
        $this->nestSeparator = $separator;
        return $this;
    }

    /**
     * Create a new IniEditor
     *
     * @param $content The content of the ini as string
     */
    public function __construct($content)
    {
        $this->text = explode("\n",$content);
    }

    /**
     * Set the value of the given key.
     *
     * Update the key, if it already exists, otherwise create it.
     *
     * @param array $key
     * @param $value
     */
    public function set(array $key,$value)
    {
        $line = $this->getKeyLine($key);
        if ($line === -1) {
            $this->insert($key,$value);
            return;
        }
        $content = $this->formatKeyValuePair(
            $this->truncateSection($key),$value);
        $this->updateLine($line,$content);
    }

    public function delArrayEl(array $key)
    {
        $line = $this->getArrayEl($key);
        if ($line !== -1) {
            $this->deleteLine($line);
        }
    }

    public function setArrayEl(array $key,$value)
    {
        $line = $this->getArrayEl($key);
        if (count($key) > 1) {
            $ident = $this->truncateSection($key);
            $section = $key[0];
        } else {
            $ident = $key;
            $section = null;
        }
        if ($line !== -1) {
            if (count($ident) > 1){
                $this->updateLine($line,$this->formatKeyValuePair($ident,$value));
            } else {
                // move into own section
                $this->deleteLine($line);
                $this->setSection($section);
                $this->insert(array_merge(array($section),$ident),$value);
            }
        } else {
            $e = $this->getSectionEnd($section);
            $this->insertAtLine($e,$this->formatKeyValuePair($ident,$value));
        }
    }

    /**
     * Get the line of an array element
     *
     * @param array $key The key of the property.
     * @param $value The value
     */
    private function getArrayEl(array $key)
    {
        $line = 0;
        if (count($key) > 1) {
            $line = $this->getSectionDeclLine($key[0]) + 1;
            $validKey = array_slice($key,1,null,true);
        }
        $index = array_pop($validKey);
        $formattedKey = explode('=',$this->formatKeyValuePair($validKey,''));
        $formattedKey = $formattedKey[0];

        for (;$line < count($this->text);$line++) {
            $l = $this->text[$line];
            if ($this->isSectionDecl($l)) {
                return -1;
            }
            if (strlen($formattedKey) > 0) {
                if (preg_match('/^'.$formattedKey.'\[\]/',$l) === 1 ||
                    preg_match('/^'.$formattedKey.'.'.$index.'/',$l) === 1 ) {
                    return $line;
                }
            } else {
                if (preg_match('/^'.$index.'/',$l) === 1 ) {
                    return $line;
                }
            }
        }
        return -1;
    }

    /**
     * Reset the given key
     *
     * Set the key to null, if it already exists. Otherwise do nothing.
     *
     * @param array $key
     */
    public function reset(array $key)
    {
        $line = $this->getKeyLine($key);
        if ($line === -1) {
            return;
        }
        $this->deleteLine($line);
    }

    /**
     * Change the extended section of $section
     */
    public function setSection($section,$extend = null)
    {
        if (isset($extend)) {
            $decl = '['.$section.' : '.$extend.']';
        } else {
            $decl = '['.$section.']';
        }
        $line = $this->getSectionDeclLine($section);
        if ($line !== -1) {
            $this->deleteLine($line);
            $this->insertAtLine($line,$decl);
        } else {
            $line = $this->getLastLine();
            $this->insertAtLine($line,$decl);
            $this->insertAtLine($line,"");
        }
    }

    /**
     * Remove the section declarationa of $section
     */
    public function removeSection($section)
    {
        $line = $this->getSectionDeclLine($section);
        if ($line !== -1) {
            $this->deleteLine($line);
        }
    }

    /**
     * Insert a key
     *
     * Insert the key at the end of the corresponding section.
     *
     * @param array $key The key to insert
     * @param $value The value to insert
     */
    private function insert(array $key,$value)
    {
        if (count($key) > 1) {
            // insert into end of section
            $line = $this->getSectionEnd($key[0]);
        } else {
            // insert into section-less space
            $line = $this->getSectionEnd();
        }
        $content = $this->formatKeyValuePair($this->truncateSection($key),$value);
        $this->insertAtLine($line,$content);
    }


    /**
     * Return the edited text
     *
     * @return string The edited text
     */
    public function getText()
    {
        // clean up whitespaces
        $i = count($this->text) - 1;
        for (;$i >= 0; $i--) {
            $line = $this->text[$i];
            if ($this->isSectionDecl($line)) {
                $i--;
                $line = $this->text[$i];
                while ($i >= 0 && preg_match('/^[\s]*$/',$line) === 1) {
                    $this->deleteLine($i);
                    $i--;
                    $line = $this->text[$i];
                }
                if ($i !== 0) {
                    $this->insertAtLine($i + 1,'');
                }
            }
        }
        return implode("\n",$this->text);
    }

    /**
     * Insert the text at line $lineNr
     *
     * @param $lineNr The line nr the inserted line should have
     * @param $toInsert The text that will be inserted
     */
    private function insertAtLine($lineNr,$toInsert)
    {
        $this->text = IniEditor::insertIntoArray($this->text,$lineNr,$toInsert);
    }

    /**
     * Update the line $lineNr
     *
     * @param $lineNr
     * @param $toInsert The lineNr starting at 0
     */
    private function updateLine($lineNr,$toInsert)
    {
        $this->text[$lineNr] = $toInsert;
    }

    /**
     * Delete the line $lineNr
     *
     * @param $lineNr The lineNr starting at 0
     */
    private function deleteLine($lineNr)
    {
        $this->text = $this->removeFromArray($this->text,$lineNr);
    }

    /**
     * Format a key-value pair to an INI file-entry
     *
     * @param array $key The key
     * @param $value The value
     *
     * @return string The formatted key-value pair
     */
    private function formatKeyValuePair(array $key,$value)
    {
        return implode($this->nestSeparator,$key).'='.$this->_prepareValue($value);
    }

    /**
     * Strip the section off of a key, when necessary.
     *
     * @param array $key
     * @return array
     */
    private function truncateSection(array $key)
    {
        if (count($key) > 1) {
            unset($key[0]);
        }
        return $key;
    }

    /**
     * Get the first line after the given $section
     *
     * If section is empty, return the end of section-less
     * space at the file start.
     *
     * @param $section The name of the section
     * @return int
     */
    private function getSectionEnd($section = null)
    {
        $i = 0;
        $started = false;
        if (!isset($section)) {
            $started = true;
        }
        foreach ($this->text as $line) {
            if ($started) {
                if (preg_match('/^\[/',$line) === 1) {
                    return $i;
                }
            } elseif (preg_match('/^\['.$section.'.*\]/',$line) === 1) {
                $started = true;
            }
            $i++;
        }
        if (!$started) {
            return -1;
        }
        return $i;
    }

    /**
     * Check if the given line contains a section declaration
     *
     * @param $lineContent The content of the line
     * @param string $section The optional section name that will be assumed
     * @return bool
     */
    private function isSectionDecl($lineContent,$section = "")
    {
        return preg_match('/^\[/'.$section,$lineContent) === 1;
    }

    private function getSectionDeclLine($section)
    {
        $i = 0;
        foreach ($this->text as $line) {
            if (preg_match('/^\['.$section.'/',$line)) {
                return $i;
            }
            $i++;
        }
        return -1;
    }

    /**
     * Return the line number of the given key
     *
     * When sections are active, return the first matching key in the key's
     * section, otherwise return the first matching key.
     *
     * @param array $keys The key and its parents
     */
    private function getKeyLine(array $keys)
    {
        // remove section
        if (count($keys) > 1) {
            // the key is in a section
            $section = $keys[0];
            $key = implode($this->nestSeparator,array_slice($keys,1,null,true));
            $inSection = false;
        } else {
            // section-less key
            $section = null;
            $key = implode($this->nestSeparator,$keys);
            $inSection = true;
        }
        $i = 0;
        foreach ($this->text as $line) {
            if ($inSection && preg_match('/^\[/',$line) === 1) {
                return -1;
            }
            if ($inSection && preg_match('/^'.$key.'/',$line) === 1) {
                return $i;
            }
            if (!$inSection && preg_match('/^\['.$section.'/',$line) === 1) {
                $inSection = true;
            }
            $i++;
        }
        return -1;
    }

    /**
     * Get the last line number
     *
     * @return int The line nr. of the last line
     */
    private function getLastLine()
    {
        return count($this->text);
    }

    /**
     * Insert a new element into a specific position of an array
     *
     * @param $array The array to use
     * @param $pos The target position
     * @param $element The element to insert
     */
    private static function insertIntoArray($array,$pos,$element)
    {
        array_splice($array, $pos, 0, $element);
        return $array;
    }

    /**
     * Remove an element from an array
     *
     * @param $array The array to use
     * @param $pos The position to remove
     */
    private function removeFromArray($array,$pos)
    {
        unset($array[$pos]);
        return array_values($array);
    }

    /**
     * Prepare a value for INI
     *
     * @param $value
     * @return string
     * @throws Zend_Config_Exception
     */
    protected function _prepareValue($value)
    {
        if (is_integer($value) || is_float($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return ($value ? 'true' : 'false');
        } elseif (strpos($value, '"') === false) {
            return '"' . $value .  '"';
        } else {
            /** @see Zend_Config_Exception */
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('Value can not contain double quotes "');
        }
    }
}

