<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

abstract class ItemListControl extends BaseHtmlElement
{
    protected $tag = 'div';

    /**
     * Get this item's unique html identifier
     *
     * @return string
     */
    abstract protected function getHtmlId();

    /**
     * Get a class name for the collapsible control
     *
     * @return string
     */
    abstract protected function getCollapsibleControlClass();

    /**
     * Create an action link to be added at the end of the list
     *
     * @return HtmlElement
     */
    abstract protected function createActionLink();

    /**
     * Create the appropriate item list of this control
     *
     * @return HtmlElement
     */
    abstract protected function createItemList();

    /**
     * Assemble a header element for this item list
     *
     * @param Url $url
     * @param string $header
     *
     * @return void
     */
    protected function assembleHeader(Url $url, $header)
    {
        $header = HtmlElement::create('h1', ['class' => 'collapsible-header'], $header);
        $header->addHtml(new Link(t('Edit'), $url, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $this->addHtml($header);
    }

    protected function assemble()
    {
        $this->getAttributes()->add([
            'id'                  => $this->getHtmlId(),
            'class'               => ['collapsible'],
            'data-toggle-element' => '.dashboard-list-info',
        ]);

        $this->addHtml(HtmlElement::create('div', ['class' => $this->getCollapsibleControlClass()], [
            new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
        ]));

        $this->addHtml($this->createItemList());
        $actionLink = $this->createActionLink();
        $actionLink->getAttributes()->add([
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]);

        $this->addHtml($actionLink);
    }
}
