<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form\Validator;

use Zend_Validate_Abstract;

/**
 * Validator that checks whether a textfield doesn't contain raw double quotes
 */
class UrlValidator extends Zend_Validate_Abstract
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_messageTemplates = ['HAS_QUOTES' => t(
            'The url must not contain raw double quotes. If you really need double quotes, use %22 instead.'
        )];
    }

    /**
     * Validate the input value
     *
     * @param   string  $value      The string to validate
     *
     * @return  bool    true if and only if the input is valid, otherwise false
     *
     * @see     Zend_Validate_Abstract::isValid()
     */
    public function isValid($value)
    {
        $hasQuotes = false === strpos($value, '"');
        if (! $hasQuotes) {
            $this->_error('HAS_QUOTES');
        }
        return $hasQuotes;
    }
}
