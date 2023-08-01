<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Common\HealthBadgeTrait;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class ConfigMenu extends BaseHtmlElement
{
    use HealthBadgeTrait;

    protected $tag = 'ul';

    protected $defaultAttributes = ['class' => 'nav'];

    protected $children;

    protected $selected;

    protected $state;

    protected $healthBadge;

    protected $flyoutID = 'config-menu-flyout';

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
                        'url' => 'navigation/shared'
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

        if (Logger::writesToFile()) {
            $this->children['system']['items']['application_log'] = [
                'label'       => t('Application Log'),
                'url'         => 'list/applicationlog',
                'permission'  => 'application/log'
            ];
        }
    }

    protected function assembleUserMenuItem(BaseHtmlElement $userMenuItem)
    {
        $username = Auth::getInstance()->getUser()->getUsername();

        $userMenuItem->add(
            new HtmlElement(
                'a',
                Attributes::create(['href' => Url::fromPath('account')]),
                new HtmlElement(
                    'i',
                    Attributes::create(['class' => 'user-ball']),
                    Text::create($username[0])
                ),
                Text::create($username)
            )
        );

        if (Icinga::app()->getRequest()->getUrl()->matches('account')) {
            $userMenuItem->addAttributes(['class' => 'selected active']);
        }
    }

    protected function assembleCogMenuItem($cogMenuItem)
    {
        $cogMenuItem->add([
            HtmlElement::create(
                'button',
                null,
                [
                    new Icon('cog'),
                    $this->createHealthBadge(),
                ]
            ),
            $this->createLevel2Menu()
        ]);
    }

    protected function assembleLevel2Nav(BaseHtmlElement $level2Nav)
    {
        $navContent = HtmlElement::create('div', ['class' => 'flyout-content']);
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

            $ul = HtmlElement::create('ul', ['class' => ['nav', 'flyout-menu']]);
            foreach ($c['items'] as $key => $item) {
                $ul->add($this->createLevel2MenuItem($item, $key));
            }

            $navContent->add($ul);
        }

        $level2Nav->add($navContent);
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
            Attributes::create(['class' => 'nav-level-1 flyout', 'id' => $this->flyoutID ])
        );

        $this->assembleLevel2Nav($level2Nav);

        return $level2Nav;
    }

    protected function createLevel2MenuItem($item, $key)
    {
        if (isset($item['permission']) && ! Auth::getInstance()->hasPermission($item['permission'])) {
            return null;
        }

        $healthBadge = null;
        $class = [];
        if ($key === 'health') {
            $class = 'badge-nav-item';
            $healthBadge = $this->healthBadge;
        }

        $icon = null;
        if (isset($item['icon'])) {
            $icon = new Icon($item['icon']);
            $class[] = 'has-icon';
        }

        $li = HtmlElement::create(
            'li',
            isset($item['atts']) ? $item['atts'] : [],
            [
                HtmlElement::create(
                    'a',
                    Attributes::create(['href' => Url::fromPath($item['url'])]),
                    [
                        $icon,
                        $item['label'],
                        isset($healthBadge) ? $healthBadge : ''
                    ]
                ),
            ]
        );
        $li->addAttributes(['class' => $class]);

        if ($this->isSelectedItem($item)) {
            $li->addAttributes(['class' => 'selected']);
        }

        return $li;
    }

    protected function createUserMenuItem()
    {
        $userMenuItem = HtmlElement::create('li', ['class' => 'user-nav-item']);

        $this->assembleUserMenuItem($userMenuItem);

        return $userMenuItem;
    }

    protected function createCogMenuItem()
    {
        $cogMenuItem = HtmlElement::create('li', [
            'class' => 'config-nav-item',
            'data-flyout-target' => $this->flyoutID
        ]);

        $this->assembleCogMenuItem($cogMenuItem);

        return $cogMenuItem;
    }

    protected function assemble()
    {
        $this->healthBadge = $this->createHealthBadge();

        $this->add([
            $this->createUserMenuItem(),
            $this->createCogMenuItem()
        ]);
    }
}
