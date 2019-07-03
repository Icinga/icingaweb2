<?php
/* Icinga Web 2 | (c) 2019 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Element;

use Icinga\Web\Form\FormElement;

class Textarea extends FormElement
{
    public $helper = 'formTextarea';

    public function __construct($spec, $options = null)
    {
        parent::__construct($spec, $options);

        if ($this->getAttrib('rows') === null) {
            $this->setAttrib('rows', 3);
        }
    }
}
