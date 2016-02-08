<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\Validator\TriStateValidator;
use Zend_Form_Element_Xhtml;

/**
 * A checkbox that can display three different states:
 * true, false and mixed. When there is no JavaScript
 * available to display the checkbox properly, a radio
 * button-group with all three possible states will be
 * displayed.
 */
class TriStateCheckbox extends Zend_Form_Element_Xhtml
{
    /**
     * Name of the view helper
     *
     * @var string
     */
    public $helper = 'formTriStateCheckbox';

    public function __construct($name, $options = null)
    {
        parent::__construct($name, $options);

        $this->triStateValidator = new TriStateValidator($this->patterns);
        $this->addValidator($this->triStateValidator);
    }
}
