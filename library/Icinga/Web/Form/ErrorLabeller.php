<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form;

use BadMethodCallException;
use Zend_Translate_Adapter;
use Zend_Validate_NotEmpty;
use Zend_Validate_File_MimeType;
use Icinga\Web\Form\Validator\DateTimeValidator;
use Icinga\Web\Form\Validator\ReadablePathValidator;
use Icinga\Web\Form\Validator\WritablePathValidator;

class ErrorLabeller extends Zend_Translate_Adapter
{
    protected $messages;

    public function __construct($options = array())
    {
        if (! isset($options['element'])) {
            throw new BadMethodCallException('Option "element" is missing');
        }

        $this->messages = $this->createMessages($options['element']);
    }

    public function isTranslated($messageId, $original = false, $locale = null)
    {
        return array_key_exists($messageId, $this->messages);
    }

    public function translate($messageId, $locale = null)
    {
        if (array_key_exists($messageId, $this->messages)) {
            return $this->messages[$messageId];
        }

        return $messageId;
    }

    protected function createMessages($element)
    {
        $label = $element->getLabel() ?: $element->getName();

        return array(
            Zend_Validate_NotEmpty::IS_EMPTY            => sprintf(t('%s is required and must not be empty'), $label),
            Zend_Validate_File_MimeType::FALSE_TYPE     => sprintf(
                t('%s (%%value%%) has a false MIME type of "%%type%%"'),
                $label
            ),
            Zend_Validate_File_MimeType::NOT_DETECTED   => sprintf(t('%s (%%value%%) has no MIME type'), $label),
            WritablePathValidator::NOT_WRITABLE         => sprintf(t('%s is not writable', 'config.path'), $label),
            WritablePathValidator::DOES_NOT_EXIST       => sprintf(t('%s does not exist', 'config.path'), $label),
            ReadablePathValidator::NOT_READABLE         => sprintf(t('%s is not readable', 'config.path'), $label),
            DateTimeValidator::INVALID_DATETIME_FORMAT  => sprintf(
                t('%s not in the expected format: %%value%%'),
                $label
            )
        );
    }

    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        // nonsense, required as being abstract otherwise...
    }

    public function toString()
    {
        return 'ErrorLabeller'; // nonsense, required as being abstract otherwise...
    }
}
