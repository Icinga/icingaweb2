<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Translation\Catalog;

use Exception;
use Icinga\Exception\IcingaException;
use Icinga\Module\Translation\Exception\CatalogEntryException;

/**
 * Class CatalogEntry
 *
 * Provides a convenient interface to handle entries of gettext PO files.
 *
 * @package Icinga\Module\Translation\Catalog
 */
class CatalogEntry
{
    /**
     * Maximal amount of chars per line
     *
     * @var int
     */
    const MAX_LINE_LENGTH = 80;

    /**
     * Regex that matches php-format placeholders
     *
     * @var string
     */
    const PHP_FORMAT_REGEX = '/(?<!%)%(?:\d+\$)?[+-]?(?:[ 0]|\'.)?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/';

    /**
     * Obsolete tag for this CatalogEntry
     *
     * @var bool
     */
    protected $obsolete;

    /**
     * Context for this CatalogEntry
     *
     * @var string
     */
    protected $messageContext;

    /**
     * Untranslated message for this CatalogEntry
     *
     * @var string
     */
    protected $messageId;

    /**
     * Untranslated plural messages for this CatalogEntry
     *
     * @var array
     */
    protected $messageIdPlural;

    /**
     * Translated message for this CatalogEntry
     *
     * @var string
     */
    protected $message;

    /**
     * Translated plural messages for this CatalogEntry
     *
     * @var array
     */
    protected $messagePlurals;

    /**
     * Context of the message before the last change for this CatalogEntry
     *
     * @var string
     */
    protected $previousMessageContext;

    /**
     * Untranslated message before the last change for this CatalogEntry
     *
     * @var string
     */
    protected $previousMessageId;

    /**
     * Untranslated plural messages before the last change for this CatalogEntry
     *
     * @var array
     */
    protected $previousMessageIdPlural;

    /**
     * Translator comments for this CatalogEntry
     *
     * @var array
     */
    protected $translatorComments;

    /**
     * Extracted comments for this CatalogEntry
     *
     * @var array
     */
    protected $extractedComments;

    /**
     * Paths in which this CatalogEntry is used
     *
     * @var array
     */
    protected $paths;

    /**
     * Information about the translation status and format of this CatalogEntry
     *
     * @var array
     */
    protected $flags;

    /**
     * Create a new CatalogEntry
     *
     * @param   string  $messageId      The untranslated message
     * @param   string  $message        The translated message
     * @param   string  $messageContext The message's context
     */
    public function __construct($messageId, $message, $messageContext = null)
    {
        $this->messageId = $messageId;
        $this->message = $message;
        $this->messageContext = $messageContext;
    }

    /**
     * Create and return a new CatalogEntry from the given array
     *
     * @param   array   $entry
     *
     * @return  CatalogEntry
     *
     * @throws  CatalogEntryException
     */
    public static function fromArray(array $entry)
    {
        if (! isset($entry['msgid']) || ! isset($entry['msgstr'][0])) {
            throw new CatalogEntryException('Missing msgid or msgstr');
        }

        $catalogEntry = new static(
            $entry['msgid'],
            $entry['msgstr'][0],
            isset($entry['msgctxt']) ? $entry['msgctxt'] : null
        );

        foreach ($entry as $key => $value)
        {
            switch ($key)
            {
                case 'obsolete':
                    $catalogEntry->setObsolete($value);
                    break;
                case 'msgid_plural':
                    $catalogEntry->setMessageIdPlural($value);
                    break;
                case 'msgstr':
                    unset($value[0]);
                    if (! empty($value)) {
                        $catalogEntry->setMessagePlurals($value);
                    }
                    break;
                case 'previous_msgctxt':
                    $catalogEntry->setPreviousMessageContext($value);
                    break;
                case 'previous_msgid':
                    $catalogEntry->setPreviousMessageId($value);
                    break;
                case 'previous_msgid_plural':
                    $catalogEntry->setPreviousMessageIdPlural($value);
                    break;
                case 'translator_comments':
                    $catalogEntry->setTranslatorComments($value);
                    break;
                case 'extracted_comments':
                    $catalogEntry->setExtractedComments($value);
                    break;
                case 'paths':
                    $catalogEntry->setPaths($value);
                    break;
                case 'flags':
                    $catalogEntry->setFlags($value);
                    break;
            }
        }

        return $catalogEntry;
    }

    /**
     * Set the message for this CatalogEntry
     *
     * @param   string  $message
     *
     * @return  $this
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Return the message for this CatalogEntry
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set  the message id for this CatalogEntry
     *
     * @param   string  $messageId
     *
     * @return  $this
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;
        return $this;
    }

    /**
     * Return the message id for this CatalogEntry
     *
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * Set the message context for this CatalogEntry
     *
     * @param   string  $messageContext
     *
     * @return  $this
     */
    public function setMessageContext($messageContext)
    {
        $this->messageContext = $messageContext;
        return $this;
    }

    /**
     * Return the message context for this CatalogEntry
     *
     * @return string
     */
    public function getMessageContext()
    {
        return $this->messageContext;
    }

    /**
     * Set whether this CatalogEntry is obsolete
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setObsolete($state = true)
    {
        $this->obsolete = $state;
        return $this;
    }

    /**
     * Return whether this CatalogEntry is obsolete
     *
     * @return bool
     */
    public function getObsolete()
    {
        return $this->obsolete;
    }

    /**
     * Set the plural message id for this CatalogEntry
     *
     * @param   string  $messageIdPlural
     *
     * @return  $this
     */
    public function setMessageIdPlural($messageIdPlural)
    {
        $this->messageIdPlural = $messageIdPlural;
        return $this;
    }

    /**
     * Return the plural message id for this CatalogEntry
     *
     * @return string
     */
    public function getMessageIdPlural()
    {
        return $this->messageIdPlural;
    }

    /**
     * Set the plural messages for this CatalogEntry
     *
     * @param   array   $messagePlurals
     *
     * @return  $this
     */
    public function setMessagePlurals(array $messagePlurals)
    {
        $this->messagePlurals = $messagePlurals;
        return $this;
    }

    /**
     * Return the plural messages for this CatalogEntry
     *
     * @return array
     */
    public function getMessagePlurals()
    {
        return $this->messagePlurals;
    }

    /**
     * Set the previous message context for this CatalogEntry
     *
     * @param   string  $previousMessageContext
     *
     * @return  $this
     */
    public function setPreviousMessageContext($previousMessageContext)
    {
        $this->previousMessageContext = $previousMessageContext;
        return $this;
    }

    /**
     * Return the previous message context for this CatalogEntry
     *
     * @return  string
     */
    public function getPreviousMessageContext()
    {
        return $this->previousMessageContext;
    }

    /**
     * Set the previous message id for this CatalogEntry
     *
     * @param   string  $previousMessageId
     *
     * @return  $this
     */
    public function setPreviousMessageId($previousMessageId)
    {
        $this->previousMessageId = $previousMessageId;
        return $this;
    }

    /**
     * Return the previous message id for this CatalogEntry
     *
     * @return string
     */
    public function getPreviousMessageId()
    {
        return $this->previousMessageId;
    }

    /**
     * Set the previous plural message id for this CatalogEntry
     *
     * @param   string  $previousMessageIdPlural
     *
     * @return  $this
     */
    public function setPreviousMessageIdPlural($previousMessageIdPlural)
    {
        $this->previousMessageIdPlural = $previousMessageIdPlural;
        return $this;
    }

    /**
     * Return the previous plural message id for this CatalogEntry
     *
     * @return string
     */
    public function getPreviousMessageIdPlural()
    {
        return $this->previousMessageIdPlural;
    }

    /**
     * Set translator comments for this CatalogEntry
     *
     * @param   array   $translatorComments
     *
     * @return  $this
     */
    public function setTranslatorComments(array $translatorComments)
    {
        $this->translatorComments = $translatorComments;
        return $this;
    }

    /**
     * Return translator comments for this CatalogEntry
     *
     * @return array
     */
    public function getTranslatorComments()
    {
        return $this->translatorComments;
    }

    /**
     * Set extracted comments for this CatalogEntry
     *
     * @param   array   $extractedComments
     *
     * @return  $this
     */
    public function setExtractedComments(array $extractedComments)
    {
        $this->extractedComments = $extractedComments;
        return $this;
    }

    /**
     * Return extracted comments for this CatalogEntry
     *
     * @return array
     */
    public function getExtractedComments()
    {
        return $this->extractedComments;
    }

    /**
     * Set paths for this CatalogEntry
     *
     * @param   array   $paths
     *
     * @return  $this
     */
    public function setPaths(array $paths)
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * Return paths for this CatalogEntry
     *
     * @return array
     */
    public function getPaths()
    {
        return $this->paths;
    }

    /**
     * Set flags for this CatalogEntry
     *
     * @param   array   $flags
     *
     * @return  $this
     */
    public function setFlags(array $flags)
    {
        $this->flags = $flags;
        return $this;
    }

    /**
     * Return flags for this CatalogEntry
     *
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * Return whether this CatalogEntry is translated
     *
     * @return  bool
     */
    public function isTranslated()
    {
        return ! empty($this->message);
    }

    /**
     * Return whether this CatalogEntry is fuzzy
     *
     * @return  bool
     */
    public function isFuzzy()
    {
        return ! empty($this->flags) && in_array('fuzzy', $this->flags);
    }

    /**
     * Return whether this CatalogEntry is faulty
     *
     * @return  bool
     */
    public function isFaulty()
    {
        if ($this->getMessage()) {
            $numberOfPlaceholdersInId = preg_match_all(static::PHP_FORMAT_REGEX, $this->messageId, $_);
            $numberOfPlaceholdersInMessage = preg_match_all(static::PHP_FORMAT_REGEX, $this->message, $_);
            if ($numberOfPlaceholdersInId !== $numberOfPlaceholdersInMessage) {
                return true;
            }

            if ($this->getMessageIdPlural()) {
                $numberOfPlaceholdersInIdPlural = preg_match_all(static::PHP_FORMAT_REGEX, $this->messageIdPlural, $_);
                foreach ($this->messagePlurals as $value) {
                    $numberOfPlaceholdersInMessagePlural = preg_match_all(static::PHP_FORMAT_REGEX, $value, $_);
                    if ($numberOfPlaceholdersInIdPlural !== $numberOfPlaceholdersInMessagePlural) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Return whether this CatalogEntry is obsolete
     *
     * @return  bool
     */
    public function isObsolete()
    {
        return $this->obsolete;
    }

    /**
     * Render and return this CatalogEntry as string
     *
     * @return  string
     */
    public function render()
    {
        $entries = array_merge(
            array_map(function ($value) { return '# ' . $value; }, $this->translatorComments ?: array()),
            array_map(function ($value) { return '#. ' . $value; }, $this->extractedComments ?: array()),
            array_map(function ($value) { return '#: ' . $value; }, $this->paths ?: array()),
            array_map(function ($value) { return '#, ' . $value; }, $this->flags ?: array()),
            $this->renderAttribute('#| msgctxt ', $this->previousMessageContext, '#| '),
            $this->renderAttribute('#| msgid ', $this->previousMessageId, '#| '),
            $this->renderAttribute('#| msgid_plural ', $this->previousMessageIdPlural, '#| '),
            $this->renderAttribute('msgctxt ', $this->messageContext),
            $this->renderAttribute('msgid ', $this->messageId),
            $this->renderAttribute('msgid_plural ', $this->messageIdPlural)
        );

        if (! empty($this->messagePlurals)) {
            $entries = array_merge($entries, $this->renderAttribute('msgstr[0] ', $this->message));
            foreach ($this->messagePlurals as $key => $value)
            {
                $entries = array_merge($entries, $this->renderAttribute('msgstr[' . $key . '] ', $value));
            }
        } else {
            $entries = array_merge($entries, $this->renderAttribute('msgstr ', $this->message));
        }

        return implode("\n", $entries);
    }

    /**
     * Reformat the given string to fit line length limitation and .po format and add quotes
     *
     * @param   string  $attributePrefix            The attribute prefix that will be placed in front of the string
     * @param   string  $string                     The string to split
     * @param   string  $lineContinuationPrefix     The prefix that will be placed in front of the multi lines of the string
     *
     * @return  array                       Returns an array of split lines
     */
    public function renderAttribute($attributePrefix, $string, $lineContinuationPrefix = '')
    {
        if (empty($string)) {
            return array();
        }

        $string = strtr($string, array_flip(CatalogParser::$escapedChars));

        $attributePrefix = ($this->isObsolete() ? ('#~ ' . $attributePrefix) : $attributePrefix);
        $oneLine = $attributePrefix . '"' . $string . '"';
        if (strlen($oneLine) <= static::MAX_LINE_LENGTH) {
            return array($oneLine);
        }

        $splitLines = array($attributePrefix . '""');
        $lastSpace = 0;
        $lastCut = 0;
        $additionalChars = strlen($lineContinuationPrefix) + 2;
        for ($i = 0; $i < strlen($string); $i++)
        {
            if ($i - $lastCut + $additionalChars === static::MAX_LINE_LENGTH) {
                $splitLines[] = ($this->isObsolete() ? '#~ ' : '')
                    . $lineContinuationPrefix
                    . '"'
                    . substr($string, $lastCut, $lastSpace - $lastCut + 1)
                    . '"';
                $lastCut = $lastSpace + 1;
            }

            if ($string[$i] === ' ') {
                $lastSpace = $i;
            }
        }

        $splitLines[] = ($this->isObsolete() ? '#~ ' : '')
            . $lineContinuationPrefix
            . '"'
            . substr($string, $lastCut)
            . '"';
        return $splitLines;
    }

    /**
     * @see CatalogEntry::render()
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return 'Failed to render CatalogEntry: ' . IcingaException::describe($e);
        }
    }
}
