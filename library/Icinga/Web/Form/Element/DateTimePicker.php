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

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\Validator\DateTimeValidator;
use \Zend_Form_Element_Text;
use \Zend_Form_Element;
use \Icinga\Util\DateTimeFactory;

/**
 * Datetime form element which returns the input as Unix timestamp after the input has been proven valid. Utilizes
 * DateTimeFactory to ensure time zone awareness
 *
 * @see isValid()
 */
class DateTimePicker extends Zend_Form_Element_Text
{
    /**
     * View helper to use
     * @var string
     */
    public $helper = 'formDateTime';

    /**
     * The validator used for datetime validation
     * @var DateTimeValidator
     */
    private $dateValidator;

    /**
     * Valid formats to check user input against
     * @var array
     */
    public $patterns;

    /**
     * Create a new DateTimePicker
     *
     * @param array|string|\Zend_Config $spec
     * @param null $options
     * @see Zend_Form_Element::__construct()
     */
    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);
        $this->dateValidator = new DateTimeValidator($this->patterns);
        $this->addValidator($this->dateValidator);

    }

    /**
     * Validate filtered date/time strings
     *
     * Expects one or more valid formats being set in $this->patterns. Sets element value as Unix timestamp
     * if the input is considered valid. Utilizes DateTimeFactory to ensure time zone awareness.
     *
     * @param   string  $value
     * @param   mixed   $context
     * @return  bool
     */
    public function isValid($value, $context = null)
    {
        // Overwrite the internal validator to use

        if (!parent::isValid($value, $context)) {
            return false;
        }
        $pattern = $this->dateValidator->getValidPattern();
        if (!$pattern) {
            $this->setValue($value);
            return true;
        }
        $this->setValue(DateTimeFactory::parse($value, $pattern)->getTimestamp());
        return true;
    }
}
