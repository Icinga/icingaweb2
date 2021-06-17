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
            'url'       => 'dashboard',
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
     * Load user specific and shared dashboard homes from the db and system
     *
     * homes from the navigation and append them as child items to the dashboard menu
     */
    protected function loadDashboardHomes()
    {
        $user = Auth::getInstance()->getUser();
        $dashboardItem = $this->getItem('dashboard');
        $homesFromDb = [];

        $dashboardHomes = DashboardHome::getConn()->select((new Select())
            ->columns('*')
            ->from(DashboardHome::TABLE . ' dh')
            ->where([
                'dh.owner = ?' => $user->getUsername(),
                sprintf("dh.owner = '%s'", DashboardHome::DEFAULT_IW2_USER)
            ], 'OR'));

        $priority = 10;
        foreach ($dashboardHomes as $dashboardHome) {
            if (array_key_exists($dashboardHome->name, $homesFromDb)
                && $dashboardHome->owner === DashboardHome::DEFAULT_IW2_USER) {
                continue;
            }

            $home = new DashboardHome($dashboardHome->name, [
                'label'         => t($dashboardHome->label),
                'priority'      => $priority,
                'user'          => $user,
                'owner'         => $dashboardHome->owner,
                'identifier'    => $dashboardHome->id,
                'disabled'      => (bool) $dashboardHome->disabled,
            ]);

            $dashboardItem->addChild($home);

            $priority += 10;
            $homesFromDb[$home->getName()] = $home;
        }

        $navigation = new Navigation();
        $homes = $navigation->load('dashboard-home');
        $highestId = DashboardHome::getConn()->select((new Select())
            ->columns('MAX(id) AS highestId')
            ->from(DashboardHome::TABLE))->fetch();

        /** @var DashboardHome $home */
        foreach ($homes as $home) {
            // When the item type doesn't match dashboard-home, we do nothing
            if ($home->getAttribute('type') !== 'dashboard-home') {
                continue;
            }

            if (array_key_exists($home->getName(), $homesFromDb)) {
                $homeItem = $homesFromDb[$home->getName()];

                $dashboard = DashboardHome::getConn()->select((new Select())
                    ->columns('d.id')
                    ->from('dashboard d')
                    ->join(DashboardHome::TABLE . ' dh', 'dh.id = d.home_id')
                    ->where(['home_id = ?' => $homeItem->getIdentifier()])
                    ->where([
                        'd.owner = ?'   => $user->getUsername(),
                        'dh.owner = ?'  => $user->getUsername()
                    ], 'OR')
                    ->limit(1))->fetch();

                if ($dashboard || $homeItem->getDisabled()) {
                    $homeItem->setPanes($home->getChildren());

                    continue;
                } else {
                    $dashboard = DashboardHome::getConn()->select((new Select())
                        ->columns('dashboard_id')
                        ->from('dashboard_override')
                        ->where(['home_id = ?' => $homeItem->getIdentifier()])
                        ->limit(1))->fetch();

                    if ($dashboard) {
                        $homeItem->setPanes($home->getChildren());

                        continue;
                    }

                    // This home has been edited by the user, e.g by disabling the entire
                    // home, but now it has been re-enabled and can be removed from the DB
                    DashboardHome::getConn()->delete(DashboardHome::TABLE, [
                        'id = ?'    => $homeItem->getIdentifier(),
                        'owner = ?' => $homeItem->getOwner()
                    ]);
                }
            }

            $home
                ->setPanes($home->getChildren())
                ->setChildren([])
                ->setIdentifier(++$highestId->highestId);

            $dashboardItem->addChild($home);
        }
    }
}
