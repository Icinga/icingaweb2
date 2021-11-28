<?php

/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Authentication\Auth;
use Icinga\DBUser;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class HomePaneForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    /**
     * Icinga Web 2's authentication manager
     *
     * @var Auth
     */
    private $auth;

    /**
     * @var array
     */
    private $dashlets = [];

    /**
     * HomePaneForm constructor.
     *
     * @param Dashboard $dashboard
     * @param Auth      $auth
     */
    public function __construct(Dashboard $dashboard, Auth $auth)
    {
        $this->dashboard = $dashboard;
        $this->auth = $auth;
    }

    protected function setUpProvidedDashlets()
    {
        $dashlets = Url::fromRequest()->getParams()->getRequired('dashlets');
        $dashlets = rawurldecode($dashlets);

        $dashlets = explode(',', $dashlets);
        $home = $this->dashboard->getHome(DashboardHome::AVAILABLE_DASHLETS);
        $this->dashboard->activateHome($home);

        $providedDashlets = $home->loadProvidedDashlets();
        foreach ($dashlets as $dashlet) {
            $dashlet = explode(':', $dashlet);
            /** @var Dashlet[] $dashletPart */
            foreach ($providedDashlets as $module => $dashletPart) {
                if ($dashlet[0] !== $module || ! array_key_exists($dashlet[1], $dashletPart)) {
                    continue;
                }

                $this->dashlets[$module][$dashlet[1]] = $dashletPart[$dashlet[1]];
            }
        }
    }

    /**
     * Check the auth user has the required dashboard permission
     *
     * @return bool
     */
    protected function hasPerm()
    {
        return $this->auth->hasPermission('application/manage/dashboards');
    }

    /**
     * Check whether this pane|home being load is a system one
     *
     * @return bool
     */
    protected function isSystem()
    {
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() !== Dashboard::BASE_ROUTE . '/new-dashboard') {
            $activeHome = $this->dashboard->getActiveHome();

            return $requestUrl->hasParam('param')
                ? $activeHome->getPane($requestUrl->getParam('pane'))->getType() === Dashboard::SYSTEM
                : $activeHome->getType() === Dashboard::SYSTEM;
        }

        return false;
    }

    /**
     * Check whether this pane|home being loaded is a public one
     *
     * @return bool
     */
    protected function isPublic()
    {
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() !== Dashboard::BASE_ROUTE . '/new-dashboard') {
            $activeHome = $this->dashboard->getActiveHome();

            return $requestUrl->hasParam('param')
                ? $activeHome->getPane($requestUrl->getParam('pane'))->getType() === Dashboard::PUBLIC_DS
                : $activeHome->getType() === Dashboard::PUBLIC_DS;
        }

        return false;
    }

    /**
     * Check whether this pane|home being loaded is a shared one
     *
     * @return bool
     */
    protected function isShared()
    {
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() !== Dashboard::BASE_ROUTE . '/new-dashboard') {
            $activeHome = $this->dashboard->getActiveHome();

            return $requestUrl->hasParam('param')
                ? $activeHome->getPane($requestUrl->getParam('pane'))->getType() === Dashboard::SHARED
                : $activeHome->getType() === Dashboard::SHARED;
        }

        return false;
    }

    protected function renderRemoveForAllUsers()
    {
        $dashboard = $this->dashboard;
        $home = $dashboard->getActiveHome();
        $pane = null;
        if (($paneParam = Url::fromRequest()->getParam('pane'))) {
            $pane = $home->getPane($paneParam);
        }

        if (
            $this->hasPerm()
            && $this->isPublic()
            || $this->isShared()
            && $this->dashboard->getAuthUser()->hasWriteAccess($pane)
        ) {
            $purgeCheckbox = $this->createElement('checkbox', 'purge_from_db', [
                'label'         => t('Purge from DB'),
                'value'         => 'n',
                'description'   => sprintf(
                    t('Check this box if you want to completely remove this %s from DB.'),
                    $pane ? 'pane' : 'home'
                )
            ]);

            $this->registerElement($purgeCheckbox);
            $this->decorate($purgeCheckbox);
            $this->prependHtml($purgeCheckbox);
        }
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('btn_remove')
                || $this->getPopulatedValue('btn_update')
                || $this->getPopulatedValue('btn_new_dashboard')
            );
    }

    protected function renderPaneOptions($home = null)
    {
        $panes = [];
        $home = $this->getPopulatedValue('home', $home);
        if ($this->dashboard->hasHome($home)) {
            $home = $this->dashboard->getHome($home);
            if ($home->getType() === Dashboard::SHARED) {
                $home->setActive();
                $home->loadUserDashboards();
                $panes = $home->getPaneKeyTitleArray(true);
            }
        }

        $shouldDisable = empty($panes) || $this->getPopulatedValue('create_new_home') === 'y';
        $this->addElement('checkbox', 'create_new_pane', [
            'class'         => 'autosubmit',
            'disabled'      => $shouldDisable ?: null,
            'required'      => false,
            'label'         => t('New Dashboard'),
            'description'   => t('Check this box if you want to add the dashlet to a new dashboard'),
        ]);

        if ($shouldDisable || $this->getPopulatedValue('create_new_pane') === 'y') {
            $this->getElement('create_new_pane')->setAttribute('checked', 'checked');
            $this->addElement('text', 'pane', [
                'required'      => true,
                'label'         => t('Dashboard Name'),
                'description'   => t('Enter a title for the new dashboard.'),
            ]);
        } else {
            $this->addElement('select', 'pane', [
                'required'      => true,
                'label'         => t('Dashboard Name'),
                'multiOptions'  => $panes,
                'value'         => reset($panes),
                'description'   => t('Enter a title for the new dashboard.'),
            ]);
        }
    }

    protected function renderSharedDashboard()
    {
        $requestRoute = Url::fromRequest()->getPath();
        if ($requestRoute === Dashboard::BASE_ROUTE . '/new-dashboard') {
            $this->setUpProvidedDashlets();
            $result = 0;
            foreach ($this->dashlets as $_ => $dashlets) {
                $result += count($dashlets);
            }

            $this->addHtml(HtmlElement::create('div', ['class' => 'control-group'], HtmlElement::create('p', [
                'class' => 'create-new-dashboard-label'
            ], [
                'You are about to create a Dashboard from ',
                HtmlElement::create('span', ['class' => 'count-pinned-items'], $result),
                ' pinned Dashlets.'
            ])));
        }
    }

    protected function assemble()
    {
        $removeHome = Dashboard::BASE_ROUTE . '/remove-home';
        $renamePane = Dashboard::BASE_ROUTE . '/rename-pane';
        $removePane = Dashboard::BASE_ROUTE . '/remove-pane';
        $newDashboard = Dashboard::BASE_ROUTE . '/new-dashboard';

        $requestUrl = Url::fromRequest();
        $requestPath = $requestUrl->getPath();

        $activeHome = $this->dashboard->getActiveHome();
        $populated = $this->getPopulatedValue('home', $activeHome->getName());

        if ($requestUrl->getPath() !== Dashboard::BASE_ROUTE . '/new-dashboard') {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray();
        } else {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray(true, true);
        }

        $dbTarget = '_main';
        $btnUpdateLabel = t('Update Home');
        $btnRemoveLabel = t('Remove Home');
        $titleDesc = t('Edit the current home title.');
        $formaction = (string) $requestUrl->setPath($removeHome);

        $this->addElement('hidden', 'org_name', ['required' => false]);
        $this->renderSharedDashboard();

        if (
            $requestPath === $renamePane
            || $newDashboard === $requestPath
            || $requestPath === Dashboard::BASE_ROUTE . '/rename-home'
        ) {
            if ($requestPath === Dashboard::BASE_ROUTE . '/rename-home') {
                if ($this->dashboard->getActiveHome()->isDisabled()) {
                    $this->addElement('checkbox', 'enable_home', [
                        'value'         => 'n',
                        'label'         => t('Enable Home'),
                        'description'   => t('Check this box if you want to enable this home.')
                    ]);
                }
            }

            if ($renamePane === $requestPath || $newDashboard === $requestPath) {
                if ($renamePane === $requestPath) {
                    $dbTarget = '_self';
                    $btnUpdateLabel = t('Update Pane');
                    $btnRemoveLabel = t('Remove Pane');
                    $titleDesc = t('Edit the current pane title.');
                    $formaction = (string) $requestUrl->setPath($removePane);

                    $this->addElement('hidden', 'org_title', ['required' => false]);
                }

                $this->addElement('checkbox', 'create_new_home', [
                    'class'         => 'autosubmit',
                    'disabled'      => empty($dashboardHomes) ?: null,
                    'required'      => false,
                    'label'         => t('New Dashboard Home'),
                    'description'   => t('Check this box if you want to move the pane to a new dashboard home.'),
                ]);

                if ($requestPath === $newDashboard) {
                    $dashboardHomes = $this->dashboard->getHomeKeyNameArray(true, true);
                    $populated = reset($dashboardHomes);
                }

                if (empty($dashboardHomes) || $this->getPopulatedValue('create_new_home') === 'y') {
                    $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);

                    $this->addElement('text', 'home', [
                        'required'      => true,
                        'label'         => t('Dashboard Home'),
                        'description'   => t('Enter a title for the new dashboard home.'),
                    ]);
                } else {
                    $this->addElement('select', 'home', [
                        'class'         => 'autosubmit',
                        'required'      => true,
                        'label'         => $newDashboard === $requestPath ? t('Dashboard Home') : t('Move to home'),
                        'multiOptions'  => $dashboardHomes,
                        'value'         => $populated,
                        'description'   => t('Select a dashboard home you want to move the dashboard to'),
                    ]);
                }

                if ($renamePane === $requestPath) {
                    $pane = $this->dashboard->getActiveHome()->getPane($requestUrl->getParam('pane'));
                    if ($pane->isDisabled()) {
                        $this->addElement('checkbox', 'enable_pane', [
                            'label'         => t('Enable Pane'),
                            'value'         => 'n',
                            'description'   => t('Check this box if you want to enable this pane.')
                        ]);
                    }
                }
            }

            if ($newDashboard !== $requestPath) {
                $this->addElement('text', 'title', [
                    'required'      => true,
                    'label'         => t('Title'),
                    'description'   => $titleDesc
                ]);
            } else {
                $this->renderPaneOptions(array_search($populated, $dashboardHomes));
                if ($this->auth->hasPermission('application/share/dashboards')) {
                    $users = array_keys($this->dashboard->getUsers());
                    if (! empty($users)) {
                        $this->addElement('select', 'share_with[]', [
                            'required'      => false,
                            'multiple'      => true,
                            'multiOptions'  => array_combine($users, $users),
                            'label'         => t('Share with'),
                            'description'   => t(
                                'Enter a username, groups or roles you want to share with.'
                            )
                        ]);
                    }

                    $this->addElement('textarea', 'share_with_users', [
                        'required'      => false,
                        'label'         => t('Share with'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to share with.'
                        )
                    ]);

                    if (! empty($users)) {
                        $this->addElement('select', 'write_access[]', [
                            'required'      => false,
                            'multiple'      => true,
                            'multiOptions'  => array_combine($users, $users),
                            'label'         => t('Write Permissions'),
                            'description'   => t(
                                'Enter a username, groups or roles you want to grant write access to.'
                            )
                        ]);
                    }

                    $this->addElement('textarea', 'write_access_users', [
                        'required'      => false,
                        'label'         => t('Write Permissions'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to grant write access to.'
                        )
                    ]);
                }
            }
        }

        if ($removePane === $requestPath || $requestPath === Dashboard::BASE_ROUTE . '/remove-home') {
            $message = sprintf(t('Please confirm removal of dashboard home "%s"'), $activeHome->getName());

            if ($requestPath === $removePane) {
                $btnRemoveLabel = t('Remove Pane');
                $formaction = (string) $requestUrl->setPath($removePane);
                $message = sprintf(t('Please confirm removal of dashboard "%s"'), $requestUrl->getParam('pane'));
            }

            $this->addHtml(new HtmlElement('h1', null, Text::create($message)));
        }

        if ($newDashboard !== $requestPath) {
            $controlGroup = HtmlElement::create('div', ['class' => 'control-group form-controls']);
            $shouldDisable = ! $this->hasPerm() && ($this->isSystem() || $this->isPublic() || $this->isShared());
            if (
                $requestUrl->hasParam('pane')
                && ! $activeHome->getPane($requestUrl->getParam('pane'))->isDisabled()
                || (
                    ! $requestUrl->hasParam('pane')
                    && ! $activeHome->isDisabled()
                )
            ) {
                $controlGroup->addHtml(HtmlElement::create('input', [
                    'class'             => 'btn-primary',
                    'type'              => 'submit',
                    'name'              => 'btn_remove',
                    'data-base-target'  => $dbTarget,
                    'value'             => $btnRemoveLabel,
                    'formaction'        => $formaction,
                    'disabled'          => $shouldDisable ?: null,
                    'title'             => $shouldDisable
                        ? sprintf(
                            t('You have not the required permission to %s %s'),
                            $this->isPublic()
                                ? 'remove public' : ($this->isShared() ? 'remove shared' : 'disable system'),
                            ! $requestUrl->hasParam('pane') ? 'home' : 'dashboard'
                        )
                        : null
                ]));
            }

            if ($removeHome !== $requestPath || $removePane !== $requestPath) {
                $controlGroup->addHtml(HtmlElement::create('input', [
                    'class'     => 'btn-primary',
                    'type'      => 'submit',
                    'name'      => 'btn_update',
                    'value'     => $btnUpdateLabel,
                    'disabled'  => $shouldDisable ?: null,
                    'title'     => $shouldDisable
                        ? sprintf(
                            t('You have not the required permission to edit %s %s'),
                            $this->isPublic() ? 'public' : ($this->isShared() ? 'shared' :'system'),
                            ! $requestUrl->hasParam('pane') ? 'home' : 'dashboard'
                        )
                        : null
                ]));
            }

            $this->addHtml($controlGroup);
        } else {
            $this->addElement('submit', 'btn_new_dashboard', ['label' => t('Add To Dashboard')]);
        }
    }

    public function validate()
    {
        parent::validate();
        if (! $this->isValid) {
            return $this;
        }

        if ($this->getPopulatedValue('btn_remove')) {
            if ($this->getPopulatedValue('purge_from_db') !== null) {
                return $this;
            }

            $this->renderRemoveForAllUsers();

            $this->isValid = false;
        }

        return $this;
    }

    protected function onSuccess()
    {
        $dashboard = $this->dashboard;
        $requestUrl = Url::fromRequest();

        $orgHome = null;
        if ($this->getPopulatedValue('btn_new_dashboard') === null) {
            $orgHome = $dashboard->getHome($requestUrl->getParam('home'));
        }

        if (
            $this->getPopulatedValue('btn_new_dashboard')
            || $requestUrl->getPath() === Dashboard::BASE_ROUTE . '/rename-pane'
            || $requestUrl->getPath() === Dashboard::BASE_ROUTE . '/remove-pane'
            || $requestUrl->getPath() === Dashboard::BASE_ROUTE . '/new-pane'
        ) {
            if ($this->getPopulatedValue('btn_update')) {
                $orgPane = $orgHome->getPane($this->getValue('org_name'));
                $newHome = new DashboardHome($this->getPopulatedValue('home', $orgHome->getName()));
                $newHome
                    ->setType(Dashboard::PRIVATE_DS)
                    ->setAuthUser($orgHome->getAuthUser());

                if (
                    $this->getPopulatedValue('create_new_home') !== 'y'
                    && $dashboard->hasHome($newHome->getName())
                ) {
                    $newHome->setLabel($dashboard->getHome($newHome->getName())->getLabel());
                }

                if (! $orgPane->isUserWidget() && $orgHome->getName() !== $newHome->getName()) {
                    Notification::info(sprintf(
                        t('It is not allowed to move system dashboard: "%s"'),
                        $orgPane->getTitle()
                    ));

                    return;
                }

                if ($orgPane->isOverridingWidget() && $orgHome->getName() !== $newHome->getName()) {
                    Notification::info(sprintf(
                        t('Pane "%s" can\'t be moved, as it overwrites a system pane'),
                        $orgPane->getTitle()
                    ));

                    return;
                }

                $newPane = clone $orgPane;
                $newPane->setTitle($this->getValue('title'));

                if ($this->getPopulatedValue('enable_pane') === 'y') {
                    $newPane->setDisabled(false);
                }

                if ($dashboard->hasHome($newHome->getName())) {
                    $copyHome = $orgHome->getActive() ? $orgHome : $dashboard->getActiveHome();
                    if (
                        $newHome->getName() !== $orgHome->getName()
                        || $dashboard->getActiveHome()->getName() !== $newHome->getName()
                    ) {
                        $copyHome = (clone $dashboard->getHome($newHome->getName()))->setPanes([]);
                        $copyHome->setActive(true);
                        $copyHome->loadUserDashboards();
                    }

                    if ($copyHome->hasPane($newPane->getName()) && $copyHome->getName() !== $orgHome->getName()) {
                        Notification::info(
                            sprintf(t('There is already a dashboard "%s" within this home.'), $newPane->getTitle())
                        );

                        return;
                    }

                    if ($copyHome->getType() !== Dashboard::SHARED && $orgPane->getType() === Dashboard::SHARED) {
                        Notification::info(sprintf(
                            t('You can not move shared pane "%s" to a "%s" home'),
                            $newPane->getTitle(),
                            $copyHome->getType()
                        ));

                        return;
                    }

                    $newHome->setType($copyHome->getType());
                }

                $newHome->setPanes($newPane);
                $dashboard->manageHome($newHome, $orgHome);

                $message = sprintf(
                    t('Pane "%s" successfully renamed to "%s".'),
                    $orgPane->getTitle(),
                    $newPane->getTitle()
                );

                if (! $newPane->isDisabled() && $orgPane->isDisabled()) {
                    $message = sprintf(t('Pane "%s" successfully enabled.'), $orgPane->getTitle());
                } elseif ($orgHome->getName() !== $newHome->getName()) {
                    $message = sprintf(
                        t('Pane "%s" successfully moved from "%s" to "%s"'),
                        $newPane->getTitle(),
                        $orgHome->getLabel(),
                        $newHome->getLabel()
                    );
                }

                Notification::success($message);
            } elseif ($this->getPopulatedValue('btn_new_dashboard')) {
                $shareWith = $this->getPopulatedValue('share_with') ?: [];
                if (($users = $this->getValue('share_with_users'))) {
                    $shareWith = array_merge($shareWith, array_map('trim', explode(',', $users)));
                }

                $writeAccessUsers = $this->getPopulatedValue('write_access') ?: [];
                if (($users = $this->getValue('write_access_users'))) {
                    $writeAccessUsers = array_merge($writeAccessUsers, array_map('trim', explode(',', $users)));
                }

                $users = [];
                foreach (array_merge($shareWith, $writeAccessUsers) as $user) {
                    $dbUser = new DBUser($user);
                    if (in_array($user, $writeAccessUsers)) {
                        $dbUser->setWriteAccess(true);
                    }

                    $users[$user] = $dbUser;
                }

                $dashboard->getAuthUser()->setWriteAccess(true);
                $newHome = new DashboardHome($this->getPopulatedValue('home'));
                $newHome
                    ->setType(Dashboard::SHARED)
                    ->setAuthUser($dashboard->getAuthUser())
                    ->setAdditional('with_users', $users);

                $newPane = new Pane($this->getPopulatedValue('pane'));
                $newPane
                    ->setUserWidget()
                    ->setType(Dashboard::SHARED)
                    ->setAdditional('with_users', $users);

                if ($dashboard->hasHome($newHome->getName())) {
                    $orgHome = $dashboard->getHome($newHome->getName());
                    if ($orgHome->getType() !== Dashboard::SHARED) {
                        Notification::info(sprintf(
                            t('It is not allowed to create shared dashboard in a "%s" home'),
                            $orgHome->getType()
                        ));
                        return;
                    }

                    $newHome->setLabel($orgHome->getLabel());
                    if ($orgHome->hasPane($newPane->getName())) {
                        $orgPane = $orgHome->getPane($newPane->getName());
                        if ($orgPane->getType() !== Dashboard::SHARED) {
                            Notification::info(sprintf(
                                t('It is not allowed to create shared dashboard in a "%s" home'),
                                $orgHome->getType()
                            ));
                            return;
                        }
                    }
                }

                $dashlets = $this->dashlets;
                $this->dashlets = [];
                foreach ($dashlets as $module => $dashletPart) {
                    foreach ($dashletPart as $dashlet) {
                        $dashletId = DashboardHome::getSHA1($module . $dashlet->getName());
                        $dashlet->setDashletId($dashletId);
                        $dashlet
                            ->setUserWidget()
                            ->setType(Dashboard::SHARED)
                            ->setAdditional('with_users', $users);

                        $this->dashlets[] = $dashlet;
                    }
                }

                $newPane->setDashlets($this->dashlets);
                $newHome->addPane($newPane);
                $dashboard->manageHome($newHome, $orgHome);
            } else {
                $orgPane = $orgHome->getPane($this->getValue('org_name'));
                if ($this->getPopulatedValue('purge_from_db') === 'y') {
                    $orgPane->setAdditional('unshared_users', new DBUser('*'));
                }

                $orgHome->removePane($orgPane);
                $message = t('Pane has been successfully removed') . ': ' . $orgPane->getTitle();
                if (! $orgPane->isUserWidget()) {
                    $message = t('Pane has been successfully disabled') . ': ' . $orgPane->getTitle();
                }

                Notification::success($message);
            }
        } else { // Dashboard homes
            if ($this->getPopulatedValue('btn_update')) {
                if ($orgHome->getType() === Dashboard::SYSTEM && ! $orgHome->isDisabled()) {
                    Notification::info(
                        sprintf(t('It is not allowed to edit system home: "%s"'), $orgHome->getLabel())
                    );

                    return;
                }

                $newHome = new DashboardHome($orgHome->getName());
                $newHome
                    ->setType($orgHome->getType())
                    ->setAuthUser($orgHome->getAuthUser())
                    ->setDisabled($orgHome->isDisabled())
                    ->setLabel($this->getValue('title'));

                if ($newHome->getLabel() === $orgHome->getLabel() && ! $orgHome->isDisabled()) {
                    Notification::info(sprintf(t('Dashboard home "%s" already exists'), $newHome->getLabel()));

                    return;
                }

                if ($this->getPopulatedValue('enable_home') === 'y') {
                    $newHome->setDisabled(false);
                }

                $dashboard->manageHome($newHome);

                $message = sprintf(
                    t('Dashboard home "%s" successfully renamed to "%s".'),
                    $orgHome->getLabel(),
                    $newHome->getLabel()
                );

                if (! $newHome->isDisabled() && $orgHome->isDisabled()) {
                    $message = sprintf(
                        t('Dashboard home "%s" has been successfully enabled.'),
                        $orgHome->getLabel()
                    );
                }

                Notification::success($message);
            } else {
                if ($this->getPopulatedValue('purge_from_db') === 'y') {
                    $orgHome->setAdditional('unshared_users', new DbUser('*'));
                }

                $dashboard->removeHome($orgHome->getName());

                $msg = sprintf(t('System dashboard home has been disabled: "%s"'), $orgHome->getLabel());
                if ($orgHome->getType() !== Dashboard::SYSTEM) {
                    $msg = sprintf(t('Dashboard home has been removed: "%s"'), $orgHome->getLabel());
                }

                Notification::success($msg);
            }
        }
    }

    /**
     * Populate form data from config
     *
     * @param Pane|DashboardHome  $paneOrHome
     */
    public function load($paneOrHome)
    {
        $requestPath = Url::fromRequest()->getPath();
        if (
            $requestPath === Dashboard::BASE_ROUTE . '/rename-home'
            || $requestPath === Dashboard::BASE_ROUTE . '/remove-home'
        ) {
            $title = $paneOrHome->getLabel();
        } else {
            $title = $paneOrHome->getTitle();
        }

        $this->populate([
            'org_name'  => $paneOrHome->getName(),
            'title'     => $title,
            'org_title' => $title
        ]);
    }
}
