<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation\Mobile;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Common\HealthBadgeTrait;
use Icinga\Web\Menu;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Widget\Icon;

class MobileNavigation extends BaseHtmlElement
{
    use HealthBadgeTrait;

    const MAX_NUM_OF_ITEMS = 5;

    const EXCLUDED_ITEMS = [
        'configuration',
        'system',
        'user'
    ];

    protected $tag = 'nav';

    protected $items;

    public function __construct()
    {
        $menu = new Menu();
        $this->items = $menu;

        $this->items->order();
    }

    protected function createMoreItem()
    {
        $moreMenu = new HtmlElement('li');

        $this->assembleMoreItem($moreMenu);

        return $moreMenu;
    }

    protected function assembleMoreItem(BaseHtmlElement $moreMenu)
    {
        $moreMenu->add(
            HtmlElement::create(
                'button',
                Attributes::create(['id' => 'toggle-more']),
                [
                    new Icon('ellipsis-v'),
                    Text::create('More')
                ]
            )
        );

        $moreMenu->add($this->createMoreFlyout());
    }

    protected function createNavItemIcon($item)
    {
        if ($item->getUrl() !== null && substr($item->getUrl()->getPath(), 0, 9) === 'icingadb/') {
            $icon = new Icon($item->getIcon(), [ 'aria-hidden' => 1]);
            return $icon;
        }

        return new HtmlString(Icinga::app()->getViewRenderer()->view->icon($item->getIcon()));
    }

    protected function createMoreFlyout()
    {
        $moreFlyout = new HtmlElement('div', Attributes::create(['class' => 'flyout']));

        $this->assembleMoreFlyout($moreFlyout);

        return $moreFlyout;
    }

    protected function assembleMoreFlyout($moreFlyout)
    {
        $flyoutContent = HtmlElement::create('div', ['class' => 'flyout-content']);
        $ul = HtmlElement::create('ul', ['class' => 'nav flyout-menu nav-level-2']);

        $startIndex = 0;
        foreach ($this->items as $key => $item) {
            if ($this->isValidItem($key, $item)) {
                if (++$startIndex > self::MAX_NUM_OF_ITEMS - 1) {
                    $li = $this->createMenuItem($item, $key);
                    $ul->add($li);
                }
            }
        }

        $flyoutContent->add($ul);
        $moreFlyout->add($flyoutContent);
    }

    protected function createMenuItem($item, $key)
    {
        if (in_array($key, self::EXCLUDED_ITEMS)) {
            return null;
        }

        if (isset($item->permission) && ! Auth::getInstance()->hasPermission($item->permission)) {
            return null;
        }

        $class = $item->getCssClass() .
            ' '.
            ($item->getActive() ? ' active' : '') .
            ' ' .
            ($item->getSelected() ? ' selected' : '') .
            ' ' .
            (!empty($item->getIcon()) ? ' has-icon' : '');

        $menuItem = HtmlElement::create(
            'li',
            ['class' => $class],
            [
                HtmlElement::create('a', [
                    'href' => $item->getUrl(),
                ], [
                    $this->createNavItemIcon($item),
                    Text::create($item->getLabel())
                ])
            ]
        );

        return $menuItem;
    }

    protected function getValidItemCount()
    {
        $count = 0;
        foreach ($this->items as $key => $item) {
            if ($this->isValidItem($key, $item)) {
                $count++;
            }
        }

        return $count;
    }

    protected function isValidItem($key, $item)
    {
        if (in_array($key, self::EXCLUDED_ITEMS) || !($item->hasChildren())) {
            if ($key === 'dashboard') {
                return true;
            }
            return false;
        }
        return true;
    }

    protected function assemble()
    {
        $ul = new HtmlElement('ul', Attributes::create(['class' => 'nav nav-level-1']));
        $validItemCount = $this->getValidItemCount();

        $count = 0;
        foreach ($this->items as $key => $item) {
            if ($this->isValidItem($key, $item)) {
                $count++;

                if ($validItemCount == self::MAX_NUM_OF_ITEMS && $count == self::MAX_NUM_OF_ITEMS) {
                    $ul->add($this->createMenuItem($item, $key));
                    break;
                }

                if ($count > self::MAX_NUM_OF_ITEMS - 1) {
                    $ul->add($this->createMoreItem());
                    break;
                }

                $ul->add($this->createMenuItem($item, $key));
            }
        }

        $this->add($ul);
    }
}
