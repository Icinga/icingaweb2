<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\FormElement;

/**
 * A time input control
 */
class Time extends FormElement
{
    /**
     * Form view helper to use for rendering
     *
     * @var string
     */
    public $helper = 'formTime';
}
