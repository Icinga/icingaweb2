<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\File\Ini;

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
     * The indentation level of the comments
     *
     * @var string
     */
    private $commentIndentation;

    /**
     * The indentation level of the values
     *
     * @var string
     */
    private $valueIndentation;

    /**
     * The number of new lines between sections
     *
     * @var number
     */
    private $sectionSeparators;

    /**
     * Create a new IniEditor
     *
     * @param string $content   The content of the ini as string
     * @param array  $options   Optional formatting options used when changing the ini file
     *                          * valueIndentation:     The indentation level of the values
     *                          * commentIndentation:   The indentation level of the comments
     *                          * sectionSeparators:    The amount of newlines between sections
     */
    public function __construct(
        $content,
        array $options = array()
    ) {
        $this->text = explode(PHP_EOL, $content);
        $this->valueIndentation = array_key_exists('valueIndentation', $options)
            ? $options['valueIndentation'] : 19;
        $this->commentIndentation = array_key_exists('commentIndentation', $options)
            ? $options['commentIndentation'] : 43;
        $this->sectionSeparators = array_key_exists('sectionSeparators', $options)
            ? $options['sectionSeparators'] : 2;
    }

    /**
     * Set the value of the given key.
     *
     * @param array $key         The key to set
     * @param string $value      The value to set
     * @param array $section     The section to insert to.
     */
    public function set(array $key, $value, $section = null)
    {
        $line = $this->getKeyLine($key, $section);
        if ($line === -1) {
            $this->insert($key, $value, $section);
        } else {
            $content = $this->formatKeyValuePair($key, $value);
            $this->updateLine($line, $content);
        }
    }

    /**
     * Reset the value of the given array element
     *
     * @param array $key        The key of the array value
     * @param array $section    The section of the array.
     */
    public function resetArrayElement(array $key, $section = null)
    {
        $line = $this->getArrayElement($key, $section);
        if ($line !== -1) {
            $this->deleteLine($line);
        }
    }

    /**
     * Set the value for an array element
     *
     * @param array $key            The key of the property
     * @param string $value         The value of the property
     * @param array $section        The section to use
     */
    public function setArrayElement(array $key, $value, $section = null)
    {
        $line = $this->getArrayElement($key, $section);
        if ($line !== -1) {
            if (isset($section)) {
                $this->updateLine($line, $this->formatKeyValuePair($key, $value));
            } else {
                /*
                 * Move into new section to avoid ambiguous configurations
                 */
                $section = $key[0];
                unset($key[0]);
                $this->deleteLine($line);
                $this->setSection($section);
                $this->insert($key, $value, $section);
            }
        } else {
            $this->insert($key, $value, $section);
        }
    }

    /**
     * Get the line of an array element
     *
     * @param array $key            The key of the property.
     * @param mixed $section        The section to use
     *
     * @return int                  The line of the array element.
     */
    private function getArrayElement(array $key, $section = null)
    {
        $line = isset($section) ? $this->getSectionLine($section) + 1 : 0;
        $index = array_pop($key);
        $formatted = $this->formatKey($key);
        for (; $line < count($this->text); $line++) {
            $l = $this->text[$line];
            if ($this->isSectionDeclaration($l)) {
                return -1;
            }
            if (preg_match('/^\s*' . $formatted . '\[\]\s*=/', $l) === 1) {
                return $line;
            }
            if ($this->isPropertyDeclaration($l, array_merge($key, array($index)))) {
                return $line;
            }
        }
        return -1;
    }

    /**
     * When it exists, set the key back to null
     *
     * @param array $key          The key to reset
     * @param array $section      The section of the key
     */
    public function reset(array $key, $section = null)
    {
        $line = $this->getKeyLine($key, $section);
        if ($line === -1) {
            return;
        }
        $this->deleteLine($line);
    }

    /**
     * Create the section if it does not exist and set the properties
     *
     * @param string $section   The section name
     * @param array $extend     The section that should be extended by this section
     */
    public function setSection($section, $extend = null)
    {
        if (isset($extend)) {
            $decl = '[' . $section . ' : ' . $extend . ']';
        } else {
            $decl = '[' . $section . ']';
        }
        $line = $this->getSectionLine($section);
        if ($line !== -1) {
            $this->deleteLine($line);
            $this->insertAtLine($line, $decl);
        } else {
            $line = $this->getLastLine();
            $this->insertAtLine($line, $decl);
        }
    }

    /**
     * Refresh the section order of the ini file
     *
     * @param array $order  An array containing the section names in the new order
     *                      Example: array(0 => 'FirstSection', 1 => 'SecondSection')
     */
    public function refreshSectionOrder(array $order)
    {
        $sections = $this->createSectionMap($this->text);
        /*
         * Move section-less properties to the start of the ordered text
         */
        $orderedText = array();
        foreach ($sections['[section-less]'] as $line) {
            array_push($orderedText, $line);
        }
        /*
         * Reorder the sections
         */
        $len = count($order);
        for ($i = 0; $i < $len; $i++) {
            if (array_key_exists($i, $order)) {
                /*
                 * Append the lines of the section to the end of the
                 * ordered text
                 */
                foreach ($sections[$order[$i]] as $line) {
                    array_push($orderedText, $line);
                }
            }
        }
        $this->text = $orderedText;
    }

    /**
     * Create a map of sections to lines of a given ini file
     *
     * @param array $text           The text split up in lines
     *
     * @return array $sectionMap    A map containing all sections as arrays of lines. The array of section-less
     *                              lines will be available using they key '[section-less]' which is no valid
     *                              section declaration because it contains brackets.
     */
    private function createSectionMap($text)
    {
        $sections = array('[section-less]' => array());
        $section = '[section-less]';
        $len = count($text);
        for ($i = 0; $i < $len; $i++) {
            if ($this->isSectionDeclaration($text[$i])) {
                $newSection = $this->getSectionFromDeclaration($this->text[$i]);
                $sections[$newSection] = array();

                /*
                 * Remove comments 'glued' to the new section from the old
                 * section array and put them into the new section.
                 */
                $j = $i - 1;
                $comments = array();
                while ($j >= 0 && $this->isComment($this->text[$j])) {
                    array_push($comments, array_pop($sections[$section]));
                    $j--;
                }
                $comments = array_reverse($comments);
                foreach ($comments as $comment) {
                    array_push($sections[$newSection], $comment);
                }

                $section = $newSection;
            }
            array_push($sections[$section], $this->text[$i]);
        }
        return $sections;
    }

    /**
     * Extract the section name from a section declaration
     *
     * @param String $declaration    The section declaration
     *
     * @return string   The section name
     */
    private function getSectionFromDeclaration($declaration)
    {
        $tmp = preg_split('/(\[|\]|:)/', $declaration);
        return trim($tmp[1]);
    }

    /**
     * Remove a section declaration
     *
     * @param string $section  The section name
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
     * @param array $key        The key to insert
     * @param mixed $value      The value to insert
     * @param array $section    The key to insert
     */
    private function insert(array $key, $value, $section = null)
    {
        $line = $this->getSectionEnd($section);
        $content = $this->formatKeyValuePair($key, $value);
        $this->insertAtLine($line, $content);
    }

    /**
     * Get the edited text
     *
     * @return string The edited text
     */
    public function getText()
    {
        $this->cleanUpWhitespaces();
        return implode(PHP_EOL, $this->text);
    }

    /**
     * Remove all unneeded line breaks between sections
     */
    private function cleanUpWhitespaces()
    {
        $i = count($this->text) - 1;
        for (; $i > 0; $i--) {
            $line = $this->text[$i];
            if ($this->isSectionDeclaration($line) && $i > 0) {
                $i--;
                $line = $this->text[$i];
                /*
                 * Ignore comments that are glued to the section declaration
                 */
                while ($i > 0 && $this->isComment($line)) {
                    $i--;
                    $line = $this->text[$i];
                }
                /*
                 * Remove whitespaces between the sections
                 */
                while ($i > 0 && preg_match('/^\s*$/', $line) === 1) {
                    $this->deleteLine($i);
                    $i--;
                    $line = $this->text[$i];
                }
                /*
                 * Refresh section separators
                 */
                if ($i !== 0 && $this->sectionSeparators > 0) {
                    $this->insertAtLine($i + 1, str_repeat(PHP_EOL, $this->sectionSeparators - 1));
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
        $this->text = IniEditor::insertIntoArray($this->text, $lineNr, $toInsert);
    }

    /**
     * Update the line $lineNr
     *
     * @param int       $lineNr     The line number of the target line
     * @param string    $content    The new line content
     */
    private function updateLine($lineNr, $content)
    {
        $comment = $this->getComment($this->text[$lineNr]);
        $comment = trim($comment);
        if (strlen($comment) > 0) {
            $comment = ' ; ' . $comment;
            $content = str_pad($content, $this->commentIndentation) . $comment;
        }
        $this->text[$lineNr] = $content;
    }

    /**
     * Get the comment from the given line
     *
     * @param $lineContent  The content of the line
     *
     * @return string       The extracted comment
     */
    private function getComment($lineContent)
    {
        /*
         * Remove all content in double quotes that is not behind a semicolon, recognizing
         * escaped double quotes inside the string
         */
        $cleaned = preg_replace('/^[^;"]*"[^"\\\\]*(?:\\\\.[^"\\\\]*)*"/s', '', $lineContent);

        $matches = explode(';', $cleaned, 2);
        return array_key_exists(1, $matches) ? $matches[1] : '';
    }

    /**
     * Delete the line $lineNr
     *
     * @param $lineNr The lineNr starting at 0
     */
    private function deleteLine($lineNr)
    {
        $this->text = $this->removeFromArray($this->text, $lineNr);
    }

    /**
     * Format a key-value pair to an INI file-entry
     *
     * @param array     $key            The key to format
     * @param string    $value          The value to format
     *
     * @return string                   The formatted key-value pair
     */
    private function formatKeyValuePair(array $key, $value)
    {
        return str_pad($this->formatKey($key), $this->valueIndentation) . ' = ' . $this->formatValue($value);
    }

    /**
     * Format a key to an INI key
     *
     * @param   array $key        the key array to format
     *
     * @return  string
     */
    private function formatKey(array $key)
    {
        return implode($this->nestSeparator, $key);
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
        $started = isset($section) ? false : true;
        foreach ($this->text as $line) {
            if ($started && $this->isSectionDeclaration($line)) {
                if ($i === 0) {
                    return $i;
                }
                /*
                 * ignore all comments 'glued' to the next section, to allow section
                 * comments in front of sections
                 */
                while ($i > 0 && $this->isComment($this->text[$i - 1])) {
                    $i--;
                }
                return $i;
            } elseif ($this->isSectionDeclaration($line, $section)) {
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
     * Check if the given line contains only a comment
     */
    private function isComment($line)
    {
        return preg_match('/^\s*;/', $line) === 1;
    }

    /**
     * Check if the line contains the property declaration for a key
     *
     * @param string $lineContent   The content of the line
     * @param array $key            The key this declaration is supposed to have
     *
     * @return boolean              True, when the lineContent is a property declaration
     */
    private function isPropertyDeclaration($lineContent, array $key)
    {
        return preg_match(
            '/^\s*' . $this->formatKey($key) . '\s*=\s*/',
            $lineContent
        ) === 1;
    }

    /**
     * Check if the given line contains a section declaration
     *
     * @param $lineContent      The content of the line
     * @param string $section   The optional section name that will be assumed
     *
     * @return bool             True, when the lineContent is a section declaration
     */
    private function isSectionDeclaration($lineContent, $section = null)
    {
        if (isset($section)) {
            return preg_match('/^\s*\[\s*' . $section . '\s*[\]:]/', $lineContent) === 1;
        } else {
            return preg_match('/^\s*\[/', $lineContent) === 1;
        }
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
            if ($this->isSectionDeclaration($line, $section)) {
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
     * @return int          The line number
     */
    private function getKeyLine(array $keys, $section = null)
    {
        $key = implode($this->nestSeparator, $keys);
        $inSection = isset($section) ? false : true;
        $i = 0;
        foreach ($this->text as $line) {
            if ($inSection && $this->isSectionDeclaration($line)) {
                return -1;
            }
            if ($inSection && $this->isPropertyDeclaration($line, $keys)) {
                return $i;
            }
            if (!$inSection && $this->isSectionDeclaration($line, $section)) {
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
     *
     * @return array    The altered array
     */
    private function removeFromArray($array, $pos)
    {
        unset($array[$pos]);
        return array_values($array);
    }

    /**
     * Prepare a value for INe
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
            return '"' . str_replace('"', '\"', $value) . '"';
        }
    }
}
