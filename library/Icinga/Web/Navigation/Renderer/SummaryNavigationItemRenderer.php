<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Web\Navigation\Renderer\BadgeNavigationItemRenderer;

/**
 * Summary badge adding up all badges in the navigation's children that have the same state
 */
class SummaryNavigationItemRenderer extends BadgeNavigationItemRenderer
{
    /**
     * The title of each summarized child
     *
     * @var array
     */
    protected $titles;

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        $count = 0;
        foreach ($this->getItem()->getChildren() as $child) {
            $renderer = $child->getRenderer();
            if ($renderer instanceof BadgeNavigationItemRenderer) {
                if ($renderer->getState() === $this->getState()) {
                    $this->titles[] = $renderer->getTitle();
                    $count += $renderer->getCount();
                }
            }
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return ! empty($this->titles) ? join(', ', $this->titles) : '';
    }
}
