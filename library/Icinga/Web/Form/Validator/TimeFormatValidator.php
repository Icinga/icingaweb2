<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that checks if a textfield contains a correct time format
 */
class TimeFormatValidator extends Zend_Validate_Abstract
{

    /**
     * Valid time characters according to @see http://www.php.net/manual/en/function.date.php
     *
     * @var array
     * @see http://www.php.net/manual/en/function.date.php
     */
    private $validChars = ['a', 'A', 'B', 'g', 'G', 'h', 'H', 'i', 's', 'u'];

    /**
     * List of sensible time separators
     *
     * @var array
     */
    private $validSeparators = [' ', ':', '-', '/', ';', ',', '.'];

    /**
     * Error templates
     *
     * @var array
     * @see Zend_Validate_Abstract::$_messageTemplates
     */
    protected $_messageTemplates = [
        'INVALID_CHARACTERS' => 'Invalid time format'
    ];

    /**
     * Validate the input value
     *
     * @param   string    $value    The format string to validate
     * @param   null      $context  The form context (ignored)
     *
     * @return  bool True when the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value, $context = null)
    {
        $rest = trim($value, join(' ', array_merge($this->validChars, $this->validSeparators)));
        if (strlen($rest) > 0) {
            $this->_error('INVALID_CHARACTERS');
            return false;
        }
        return true;
    }
}
