<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Widget;

use Icinga\Web\Form;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;
use Icinga\Web\Widget\AbstractWidget;

class StateBadges extends AbstractWidget
{
    const STATE_CRITICAL = 'state-critical';

    const STATE_CRITICAL_HANDLED = 'state-critical-handled';

    const STATE_OK = 'state-ok';

    const STATE_PENDING = 'state-pending';

    const STATE_UNKNOWN = 'state-unknown';

    const STATE_UNKNOWN_HANDLED = 'state-unknown-handled';

    const STATE_UNREACHABLE = 'state-unreachable';

    const STATE_UNREACHABLE_HANDLED = 'state-unreachable-handled';

    const STATE_UP = 'state-up';

    const STATE_WARNING = 'state-warning';

    const STATE_WARNING_HANDLED = 'state-warning-handled';

    protected $badges = array();

    protected $url;

    public function getBadges()
    {
        return $this->badges;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        if (! $url instanceof $url) {
            $url = Url::fromPath($url);
        }
        $this->url = $url;
        return $this;
    }

    public function add(
        $state, array $filter, $count, $translateSingular, $translatePlural, array $translateArgs = array()
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

    public function createBadge($state, Navigation $badges)
    {
        if ($this->has($state)) {
            $badge = $this->get($state);
            $badges->addItem(array(
                'class' => static::STATE_OK,
                'label' => $badge->count,
                'url'   => $this->url
            ));
        }
        return $this;
    }

    public function createBadgeGroup(array $states, Navigation $badges)
    {
        $group = array_intersect_key($this->badges, array_flip($states));
        if (! empty($group)) {
            $groupItem = new NavigationItem();
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

    public function has($state)
    {
        return isset($this->badges[$state]) && $this->badges[$state]->count;
    }

    public function get($state)
    {
        return $this->badges[$state];
    }

    public function render()
    {
        $badges = new Navigation();
        $badges->setLayout(Navigation::LAYOUT_TABS);
        $this
            ->createBadge(static::STATE_OK, $badges)
            ->createBadgeGroup(
                array(static::STATE_WARNING, static::STATE_WARNING_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_CRITICAL, static::STATE_CRITICAL_HANDLED),
                $badges
            )
            ->createBadgeGroup(
                array(static::STATE_UNREACHABLE, static::STATE_UNREACHABLE_HANDLED),
                $badges
            )
            ->createBadge(static::STATE_UNKNOWN, $badges)
            ->createBadge(static::STATE_PENDING, $badges);
        return $badges->getRenderer()->render();
    }
}
