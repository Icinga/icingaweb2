<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

/**
 * Badge renderer summing up the worst state of its children
 */
class SummaryNavigationItemRenderer extends BadgeNavigationItemRenderer
{
    /**
     * Cached count
     *
     * @var int
     */
    protected $count;

    /**
     * State to severity map
     *
     * @var array
     */
    protected static $stateSeverityMap = array(
        self::STATE_OK          => 0,
        self::STATE_PENDING     => 1,
        self::STATE_UNKNOWN     => 2,
        self::STATE_WARNING     => 3,
        self::STATE_CRITICAL    => 4,
    );

    /**
     * Severity to state map
     *
     * @var array
     */
    protected static $severityStateMap = array(
        self::STATE_OK,
        self::STATE_PENDING,
        self::STATE_UNKNOWN,
        self::STATE_WARNING,
        self::STATE_CRITICAL
    );

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        if ($this->count === null) {
            $countMap = array_fill(0, 5, 0);
            $maxSeverity = 0;
            $titles = array();
            foreach ($this->getItem()->getChildren() as $child) {
                $renderer = $child->getRenderer();
                if ($renderer instanceof BadgeNavigationItemRenderer) {
                    $count = $renderer->getCount();
                    if ($count) {
                        $severity = static::$stateSeverityMap[$renderer->getState()];
                        $countMap[$severity] += $count;
                        $titles[] = $renderer->getTitle();
                        $maxSeverity = max($maxSeverity, $severity);
                    }
                }
            }
            $this->count = $countMap[$maxSeverity];
            $this->state = static::$severityStateMap[$maxSeverity];
            $this->title = implode('. ', $titles);
        }

        return $this->count;
    }
}
