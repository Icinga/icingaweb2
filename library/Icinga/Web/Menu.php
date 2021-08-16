<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Navigation\Navigation;
use ipl\Sql\Select;

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
        $this->load('menu-item');
        $this->loadDashboardHomes();
    }

    /**
     * Setup the main menu
     */
    public function init()
    {
        $this->addItem('dashboard', [
            'label'     => t('Dashboard'),
            'url'       => DashboardHome::BASE_PATH,
            'icon'      => 'dashboard',
            'priority'  => 10
        ]);
        $this->addItem('system', [
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

    /**
     * Load user specific and shared dashboard homes from the db and
     *
     * append them as child items to the "dashboard" navigation menu
     */
    protected function loadDashboardHomes()
    {
        $user = Auth::getInstance()->getUser();
        $dashboardItem = $this->getItem('dashboard');

        $dashboardHomes = DashboardHome::getConn()->select((new Select())
            ->columns('*')
            ->from(DashboardHome::TABLE . ' dh')
            ->where(['dh.owner = ?' => $user->getUsername()]));

        $priority = 10;
        foreach ($dashboardHomes as $dashboardHome) {
            $home = new DashboardHome($dashboardHome->name, [
                'label'         => t($dashboardHome->label),
                'priority'      => $priority,
                'user'          => $user,
                'owner'         => $dashboardHome->owner,
                'id'            => $dashboardHome->id,
            ]);

            $dashboardItem->addChild($home);
            $priority += 10;
        }
    }
}
