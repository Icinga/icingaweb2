<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Model\Home;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Navigation\DashboardHomeItem;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Dashboard\Dashboard;
use ipl\Stdlib\Filter;

/**
 * Main menu for Icinga Web 2
 */
class Menu extends Navigation
{
    /**
     * Create the main menu
     */
    public function __construct()
    {
        $this->init();
        $this->initHome();

        $this->load('menu-item');
    }

    /**
     * Setup the main menu
     */
    public function init()
    {
        $this->addItem('dashboard', [
            'label'     => t('Dashboard'),
            'url'       => Dashboard::BASE_ROUTE,
            'icon'      => 'dashboard',
            'priority'  => 10
        ]);
        $this->addItem('system', [
            'cssClass'  => 'system-nav-item',
            'label'     => t('System'),
            'icon'      => 'services',
            'priority'  => 700,
            'renderer'  => [
                'SummaryNavigationItemRenderer',
                'state' => 'critical'
            ],
            'children'  => [
                'about' => [
                    'icon'        => 'info',
                    'description' => t('Open about page'),
                    'label'       => t('About'),
                    'url'         => 'about',
                    'priority'    => 700
                ],
                'health' => [
                    'icon'        => 'eye',
                    'description' => t('Open health overview'),
                    'label'       => t('Health'),
                    'url'         => 'health',
                    'priority'    => 710,
                    'renderer'    => 'HealthNavigationRenderer'
                ],
                'announcements' => [
                    'icon'        => 'megaphone',
                    'description' => t('List announcements'),
                    'label'       => t('Announcements'),
                    'url'         => 'announcements',
                    'priority'    => 720
                ],
                'sessions' => [
                    'icon'        => 'host',
                    'description' => t('List of users who stay logged in'),
                    'label'       => t('User Sessions'),
                    'permission'  => 'application/sessions',
                    'url'         => 'manage-user-devices',
                    'priority'    => 730
                ]
            ]
        ]);
        $this->addItem('configuration', [
            'cssClass'      => 'configuration-nav-item',
            'label'         => t('Configuration'),
            'icon'          => 'wrench',
            'permission'    => 'config/*',
            'priority'      => 800,
            'children'      => [
                'application' => [
                    'icon'        => 'wrench',
                    'description' => t('Open application configuration'),
                    'label'       => t('Application'),
                    'url'         => 'config',
                    'priority'    => 810
                ],
                'authentication' => [
                    'icon'        => 'users',
                    'description' => t('Open access control configuration'),
                    'label'       => t('Access Control'),
                    'permission'  => 'config/access-control/*',
                    'priority'    => 830,
                    'url'         => 'role'
                ],
                'navigation' => [
                    'icon'        => 'sitemap',
                    'description' => t('Open shared navigation configuration'),
                    'label'       => t('Shared Navigation'),
                    'url'         => 'navigation/shared',
                    'permission'  => 'config/navigation',
                    'priority'    => 840,
                ],
                'modules' => [
                    'icon'        => 'cubes',
                    'description' => t('Open module configuration'),
                    'label'       => t('Modules'),
                    'url'         => 'config/modules',
                    'permission'  => 'config/modules',
                    'priority'    => 890
                ]
            ]
        ]);
        $this->addItem('user', [
            'cssClass'  => 'user-nav-item',
            'label'     => Auth::getInstance()->getUser()->getUsername(),
            'icon'      => 'user',
            'priority'  => 900,
            'children'  => [
                'account' => [
                    'icon'        => 'sliders',
                    'description' => t('Open your account preferences'),
                    'label'       => t('My Account'),
                    'priority'    => 100,
                    'url'         => 'account'
                ],
                'logout' => [
                    'icon'        => 'off',
                    'description' => t('Log out'),
                    'label'       => t('Logout'),
                    'priority'    => 200,
                    'attributes'  => ['target' => '_self'],
                    'url'         => 'authentication/logout'
                ]
            ]
        ]);

        if (Logger::writesToFile()) {
            $this->getItem('system')->addChild($this->createItem('application_log', [
                'icon'        => 'doc-text',
                'description' => t('Open Application Log'),
                'label'       => t('Application Log'),
                'url'         => 'list/applicationlog',
                'permission'  => 'application/log',
                'priority'    => 900
            ]));
        }
    }

    public function initHome()
    {
        $user = Dashboard::getUser();
        $dashboardItem = $this->getItem('dashboard');

        $homes = Home::on(Dashboard::getConn());
        $homes->filter(Filter::equal('username', $user->getUsername()));

        try {
            foreach ($homes as $home) {
                $dashboardHome = new DashboardHomeItem($home->name, [
                    'uuid'     => $home->id,
                    'label'    => t($home->label),
                    'priority' => $home->priority,
                    'type'     => $home->type,
                ]);

                $dashboardItem->addChild($dashboardHome);
            }
        } catch (\Exception $_) {
            // Nothing to do
            // Any database issue will be noticed soon enough, so prevent the Menu
            // from being ruined in any case.
        }
    }

    /**
     * Load dashboard homes form the navigation menu
     *
     * @return DashboardHome[]
     */
    public function loadHomes()
    {
        $homes = [];
        foreach ($this->getItem('dashboard')->getChildren() as $child) {
            if (! $child instanceof DashboardHomeItem) {
                continue;
            }

            $homes[$child->getName()] = DashboardHome::create($child);
        }

        return $homes;
    }
}
