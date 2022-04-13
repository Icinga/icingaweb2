<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class DashletListItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'dashlet-list-item',];

    protected $tag = 'li';

    /** @var Dashlet */
    protected $dashlet;

    /** @var bool Whether to render an edit button for the dashlet */
    protected $renderEditButton;

    public function __construct(Dashlet $dashlet = null, $renderEditButton = false)
    {
        $this->dashlet = $dashlet;
        $this->renderEditButton = $renderEditButton;

        if ($this->dashlet && $renderEditButton) {
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
     * Set whether to render an edit button for this dashlet
     *
     * @param bool $value
     *
     * @return $this
     */
    protected function setDetailUrl(bool $value): self
    {
        $this->renderEditButton = $value;

        return $this;
    }

    protected function assembleHeader(): HtmlElement
    {
        $title = HtmlElement::create('h1', ['class' => 'dashlet-header']);

        if ($this->renderEditButton) {
            $title->addHtml(new Link(
                t($this->dashlet->getTitle()),
                $this->dashlet->getUrl()->getUrlWithout(['showCompact', 'limit'])->getRelativeUrl(),
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

            $title->addHtml(new Link(t('Edit'), $url, [
                'data-icinga-modal'   => true,
                'data-no-icinga-ajax' => true
            ]));
        } else {
            $title->add($this->dashlet->getTitle());
            $title->getAttributes()->set('title', $this->dashlet->getTitle());
        }

        return $title;
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
