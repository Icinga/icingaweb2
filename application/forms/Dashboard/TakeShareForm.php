<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Config;
use Icinga\DBUser;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;

class TakeShareForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    /** @var Pane[] */
    private $panes;

    public function __construct(Dashboard $dashboard, array $panes = [])
    {
        $this->dashboard = $dashboard;
        $this->panes = $panes;
    }

    protected function assembleUnUsedSharedPane()
    {
        if (! Url::fromRequest()->getParam('home')) {
            $this->addHtml(HtmlElement::create('h1', null, t('Share this dashboard with others')));

            $sharedHomes = $this->dashboard->getHomeKeyNameArray(true, true);
            $this->addElement('select', 'target_location', [
                'class'         => 'autosubmit',
                'required'      => true,
                'multiOptions'  => ['menu-item' => t('Menu Entry'), 'dashboard-item' => t('Dashboard')],
                'label'         => t('Target Location'),
                'description'   => t('The target location you want to locate this dashboard at')
            ]);

            $this->addElement('checkbox', 'create_new_home', [
                'class'         => 'autosubmit',
                'disabled'      => empty($sharedHomes) ?: null,
                'required'      => false,
                'label'         => t('New Dashboard Home'),
                'description'   => t('Check this box if you want to add the dashboard to a new dashboard home'),
            ]);

            if (empty($sharedHomes) || $this->getPopulatedValue('create_new_home') === 'y') {
                $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);
                $this->addElement('text', 'home', [
                    'required'      => true,
                    'label'         => t('Dashboard Home'),
                    'description'   => t('Enter a title for the new dashboard home')
                ]);
            } else {
                $this->addElement('select', 'home', [
                    'class'         => 'autosubmit',
                    'required'      => true,
                    'label'         => t('Dashboard Home'),
                    'multiOptions'  => $sharedHomes,
                    'value'         => reset($sharedHomes),
                    'description'   => t('Select a home you want to add the pane to')
                ]);
            }
        }
    }

    protected function assemble()
    {
        $this->assembleUnUsedSharedPane();
        $pane = null;

        if (Url::fromRequest()->hasParam('home')) {
            $this->addHtml(HtmlElement::create('h1', null, t('Add new members')));

            $home = $this->dashboard->getHome(Url::fromRequest()->getParam('home'));
            $pane = $home->getPane(Url::fromRequest()->getParam('pane'));
        }

        $this->addElement('textarea', 'users', [
            'required'      => false,
            'label'         => t('Users'),
            'description'   => t('Comma separated list of usernames to share this dashboard with')
        ]);

        $this->addElement('checkbox', 'share_with_groups', [
            'class'         => 'autosubmit',
            'required'      => false,
            'label'         => t('Share with Groups'),
            'description'   => t('Check this box if you want to share this dashboard other groups')
        ]);

        if ($this->getPopulatedValue('share_with_groups') === 'y') {
            $this->addElement('textarea', 'groups', [
                'required'      => false,
                'label'         => t('Groups'),
                'description'   => t('Comma separated list of group names to share this dashboard with')
            ]);
        }

        $this->addElement('checkbox', 'share_with_roles', [
            'class'         => 'autosubmit',
            'required'      => false,
            'label'         => t('Share with Roles'),
            'description'   => t('Check this box if you want to share this dashboard other roles')
        ]);

        if ($this->getPopulatedValue('share_with_roles') === 'y') {
            $this->addElement('textarea', 'roles', [
                'required'      => false,
                'label'         => t('Roles'),
                'description'   => t('Comma separated list of role names to share this dashboard with')
            ]);
        }

        $user = $this->dashboard->getAuthUser();
        $disable = $pane && $pane->getOwner() !== $user->getUsername() && ! $user->hasWriteAccess($pane);
        $this->addElement('submit', 'take_share', [
            'label'     => t('Take Share'),
            'disabled'  => $disable ?: null,
            'title'     => $disable ? t('You do not have the appropriate permission to share this with others') : null
        ]);
    }

    protected function onSuccess()
    {
        $usernames = [];
        if (($users = $this->getValue('users'))) {
            $usernames = array_map('trim', explode(',', $users));
        }

        $groups = [];
        if (($newGroups = $this->getValue('groups'))) {
            $groups = array_map('trim', explode(',', $newGroups));
        }

        $roles = [];
        if (($newRoles = $this->getValue('roles'))) {
            $roles = array_map('trim', explode(',', $newRoles));
        }

        $users = [];
        foreach ($usernames as $username) {
            $users[$username] = (new DBUser($username))->setRemoved(false);
        }

        $requestUrl = Url::fromRequest();
        $dashboard = $this->dashboard;

        $orgHome = null;
        $pane = null;
        if (($home = $requestUrl->getParam('home'))) {
            $orgHome = $dashboard->getHome($home);
        }

        if (($paneParam = $requestUrl->getParam('pane'))) {
            if (! array_key_exists($paneParam, $this->panes)) {
                throw new HttpNotFoundException('Shared dashboard not found');
            }

            $pane = $this->panes[$paneParam];
            $pane
                ->setDashlets([])
                ->setType(Dashboard::SHARED)
                ->setAdditional('with_users', $users)
                ->setAdditional('with_groups', $groups)
                ->setAdditional('with_roles', $roles);
        }

        $newHome = new DashboardHome($this->getValue('home', ! $orgHome ?: $orgHome->getName()));
        $newHome
            ->setType(Dashboard::SHARED)
            ->setAuthUser($dashboard->getAuthUser())
            ->setAdditional('with_users', $users)
            ->setAdditional('with_groups', $groups)
            ->setAdditional('with_roles', $roles);

        if (
            $orgHome
            && $this->getPopulatedValue('create_new_home') !== 'y'
            && $newHome->getName() === $orgHome->getName()
        ) {
            $newHome->setLabel($orgHome->getLabel());
        }

        if ($this->getPopulatedValue('target_location', 'dashboard-item') === 'dashboard-item') {
            $newHome->setPanes($pane);
            $dashboard->manageHome($newHome, $orgHome);

            $message = sprintf(
                t('Shared dashboard with %s other user(s), %s group(s) and %s role(s) successfully'),
                count($users),
                count($groups),
                count($roles)
            );
            if (! $requestUrl->hasParam('pane')) {
                $message = sprintf(
                    t('Shared home with %s other user(s), %s group(s) and %s role(s) successfully'),
                    count($users),
                    count($groups),
                    count($roles)
                );
            }

            Notification::success($message);
        } else {
            $config = Config::navigation('menu-item');
            if ($dashboard->getAuthUser()->can('user/share/navigation')) {
                $config = Config::navigation('menu-item');
            }

            $user = $dashboard->getAuthUser();
            if ($config->hasSection($newHome->getName())) {
                $section = $config->getSection($newHome->getName());
                $configUsers = explode(',', $section->get('users'));
                $configGroups = explode(',', $section->get('groups'));

                $configUsers = array_unique(array_merge($configUsers, $usernames));
                $configGroups = array_unique(array_merge($configGroups, $groups));

                $section->users = implode(',', $configUsers);
                $section->groups = implode(',', $configGroups);
                $section->panes .= ',' . $pane->getName();
            } else {
                $params = [
                    'home'  => $newHome->getName(),
                    'pane'  => $pane->getName()
                ];
                $data = [
                    'users'     => implode(',', $usernames),
                    'type'      => 'menu-item',
                    'target'    => '_main',
                    'home'      => $newHome->getName(),
                    'panes'     => $pane->getName(),
                    'url'       => Url::fromPath(Dashboard::BASE_ROUTE . '/home')->setParams($params)->getRelativeUrl(),
                    'owner'     => $user->getUsername()
                ];

                $config->setSection($newHome->getName(), $data);
            }

            $config->saveIni();

            Notification::success(t('Navigation item successfully created'));
        }
    }
}
