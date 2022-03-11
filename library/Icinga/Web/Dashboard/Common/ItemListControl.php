<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Widget\Icon;

abstract class ItemListControl extends BaseHtmlElement
{
    protected $tag = 'div';

    /**
     * Set a class name for the collapsible control
     *
     * @var string
     */
    protected $collapsibleControlClass;

    protected function setCollapsibleControlClass($class)
    {
        $this->collapsibleControlClass = $class;

        return $this;
    }

    protected function assemble()
    {
        $this->addHtml(HtmlElement::create('div', ['class' => $this->collapsibleControlClass], [
            new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
        ]));

        $this->getAttributes()->registerAttributeCallback('draggable', function () {
            return 'true';
        });
    }
}
