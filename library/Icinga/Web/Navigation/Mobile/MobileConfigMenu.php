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

    protected function assembleLevel2Nav(BaseHtmlElement $level2Nav)
    {
        parent::assembleLevel2Nav($level2Nav);

        $username = Auth::getInstance()->getUser()->getUsername();
        $level2Nav->getFirst('div')->prependHtml(HtmlElement::create('ul', ['class' => 'nav flyout-menu'], [
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
    }

    protected function assemble()
    {
        $this->healthBadge = $this->createHealthBadge();

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
