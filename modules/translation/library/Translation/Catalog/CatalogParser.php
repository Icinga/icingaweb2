<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Catalog;

use Exception;
use SplFileObject;
use Icinga\Application\Benchmark;
use Icinga\Module\Translation\Exception\CatalogParserException;
use Icinga\Util\File;

/**
 * Class CatalogParser
 *
 * Reads gettext PO files and outputs the contained entries.
 *
 * @package Icinga\Module\Translation\Catalog
 */
class CatalogParser
{
    /**
     * Escaped chars to resolve
     *
     * @var array
     */
    public static $escapedChars = array(
        '\n' => "\n",
        '\"' => '"',
        '\t' => "\t",
        '\r' => "\r",
        '\\' => "\\"
    );

    /**
     * The path of the file being parsed
     *
     * @var string
     */
    protected $catalogPath;

    /**
     * The File being parsed
     *
     * @var File
     */
    protected $catalogFile = null;

    /**
     * The line that is being parsed
     *
     * @var string
     */
    protected $stack = '';

    /**
     * Number of current line
     *
     * @var int
     */
    protected $lineNumber = 0;

    /**
     * Position in current stack
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Create a new CatalogParser
     *
     * @param   string  $catalogPath    The path to the catalog file to parse
     */
    public function __construct($catalogPath)
    {
        $this->catalogPath = $catalogPath;
        $this->catalogFile = new File($catalogPath);
        $this->catalogFile->setFlags(SplFileObject::DROP_NEW_LINE);
    }

    /**
     * Parse the given catalog file and return its entries
     *
     * @param   string  $catalogPath    The path to the catalog file to parse
     */
    public static function parsePath($catalogPath)
    {
        Benchmark::measure('CatalogParser::parsePath()');
        $parser = new static($catalogPath);
        return $parser->parse();
    }

    /**
     * Parse the catalog file and return its entries
     *
     * @return  array
     */
    public function parse()
    {
        $parsedData = array();
        $currentEntry = array();
        $lastType = null;
        $lastNumber = 0;
        while ($this->checkStack()) {
            $returnedValue = $this->handleStack();
            if (isset($returnedValue['type']) && $returnedValue['type'] === 'newline') {
                if (! empty($currentEntry)) {
                    $parsedData[] = $currentEntry;
                    $currentEntry = array();
                    $lastType = null;
                    $lastNumber = 0;
                }
            } else {
                if (isset($returnedValue['type'])) {
                    $lastType = $returnedValue['type'];
                }
                if (isset($returnedValue['number'])) {
                    $lastNumber = $returnedValue['number'];
                }
                if (! isset($currentEntry['obsolete']) || isset($returnedValue['obsolete'])) {
                    $currentEntry['obsolete'] = isset($returnedValue['obsolete']);
                }
                if (isset($returnedValue['value'])) {
                    $currentEntry = $this->processParsedValues(
                        $currentEntry,
                        $returnedValue['value'],
                        $lastType,
                        $lastNumber
                    );
                }
            }
        }

        if (! empty($currentEntry)) {
            $parsedData[] = $currentEntry;
        }

        return $parsedData;
    }

    /**
     * Process values parsed by method parse
     *
     * @param   array           $currentEntry   The current entry of method parse
     * @param   array|string    $returnedValue  The value returned by handleStack
     * @param   string          $lastType       The type the current value belongs to
     * @param   int             $lastNumber     The key the value belongs to if msgstr_plural
     *
     * @return  array
     *
     * @throws  CatalogParserException
     */
    protected function processParsedValues($currentEntry, $returnedValue, $lastType, $lastNumber)
    {
        if ($lastType === null) {
            throw new CatalogParserException(
                $this->catalogPath,
                $this->lineNumber,
                $this->position - strlen($returnedValue) - 1,
                "Missing type before \"$returnedValue\""
            );
        }

        $escapedChars = static::$escapedChars;
        if (is_array($returnedValue)) {
            $returnedValue = array_map(
                function ($value) use ($escapedChars) { return strtr($value, $escapedChars); },
                $returnedValue
            );
        } else {
            $returnedValue = strtr($returnedValue, $escapedChars);
        }

        if ($lastType === 'msgstr') {
            if (isset($currentEntry['msgstr'][$lastNumber])) {
                $currentEntry['msgstr'][$lastNumber] .= $returnedValue;
            } else {
                $currentEntry['msgstr'][$lastNumber] = $returnedValue;
            }
        } else {
            if (isset($currentEntry[$lastType])) {
                if (is_array($currentEntry[$lastType])) {
                    $currentEntry[$lastType] = array_merge($currentEntry[$lastType], $returnedValue);
                } else {
                    $currentEntry[$lastType] .= $returnedValue;
                }
            } else {
                $currentEntry[$lastType] = $returnedValue;
            }
        }

        return $currentEntry;
    }

    /**
     * Return whether there is still data available on the stack
     *
     * @return  bool
     */
    protected function checkStack()
    {
        if (! $this->stack) {
            if (! $this->catalogFile->eof()) {
                $line = $this->catalogFile->fgets();
                $this->lineNumber++;
                $this->stack = $line;
                $this->position = 0;
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Parse the current main expression and return the result
     *
     * @return  array
     */
    protected function handleStack()
    {
        $this->trimStackLeft();
        if (! $this->stack) {
            return array('type' => 'newline');
        }

        switch ($char = $this->pullCharFromStack())
        {
            case '#':
                return $this->handleHash();
            case '"':
                return array('value' => $this->readUntil('"'));
            default:
                $this->putCharInStack($char);
                return $this->handleKeyword($this->readUntil(' '));
        }
    }

    /**
     * Return first character from stack and remove it
     *
     * @return  string
     */
    protected function pullCharFromStack()
    {
        if (! $this->stack) {
            return null;
        }

        $char = $this->stack[0];
        $this->stack = substr($this->stack, 1) ?: '';
        $this->position++;

        return $char;
    }

    /**
     * Parse the current hash expression and return the result
     *
     * @return  array
     *
     * @throws  CatalogParserException
     */
    protected function handleHash()
    {
        switch ($char = $this->pullCharFromStack()) {
            case ' ':
                return array(
                    'type'  => 'translator_comments',
                    'value' => array($this->getStackAndClear())
                );
            case '.':
                return array(
                    'type'  => 'extracted_comments',
                    'value' => array(ltrim($this->getStackAndClear()))
                );
            case ':':
                return array(
                    'type'  => 'paths',
                    'value' => preg_split('/:\d+\K\s+(?=\S+)/', trim($this->getStackAndClear()))
                );
            case ',':
                return array(
                    'type'  => 'flags',
                    'value' => array_map('trim', explode(',', $this->getStackAndClear()))
                );
            case '|':
                return $this->handlePrevious();
            case '~':
                return array(
                    'obsolete' => true
                );
            case null:
                return array(
                    'type'  => 'translator_comments',
                    'value' => array('')
                );
            default:
                throw new CatalogParserException(
                    $this->catalogPath,
                    $this->lineNumber,
                    $this->position,
                    "Unexpected char \"$char\" after #"
                );
        }
    }

    /**
     * Return stack content and clear it afterwards
     *
     * @return  string
     */
    protected function getStackAndClear()
    {
        $stack = $this->stack;
        $this->stack = '';

        return $stack;
    }

    /**
     * Handle stack if first two chars were #|
     *
     * @return  array   Contains the key value if successful
     */
    protected function handlePrevious()
    {
        $this->trimStackLeft();
        $result = $this->handleKeyword($this->readUntil(' '));

        return array('type' => 'previous_' . $result['type']);
    }

    /**
     * Trim whitespaces on the left of the stack
     */
    protected function trimStackLeft()
    {
        $oldStack = $this->stack;
        $this->stack = ltrim($this->stack);

        if ($this->stack !== $oldStack) {
            $this->position += strlen($oldStack) - strlen($this->stack);
        }
    }

    /**
     * Read until given char comes up
     *
     * @param   string  $endPoint       Char to search for
     *
     * @return  string
     *
     * @throws  CatalogParserException  In case the given char cannot be found
     */
    protected function readUntil($endPoint)
    {
        $pattern = '/(?<!\\\\)' . preg_quote($endPoint, '/') . '/';

        try {
            list($string, $this->stack) = preg_split($pattern, $this->stack, 2);
        } catch (Exception $_) {
            throw new CatalogParserException(
                $this->catalogPath,
                $this->lineNumber,
                $this->position + strlen($this->stack) + 1,
                "Missing \"$endPoint\""
            );
        }

        $this->position += strlen($string) + 1;
        return $string;
    }

    /**
     * Check if keyword is correct
     *
     * @param   string  $keyword        The keyword to check
     *
     * @return  array                   Returns array with key type if correct
     *
     * @throws  CatalogParserException  In case the given keyword is incorrect
     */
    protected function handleKeyword($keyword)
    {
        switch ($keyword)
        {
            case 'msgctxt':
            case 'msgid':
            case 'msgid_plural':
            case 'msgstr':
                return array('type' => $keyword);
            case (preg_match('/^(?:msgstr\[([0-9])\])$/', $keyword, $matches) ? true : false):
                return array('type' => 'msgstr', 'number' => $matches[1]);
            default:
                throw new CatalogParserException(
                    $this->catalogPath,
                    $this->lineNumber,
                    $this->position - strlen($keyword),
                    "\"$keyword\" is not a valid keyword"
                );
        }
    }

    /**
     * Put char in front of the current stack
     *
     * @param   string  $char   Char to be put in front
     */
    protected function putCharInStack($char)
    {
        $this->position--;
        $this->stack = $char . $this->stack;
    }
}
