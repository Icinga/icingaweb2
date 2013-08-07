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

use Icinga\Exception\ConfigurationError;

/**
 * Edit the sections and keys of an ini in-place
 */
class IniEditor
{
    /**
     * The text that is edited
     *
     * @var array
     */
    private $text;

    /**
     * The symbol that is used to separate keys
     *
     * @var string
     */
    private $nestSeparator = '.';

    /**
     * Create a new IniEditor
     *
     * @param $content  The content of the ini as string
     */
    public function __construct($content)
    {
        $this->text = explode("\n",$content);
    }

    /**
     * Set the value of the given key.
     *
     * @param array $key    The key to set
     * @param $value        The value to set
     * @param $section      The section to insert to.
     */
    public function set(array $key, $value, $section = null)
    {
        $line = $this->getKeyLine($key,$section);
        if ($line === -1) {
            $this->insert($key,$value,$section);
        } else {
            $content = $this->formatKeyValuePair($key,$value);
            $this->updateLine($line,$content);
        }
    }

    /**
     * Reset the value of the given array element
     *
     * @param $key      The key of the array value
     * @param $section  The section of the array.
     */
    public function resetArrayElement(array $key, $section = null)
    {
        $line = $this->getArrayEl($key, $section);
        if ($line !== -1) {
            $this->deleteLine($line);
        }
    }

    /**
     * Set the value for an array element
     *
     * @param array $key    The key of the property
     * @param $value        The value of the property
     * @param $section      The section to use
     */
    public function setArrayElement(array $key, $value, $section = null)
    {
        $line = $this->getArrayEl($key,$section);
        if ($line !== -1) {
            if (isset($section)) {
                $this->updateLine($line,$this->formatKeyValuePair($key,$value));
            } else {
                $section = $key[0];
                unset($key[0]);
                $this->deleteLine($line);
                $this->setSection($section);
                $this->insert($key,$value,$section);
            }
        } else {
            $this->insert($key,$value,$section);
        }
    }

    /**
     * Get the line of an array element
     *
     * @param array $key    The key of the property.
     * @param $value        The value
     * @param $section      The section to use
     *
     * @return              The line of the array element.
     */
    private function getArrayEl(array $key, $section = null)
    {
        $line = isset($section) ? $this->getSectionLine($section) +1 : 0;
        $index = array_pop($key);
        $formatted = $this->formatKey($key);
        for (; $line < count($this->text); $line++) {
            $l = $this->text[$line];
            if ($this->isSectionDeclaration($l)) {
                return -1;
            }
            if (strlen($formatted) > 0) {
                if (preg_match('/^'.$formatted.'\[\]=/',$l) === 1 ||
                    preg_match(
                        '/^'.$formatted.$this->nestSeparator.$index.'=/',$l) === 1) {
                    return $line;
                }
            } else {
                if (preg_match('/^'.$index.'=/',$l) === 1 ) {
                    return $line;
                }
            }
        }
        return -1;
    }

    /**
     * When it exists, set the key back to null
     *
     * @param array $key    The key to reset
     * @param $section      The section of the key
     */
    public function reset(array $key, $section = null)
    {
        $line = $this->getKeyLine($key,$section);
        if ($line === -1) {
            return;
        }
        $this->deleteLine($line);
    }

    /**
     * Create the section if it does not exist and set the properties
     *
     * @param $section  The section name
     * @param $extend   The section that should be extended by this section
     */
    public function setSection($section, $extend = null)
    {
        if (isset($extend)) {
            $decl = '['.$section.' : '.$extend.']';
        } else {
            $decl = '['.$section.']';
        }
        $line = $this->getSectionLine($section);
        if ($line !== -1) {
            $this->deleteLine($line);
            $this->insertAtLine($line,$decl);
        } else {
            $line = $this->getLastLine();
            $this->insertAtLine($line,$decl);
        }
    }

    /**
     * Remove a section declaration
     *
     * @param $section  The section name
     */
    public function removeSection($section)
    {
        $line = $this->getSectionLine($section);
        if ($line !== -1) {
            $this->deleteLine($line);
        }
    }

    /**
     * Insert the key at the end of the corresponding section
     *
     * @param array $key    The key to insert
     * @param $value        The value to insert
     * @param array $key    The key to insert
     */
    private function insert(array $key, $value, $section = null)
    {
        $line = $this->getSectionEnd($section);
        $content = $this->formatKeyValuePair($key,$value);
        $this->insertAtLine($line,$content);
    }

    /**
     * Get the edited text
     *
     * @return string The edited text
     */
    public function getText()
    {
        $this->cleanUpWhitespaces();
        return implode("\n",$this->text);
    }

    /**
     * Remove all unneeded line breaks between sections
     */
    private function cleanUpWhitespaces()
    {
        $i = count($this->text) - 1;
        for (; $i >= 0; $i--) {
            $line = $this->text[$i];
            if ($this->isSectionDeclaration($line)) {
                $i--;
                $line = $this->text[$i];
                /*
                 * Ignore comments that are glued to the section declaration
                 */
                while ($i > 0 && preg_match('/^;/',$line) === 1) {
                    $i--;
                    $line = $this->text[$i];
                }
                /*
                 * Remove whitespaces between the sections
                 */
                while ($i > 0 && preg_match('/^[\s]*$/',$line) === 1) {
                    $this->deleteLine($i);
                    $i--;
                    $line = $this->text[$i];
                }
                /*
                 * Add a single whitespace
                 */
                if ($i !== 0) {
                    $this->insertAtLine($i + 1,'');
                }
            }
        }
    }

    /**
     * Insert the text at line $lineNr
     *
     * @param $lineNr   The line nr the inserted line should have
     * @param $toInsert The text that will be inserted
     */
    private function insertAtLine($lineNr, $toInsert)
    {
        $this->text = IniEditor::insertIntoArray($this->text,$lineNr,$toInsert);
    }

    /**
     * Update the line $lineNr
     *
     * @param $lineNr   The line number of the target line
     * @param $toInsert The new line content
     */
    private function updateLine($lineNr, $content)
    {
        $this->text[$lineNr] = $content;
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
     * @param array $key    The key
     * @param $value        The value
     *
     * @return string The formatted key-value pair
     */
    private function formatKeyValuePair(array $key, $value)
    {
        return $this->formatKey($key).'='.$this->formatValue($value);
    }

    /**
     * Format a key to an INI key
     */
    private function formatKey(array $key)
    {
        return implode($this->nestSeparator,$key);
    }

    /**
     * Get the first line after the given $section
     *
     * @param $section  The name of the section
     *
     * @return int      The line number of the section
     */
    private function getSectionEnd($section = null)
    {
        $i = 0;
        $started = isset($section) ? false: true;
        foreach ($this->text as $line) {
            if ($started && preg_match('/^\[/',$line) === 1) {
                if ($i === 0) {
                    return $i;
                }
                /*
                 * ignore all comments 'glued' to the next section, to allow section
                 * comments in front of sections
                 */
                while (preg_match('/^;/',$this->text[$i - 1]) === 1) {
                    $i--;
                }
                return $i;
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
     * @param $lineContent      The content of the line
     * @param string $section   The optional section name that will be assumed
     *
     * @return bool
     */
    private function isSectionDeclaration($lineContent, $section = "")
    {
        return preg_match('/^\[/'.$section,$lineContent) === 1;
    }

    /**
     * Get the line where the section begins
     *
     * @param $section  The section
     *
     * @return int      The line number
     */
    private function getSectionLine($section)
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
     * Get the line number where the given key occurs
     *
     * @param array $keys   The key and its parents
     * @param $section      The section of the key
     *
     * @return int The line number
     */
    private function getKeyLine(array $keys, $section = null)
    {
        $key = implode($this->nestSeparator,$keys);
        $inSection = isset($section) ? false : true;
        $i = 0;
        foreach ($this->text as $line) {
            if ($inSection && preg_match('/^\[/',$line) === 1) {
                return -1;
            }
            if ($inSection && preg_match('/^'.$key.'=/',$line) === 1) {
                return $i;
            }
            if (!$inSection && preg_match('/^\[ *'.$section.' *[\]:]/',$line) === 1) {
                $inSection = true;
            }
            $i++;
        }
        return -1;
    }

    /**
     * Get the last line number occurring in the text
     *
     * @return  The line number of the last line
     */
    private function getLastLine()
    {
        return count($this->text);
    }

    /**
     * Insert a new element into a specific position of an array
     *
     * @param $array    The array to use
     * @param $pos      The target position
     * @param $element  The element to insert
     *
     * @return array    The changed array
     */
    private static function insertIntoArray($array, $pos, $element)
    {
        array_splice($array, $pos, 0, $element);
        return $array;
    }

    /**
     * Remove an element from an array
     *
     * @param $array    The array to use
     * @param $pos      The position to remove
     */
    private function removeFromArray($array, $pos)
    {
        unset($array[$pos]);
        return array_values($array);
    }

    /**
     * Prepare a value for INI
     *
     * @param $value    The value of the string
     *
     * @return string   The formatted value
     *
     * @throws Zend_Config_Exception
     */
    private function formatValue($value)
    {
        if (is_integer($value) || is_float($value)) {
            return $value;
        } elseif (is_bool($value)) {
            return ($value ? 'true' : 'false');
        } elseif (strpos($value, '"') === false) {
            return '"' . $value .  '"';
        } else {
            throw new ConfigurationError('Value can not contain double quotes "');
        }
    }
}

