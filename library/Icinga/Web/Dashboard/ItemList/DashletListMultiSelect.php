<?php

namespace Icinga\Web\Dashboard\ItemList;

use ipl\Html\Contract\FormElement;
use ipl\Html\HtmlElement;

class DashletListMultiSelect extends DashletListItem
{
    /** @var FormElement */
    protected $checkbox;

    /**
     * Set a checkbox to be applied to the dashlet to enable multiselect
     *
     * @param FormElement $checkbox
     *
     * @return $this
     */
    public function setCheckBox(FormElement $checkbox)
    {
        $this->checkbox = $checkbox;

        return $this;
    }

    protected function createLabel()
    {
        $label = HtmlElement::create('label');
        $label->addHtml($this->checkbox);

        return $label;
    }

    protected function assemble()
    {
        parent::assemble();

        $this->addWrapper($this->createLabel());
    }
}
