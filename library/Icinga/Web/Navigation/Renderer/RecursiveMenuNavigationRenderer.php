<?php

// SPDX-FileCopyrightText: 2025 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
        if (! $this->getUseStandardItemRenderer() && $parentItem->shouldRender()) {
            $renderer->setEscapeLabel($parentItem->getRenderedLabelEscaping());
        }
        $content = $renderer->render($item);

        $this->content[] = $this->getInnerIterator()->beginItemMarkup($item);
        $this->content[] = $content;
        $this->content[] = $this->getInnerIterator()->endItemMarkup();
    }
}
