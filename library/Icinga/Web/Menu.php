<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use Icinga\Application\Logger;
use Icinga\Authentication\Auth;
use Icinga\Common\Database;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Widget\Dashboard;
use ipl\Sql\Select;

/**
 * Main menu for Icinga Web 2
 */
class Menu extends Navigation
{
    use Database;

    /**
     * Create the main menu
     */
    public function __construct()
    {
        $this->init();
        $this->load('menu-item');
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

        $this->loadDashboardHomes();
    }

    /**
     * Load user specific and shared dashboard homes from the db and system
     *
     * homes from the navigation and append them as child items to the dashboard menu
     */
    protected function loadDashboardHomes()
    {
        $user = Auth::getInstance()->getUser();
        $homesFromDb = [];

        $dashboardHomes = $this->getDb()->select((new Select())
            ->columns('*')
            ->from('dashboard_home as dh')
            ->where([
                'dh.owner = ?'  => $user->getUsername(),
                'dh.owner = \'icingaweb2\''
            ], 'OR'));

        $priority = 10;
        foreach ($dashboardHomes as $dashboardHome) {
            $this->getItem('dashboard')->addChild($this->createItem($dashboardHome->name, [
                'label'         => t($dashboardHome->name),
                'description'   => $dashboardHome->name,
                'priority'      => $priority,
                'owner'         => $dashboardHome->owner,
                'homeId'        => $dashboardHome->id,
                'disabled'      => (bool) $dashboardHome->disabled
            ]));

            if ($dashboardHome->name !== Dashboard::DEFAULT_HOME && ! $dashboardHome->disabled) {
                $this->getItem('dashboard')->getChildren()->getItem($dashboardHome->name)->setUrl(
                    \ipl\Web\Url::fromPath('dashboard/home', ['home' => $dashboardHome->name])
                );
            }

            $priority += 10;
            $homesFromDb[$dashboardHome->id] = $dashboardHome->name;
        }

        $navigation = new Navigation();
        $homes = $navigation->load('dashboard-home');
        $largestId = $this->getDb()->select((new Select())
            ->columns('MAX(id) AS largestId')
            ->from('dashboard_home'))->fetch();

        /** @var NavigationItem $home */
        foreach ($homes as $home) {
            if (in_array($home->getName(), $homesFromDb, true)) {
                $item = $this->getItem('dashboard')->getChildren()->findItem($home->getName());

                $dashboard = $this->getDb()->select((new Select())
                    ->columns('id')
                    ->from('dashboard')
                    ->where(['home_id = ?' => $item->getAttribute('homeId')])
                    ->limit(1))->fetch();

                if ($dashboard || $item->getAttribute('disabled')) {
                    $item->setChildren($home->getChildren());

                    if ($item->getAttribute('disabled')) {
                        $item->setDefaultUrl(false);
                    }

                    continue;
                } else {
                    // This home has been edited by the user, e.g by deactivating the entire
                    // home, but now it has been reactivated and can be removed from the DB
                    $this->getDb()->delete('dashboard_home', [
                        'id = ?'    => $item->getAttribute('homeId'),
                        'owner = ?' => $user->getUsername()
                    ]);
                }
            }

            // When the item type doesn't match dashboard-home, we do nothing
            if ($home->getAttribute('type') !== 'dashboard-home') {
                continue;
            }

            if (! $home->hasUrl()) {
                $home->setUrl(\ipl\Web\Url::fromPath('dashboard/home', ['home' => $home->getName()]));
            }

            $home->setAttribute('homeId', ++$largestId->largestId);
            $this->getItem('dashboard')->addChild($home);
        }
    }
}
