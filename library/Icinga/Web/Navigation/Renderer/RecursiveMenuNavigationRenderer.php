<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\NavigationItem;

/**
 * Renderer for the multi level navigation in the sidebar menu
 */
class RecursiveMenuNavigationRenderer extends RecursiveNavigationRenderer
{
    public function beginChildren(): void
    {
        parent::beginChildren();

        $parentItem = $this->getInnerIterator()->current()->getParent();
        $item = new NavigationItem($parentItem->getName());
        $item->setLabel($parentItem->getLabel());
        $item->setCssClass('nav-item-header');

        $renderer = new NavigationItemRenderer();
        $renderer->setEscapeLabel(false);
        $content = $renderer->render($item);

        $this->content[] = $this->getInnerIterator()->beginItemMarkup($item);
        $this->content[] = $content;
        $this->content[] = $this->getInnerIterator()->endItemMarkup();
    }
}
