<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Common\ItemListControl;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\ActionLink;
use ipl\Web\Widget\Link;

class DashletListItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'dashlet-list-item',];

    protected $tag = 'li';

    /** @var Dashlet */
    protected $dashlet;

    /** @var bool Whether to render the detail actions for this dashlet item */
    protected $renderDetailActions;

    public function __construct(Dashlet $dashlet = null, $renderDetailActions = false)
    {
        $this->dashlet = $dashlet;
        $this->renderDetailActions = $renderDetailActions;

        if ($this->dashlet && $renderDetailActions) {
            $this->getAttributes()
                ->registerAttributeCallback('data-icinga-dashlet', function () {
                    return $this->dashlet->getName();
                })
                ->registerAttributeCallback('id', function () {
                    return bin2hex($this->dashlet->getUuid());
                });
        }
    }

    /**
     * Set whether to render the detail actions for this dashlet
     *
     * @param bool $value
     *
     * @return $this
     */
    protected function setRenderDetailActions(bool $value): self
    {
        $this->renderDetailActions = $value;

        return $this;
    }

    /**
     * Assemble header of this dashlet item
     *
     * @return ValidHtml
     */
    protected function assembleHeader(): ValidHtml
    {
        $header = HtmlElement::create('h1', ['class' => 'dashlet-header']);

        if ($this->renderDetailActions) {
            $header->addHtml(new Link(
                t($this->dashlet->getTitle()),
                $this->dashlet->getUrl()->getRelativeUrl(),
                [
                    'class'            => 'dashlet-title',
                    'aria-label'       => t($this->dashlet->getTitle()),
                    'title'            => t($this->dashlet->getTitle()),
                    'data-base-target' => '_next'
                ]
            ));

            $pane = $this->dashlet->getPane();
            $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-dashlet');
            $url->setParams([
                'home'    => $pane->getHome()->getName(),
                'pane'    => $pane->getName(),
                'dashlet' => $this->dashlet->getName()
            ]);

            $header->addHtml(new ActionLink(t('Edit'), $url, null, [
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]));

            $header->addHtml(ItemListControl::createDragInitiator());
        } else {
            $header->add($this->dashlet->getTitle());
            $header->getAttributes()->set('title', $this->dashlet->getTitle());
        }

        return $header;
    }

    protected function assembleSummary()
    {
        $section = HtmlElement::create('section', ['class' => 'caption']);

        if ($this->dashlet) {
            $description = $this->dashlet->getDescription() ?: t('There is no provided description.');
            $section->getAttributes()->set('title', $description);

            $section->add($description);
        }

        return $section;
    }

    protected function assemble()
    {
        $this->addHtml($this->assembleHeader());
        $this->addHtml($this->assembleSummary());
    }
}
