<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use ipl\Html\BaseHtmlElement;
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
    public function setCheckBox(FormElement $checkbox): self
    {
        $this->checkbox = $checkbox;

        return $this;
    }

    protected function createLabel(): BaseHtmlElement
    {
        $label = HtmlElement::create('label');
        $label->addHtml($this->checkbox);

        return $label;
    }

    protected function assemble()
    {
        parent::assemble();

        $itemContent = HtmlElement::create('div', ['class' => 'dashlet-form-items']);
        $itemContent->addFrom($this);
        $this->setHtmlContent();

        $label = $this->createLabel();
        $label->addHtml($itemContent);
        $this->addHtml($label);
    }
}
