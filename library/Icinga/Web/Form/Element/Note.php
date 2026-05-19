<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\FormElement;

/**
 * A note
 */
class Note extends FormElement
{
    /**
     * Form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formNote';

    /**
     * Ignore element when retrieving values at form level
     *
     * @var bool
     */
    protected $_ignore = true;

    /**
     * (non-PHPDoc)
     * @see Zend_Form_Element::init() For the method documentation.
     */
    public function init()
    {
        if (count($this->getDecorators()) === 0) {
            $this->setDecorators([
                'ViewHelper',
                [
                    'HtmlTag',
                    ['tag' => 'p']
                ]
            ]);
        }
    }

    /**
     * Validate element value (pseudo)
     *
     * @param   mixed $value    Ignored
     *
     * @return  bool            Always true
     */
    public function isValid($value, $context = null)
    {
        return true;
    }
}
