<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Icon;

abstract class ItemListControl extends BaseHtmlElement
{
    protected $tag = 'details';

    /**
     * Get this item's unique html identifier
     *
     * @return string
     */
    abstract protected function getHtmlId(): string;

    /**
     * Get whether the item should be expanded by default
     *
     * @return bool
     */
    abstract protected function shouldExpandByDefault(): bool;

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
     * Get whether to render the drag initiator icon bars
     *
     * @return bool
     */
    protected function shouldRenderDragInitiator(): bool
    {
        return true;
    }

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
        $header = HtmlElement::create('summary', ['class' => 'collapsible-header']);
        $header->addHtml(
            new Icon('angle-right', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-down', ['class' => 'collapse-icon', 'title' => t('Collapse')])
        );
        $header->addHtml(Text::create($title));
        $header->addHtml(new ActionLink(t('Edit'), $url, null, [
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]));

        $header->addHtml(HtmlElement::create('div', ['class' => 'spacer']));
        if ($this->shouldRenderDragInitiator()) {
            $header->addHtml(self::createDragInitiator());
        }

        $this->addHtml($header);
    }

    protected function assemble()
    {
        $this->getAttributes()->add([
            'id'    => $this->getHtmlId(),
            'class' => 'collapsible',
            'open'  => $this->shouldExpandByDefault()
        ]);

        $this->addHtml($this->createItemList());

        $actionLink = $this->createActionLink();
        $actionLink->getAttributes()->add([
            'data-icinga-modal'   => true,
            'data-no-icinga-ajax' => true
        ]);

        $this->addHtml($actionLink);
    }
}
