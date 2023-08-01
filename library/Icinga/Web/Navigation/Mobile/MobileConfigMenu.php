<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation\Mobile;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Common\HealthBadgeTrait;
use Icinga\Web\Navigation\ConfigMenu;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class MobileConfigMenu extends ConfigMenu
{
    use HealthBadgeTrait;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class' => 'nav user-nav-item',
        'id' => 'mobile-config-menu'
    ];

    protected $flyoutID = 'mobile-config-menu-flyout';

    protected function isSelectedItem($item)
    {
        if ($item !== null && Icinga::app()->getRequest()->getUrl()->matches($item['url'])) {
            $this->selected = $item;
            return true;
        }

        return false;
    }

    protected function assembleLevel2Nav(BaseHtmlElement $level2Nav)
    {
        $username = Auth::getInstance()->getUser()->getUsername();
        $navContent = HtmlElement::create('div', ['class' => 'flyout-content']);
        $navContent->add(HtmlElement::create('ul', ['class' => 'nav flyout-menu'], [
            HtmlElement::create('li', ['class' => 'has-icon'], [
                HtmlElement::create(
                    'a',
                    Attributes::create(['href' => Url::fromPath('account')]),
                    [
                        HtmlElement::create('i', ['class' => 'user-ball'], Text::create($username[0])),
                        Text::create($username)
                    ]
                )
            ])
        ]));
        foreach ($this->children as $c) {
            if (isset($c['permission']) && ! Auth::getInstance()->hasPermission($c['permission'])) {
                continue;
            }

            if (isset($c['title'])) {
                $navContent->add(HtmlElement::create(
                    'h3',
                    null,
                    $c['title']
                ));
            }

            $ul = HtmlElement::create('ul', ['class' => 'nav flyout-menu']);
            foreach ($c['items'] as $key => $item) {
                $ul->add($this->createLevel2MenuItem($item, $key));
            }

            $navContent->add($ul);
        }
        $level2Nav->add($navContent);
    }

    protected function assemble()
    {
        $username = Auth::getInstance()->getUser()->getUsername();

        $button = HtmlElement::create(
            'button',
            ['id' => 'toggle-mobile-config-flyout'],
            [
                new HtmlElement(
                    'i',
                    Attributes::create(['class' => 'user-ball']),
                    Text::create($username[0])
                )
            ]
        );

        $this->add(HtmlElement::create('li', null, [
            $this->createLevel2Menu(),
            $button,
            $this->healthBadge
        ]));
    }
}
