<?php

namespace Icinga\Web\Navigation\Mobile;

use Icinga\Application\Icinga;
use Icinga\Authentication\Auth;
use Icinga\Common\HealthBadgeTrait;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class MobileConfigMenu extends BaseHtmlElement
{
    use HealthBadgeTrait;

    protected $tag = 'ul';

    protected $defaultAttributes = [
        'class' => 'nav user-nav-item',
        'id' => 'mobile-config-menu'
    ];

    protected $children;

    protected $healthBadge;

    public function __construct()
    {
        $this->children = [
            'system' => [
                'title' => t('System'),
                'items' => [
                    'about' => [
                        'label' => t('About'),
                        'url' => 'about'
                    ],
                    'health' => [
                        'label' => t('Health'),
                        'url' => 'health',
                    ],
                    'announcements' => [
                        'label' => t('Announcements'),
                        'url' => 'announcements'
                    ],
                    'sessions' => [
                        'label' => t('User Sessions'),
                        'permission' => 'application/sessions',
                        'url' => 'manage-user-devices'
                    ]
                ]
            ],
            'configuration' => [
                'title' => t('Configuration'),
                'permission' => 'config/*',
                'items' => [
                    'application' => [
                        'label' => t('Application'),
                        'url' => 'config/general'
                    ],
                    'authentication' => [
                        'label' => t('Access Control'),
                        'permission'  => 'config/access-control/*',
                        'url' => 'role/list'
                    ],
                    'navigation' => [
                        'label' => t('Shared Navigation'),
                        'permission'  => 'config/navigation',
                        'url' => 'navigation'
                    ],
                    'modules' => [
                        'label' => t('Modules'),
                        'permission'  => 'config/modules',
                        'url' => 'config/modules'
                    ]
                ]
            ],
            'logout' => [
                'items' => [
                    'logout' => [
                        'label' => t('Logout'),
                        'icon' => 'power-off',
                        'atts'  => [
                            'target' => '_self',
                            'class' => 'nav-item-logout'
                        ],
                        'url'         => 'authentication/logout'
                    ]
                ]
            ]
        ];

        $this->healthBadge = $this->createHealthBadge();
    }

    protected function createLevel2MenuItem($item, $key)
    {
        if (isset($item['permission']) && ! Auth::getInstance()->hasPermission($item['permission'])) {
            return null;
        }

        $class = null;
        if ($key === 'health') {
            $class = 'badge-nav-item';
        }

        $icon = null;
        $iconClass = null;
        if (isset($item['icon'])) {
            $icon =  new Icon($item['icon']);
            $iconClass = 'has-icon';
        }

        $li = HtmlElement::create(
            'li',
            isset($item['atts']) ? $item['atts'] : null,
            [
                HtmlElement::create(
                    'a',
                    Attributes::create(['href' => Url::fromPath($item['url'])]),
                    [
                        $icon,
                        $item['label'],
                        $key === 'health' ? $this->healthBadge : null
                    ]
                ),
            ]
        );

        $li->addAttributes(['class' => $class]);
        $li->addAttributes(['class' => $iconClass]);

        if ($this->isSelectedItem($item)) {
            $li->addAttributes(['class' => 'selected']);
            $this->cogItemActive = true;
        }

        return $li;
    }

    protected function isSelectedItem($item)
    {
        if ($item !== null && Icinga::app()->getRequest()->getUrl()->matches($item['url'])) {
            $this->selected = $item;
            return true;
        }

        return false;
    }

    protected function createLevel2Menu()
    {
        $level2Nav = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'nav-level-1 flyout'])
        );

        $this->assembleLevel2Nav($level2Nav);

        return $level2Nav;
    }

    protected function assembleLevel2Nav(BaseHtmlElement $level2Nav)
    {
        $username = Auth::getInstance()->getUser()->getUsername();
        $navContent = HtmlElement::create('div', ['class' => 'flyout-content']);
        $navContent->add(HtmlElement::create('ul', ['class' => 'nav'], [
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

            $ul = HtmlElement::create('ul', ['class' => 'nav']);
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
            $button,
            $this->createLevel2Menu(),
            $this->healthBadge
        ]));
    }
}
