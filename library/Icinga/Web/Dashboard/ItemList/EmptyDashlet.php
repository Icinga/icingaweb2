<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;

class EmptyDashlet extends DashletListMultiSelect
{
    protected function createEmptyList()
    {
        return HtmlElement::create('ul', ['class' => 'dashlet-item-list empty-list']);
    }

    protected function assembleHeader(): ValidHtml
    {
        $header = HtmlElement::create('h1', ['class' => 'dashlet-header']);
        $header->add(t('Custom Url'));

        return $header;
    }

    protected function assembleSummary()
    {
        $section = HtmlElement::create('section', ['class' => 'caption']);
        $section->add(t('Create a dashlet with custom url and filter'));

        return $section;
    }

    protected function assemble()
    {
        $this->addHtml($this->assembleHeader());
        $this->addHtml($this->assembleSummary());

        $this->addWrapper($this->createLabel());
        $this->addWrapper($this->createEmptyList());
    }
}
