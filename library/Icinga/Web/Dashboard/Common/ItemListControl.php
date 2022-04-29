<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

abstract class ItemListControl extends BaseHtmlElement
{
    protected $tag = 'div';

    /**
     * Get this item's unique html identifier
     *
     * @return string
     */
    abstract protected function getHtmlId(): string;

    /**
     * Get a class name for the collapsible control
     *
     * @return string
     */
    abstract protected function getCollapsibleControlClass(): string;

    /**
     * Create an action link to be added at the end of the list
     *
     * @return BaseHtmlElement
     */
    abstract protected function createActionLink(): BaseHtmlElement;

    /**
     * Create the appropriate item list of this control
     *
     * @return BaseHtmlElement
     */
    abstract protected function createItemList(): BaseHtmlElement;

    /**
     * Get a drag initiator for this widget item
     *
     * @return ValidHtml
     */
    public static function createDragInitiator()
    {
        return new Icon('bars', ['class' => 'widget-drag-initiator']);
    }

    /**
     * Assemble a header element for this item list
     *
     * @param Url $url
     * @param string $title
     *
     * @return void
     */
    protected function assembleHeader(Url $url, string $title)
    {
        $header = HtmlElement::create('h1', ['class' => 'collapsible-header'], $title);
        $header->addHtml(new ActionLink(t('Edit'), $url, null, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $header->addHtml(self::createDragInitiator());
        $this->addHtml($header);
    }

    protected function assemble()
    {
        $this->getAttributes()->add([
            'id'                  => $this->getHtmlId(),
            'class'               => 'collapsible',
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
