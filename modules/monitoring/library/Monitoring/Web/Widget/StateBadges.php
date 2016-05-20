<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Data\Filter\Filter;

class StateBadges extends AbstractWidget
{
    /**
     * CSS class for the widget
     *
     * @var string
     */
    const CSS_CLASS = 'state-badges';

    /**
     * State critical
     *
     * @var string
     */
    const STATE_CRITICAL = 'state-critical';

    /**
     * State critical handled
     *
     * @var string
     */
    const STATE_CRITICAL_HANDLED = 'state-critical handled';

    /**
     * State down
     *
     * @var string
     */
    const STATE_DOWN = 'state-down';

    /**
     * State down handled
     *
     * @var string
     */
    const STATE_DOWN_HANDLED = 'state-down handled';

    /**
     * State ok
     *
     * @var string
     */
    const STATE_OK = 'state-ok';

    /**
     * State pending
     *
     * @var string
     */
    const STATE_PENDING = 'state-pending';

    /**
     * State unknown
     *
     * @var string
     */
    const STATE_UNKNOWN = 'state-unknown';

    /**
     * State unknown handled
     *
     * @var string
     */
    const STATE_UNKNOWN_HANDLED = 'state-unknown handled';

    /**
     * State unreachable
     *
     * @var string
     */
    const STATE_UNREACHABLE = 'state-unreachable';

    /**
     * State unreachable handled
     *
     * @var string
     */
    const STATE_UNREACHABLE_HANDLED = 'state-unreachable handled';

    /**
     * State up
     *
     * @var string
     */
    const STATE_UP = 'state-up';

    /**
     * State warning
     *
     * @var string
     */
    const STATE_WARNING = 'state-warning';

    /**
     * State warning handled
     *
     * @var string
     */
    const STATE_WARNING_HANDLED = 'state-warning handled';

    /**
     * State badges
     *
     * @var object[]
     */
    protected $badges = array();

    /**
     * Internal counter for badge priorities
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * The base filter applied to any badge link
     *
     * @var Filter
     */
    protected $baseFilter;

    /**
     * Base URL
     *
     * @var Url
     */
    protected $url;

    /**
     * Get the base URL
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the base URL
     *
     * @param   Url|string $url
     *
     * @return  $this
     */
    public function setUrl($url)
    {
        if (! $url instanceof $url) {
            $url = Url::fromPath($url);
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Get the base filter
     *
     * @return  Filter
     */
    public function getBaseFilter()
    {
        return $this->baseFilter;
    }

    /**
     * Set the base filter
     *
     * @param   Filter $baseFilter
     *
     * @return  $this
     */
    public function setBaseFilter($baseFilter)
    {
        $this->baseFilter = $baseFilter;
        return $this;
    }

    /**
     * Add a state badge
     *
     * @param   string  $state
     * @param   int     $count
     * @param   array   $filter
     * @param   string  $translateSingular
     * @param   string  $translatePlural
     * @param   array   $translateArgs
     *
     * @return  $this
     */
    public function add(
        $state, $count, array $filter, $translateSingular, $translatePlural, array $translateArgs = array()
    ) {
        $this->badges[$state] = (object) array(
            'count'             => (int) $count,
            'filter'            => $filter,
            'translateArgs'     => $translateArgs,
            'translatePlural'   => $translatePlural,
            'translateSingular' => $translateSingular
        );
        return $this;
    }

    /**
     * Create a badge
     *
     * @param   string $state
     * @param   Navigation $badges
     *
     * @return  $this
     */
    public function createBadge($state, Navigation $badges)
    {
        if ($this->has($state)) {
            $badge = $this->get($state);
            $url = clone $this->url->setParams($badge->filter);
            if (isset($this->baseFilter)) {
                $url->addFilter($this->baseFilter);
            }
            $badges->addItem(new NavigationItem($state, array(
                'attributes'    => array('class' => 'badge ' . $state),
                'label'         => $badge->count,
                'priority'      => $this->priority++,
                'title'         => vsprintf(
                    mtp('monitoring', $badge->translateSingular, $badge->translatePlural, $badge->count),
                    $badge->translateArgs
                ),
                'url'           => $url
            )));
        }
        return $this;
    }

    /**
     * Create a badge group
     *
     * @param   array $states
     * @param   Navigation $badges
     *
     * @return  $this
     */
    public function createBadgeGroup(array $states, Navigation $badges)
    {
        $group = array_intersect_key($this->badges, array_flip($states));
        if (! empty($group)) {
            $groupItem = new NavigationItem(
                uniqid(),
                array(
                    'cssClass'  => 'state-badge-group',
                    'label'     => '',
                    'priority'  => $this->priority++
                )
            );
            $groupBadges = new Navigation();
            $groupBadges->setLayout(Navigation::LAYOUT_TABS);
            foreach (array_keys($group) as $state) {
                $this->createBadge($state, $groupBadges);
            }
            $groupItem->setChildren($groupBadges);
            $badges->addItem($groupItem);
        }
        return $this;
    }

    /**
     * Get whether a badge for the given state has been added
     *
     * @param   string $state
     *
     * @return  bool
     */
    public function has($state)
    {
        return isset($this->badges[$state]) && $this->badges[$state]->count;
    }

    /**
     * Get the badge for the given state
     *
     * @param   string $state
     *
     * @return  object
     */
    public function get($state)
    {
        return $this->badges[$state];
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $badges = new Navigation();
        $badges->setLayout(Navigation::LAYOUT_TABS);
        $this
            ->createBadgeGroup(
                array(static::STATE_CRITICAL, static::STATE_CRITICAL_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_DOWN, static::STATE_DOWN_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_WARNING, static::STATE_WARNING_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_UNREACHABLE, static::STATE_UNREACHABLE_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_UNKNOWN, static::STATE_UNKNOWN_HANDLED),
                $badges
            )
            ->createBadge(static::STATE_OK, $badges)
            ->createBadge(static::STATE_UP, $badges)
            ->createBadge(static::STATE_PENDING, $badges);
        return $badges
            ->getRenderer()
            ->setCssClass(static::CSS_CLASS)
            ->render();
    }
}
