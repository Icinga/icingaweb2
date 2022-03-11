<?php

namespace Icinga\Web\Dashboard\ItemList;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

class DashletListItem extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'dashlet-list-item'];

    protected $tag = 'li';

    /** @var Dashlet */
    protected $dashlet;

    protected $renderEditButton;

    public function __construct(Dashlet $dashlet = null, $renderEditButton = false)
    {
        $this->dashlet = $dashlet;
        $this->renderEditButton = $renderEditButton;
    }

    /**
     * Set whether to render an edit button for this dashlet
     *
     * @param bool $value
     *
     * @return $this
     */
    protected function setDetailUrl(bool $value)
    {
        $this->renderEditButton = $value;

        return $this;
    }

    protected function assembleTitle()
    {
        $title = HtmlElement::create('h1', ['class' => 'dashlet-header']);

        if (! $this->dashlet) {
            $title->add(t('Custom Url'));
        } else {
            $title->add($this->dashlet->getTitle());

            if ($this->renderEditButton) {
                $pane = $this->dashlet->getPane();
                $url = Url::fromPath(Dashboard::BASE_ROUTE . '/edit-dashlet');
                $url->setParams([
                    'home'      => $pane->getHome()->getName(),
                    'pane'      => $pane->getName(),
                    'dashlet'   => $this->dashlet->getName()
                ]);

                $title->addHtml(new Link(t('Edit'), $url, [
                    'data-icinga-modal'   => true,
                    'data-no-icinga-ajax' => true
                ]));
            }
        }

        return $title;
    }

    protected function assembleSummary()
    {
        $section = HtmlElement::create('section', ['class' => 'caption']);

        if (! $this->dashlet) {
            $section->add(t('Create a dashlet with custom url and filter'));
        } else {
            $section->add($this->dashlet->getDescription() ?: $this->dashlet->getTitle());
        }

        return $section;
    }

    protected function assemble()
    {
        $this->addHtml($this->assembleTitle());
        $this->addHtml($this->assembleSummary());
    }
}
