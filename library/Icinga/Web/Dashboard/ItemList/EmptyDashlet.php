<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;

class EmptyDashlet extends DashletListMultiSelect
{
    protected function assembleHeader(): BaseHtmlElement
    {
        $header = HtmlElement::create('div', ['class' => 'dashlet-header']);
        $header->add(t('Custom Url'));

        return $header;
    }

    protected function assembleSummary(): BaseHtmlElement
    {
        $section = HtmlElement::create('section', ['class' => 'caption']);
        $section->add(t('Create a dashlet with custom url and filter'));

        return $section;
    }
}
