<?php

/* Icinga Web 2 | (c) 2013-2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Authentication\Auth;
use Icinga\DBUser;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Form to add an url a dashboard pane
 */
class DashletForm extends CompatForm
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
     * DashletForm constructor.
     *
     * @param Dashboard $dashboard
     * @param Auth $auth
     */
    public function __construct(Dashboard $dashboard, Auth $auth)
    {
        $this->dashboard = $dashboard;
        $this->auth = $auth;
    }

    /**
     * Populate form data from config
     *
     * @param Dashlet $dashlet
     * @param string  $home
     */
    public function load(Dashlet $dashlet, $home)
    {
        $this->populate(array(
            'pane'          => $dashlet->getPane()->getName(),
            'org_pane'      => $dashlet->getPane()->getName(),
            'org_home'      => $home,
            'dashlet'       => $dashlet->getTitle(),
            'org_dashlet'   => $dashlet->getName(),
            'url'           => $dashlet->getUrl()->getRelativeUrl()
        ));
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('remove_dashlet')
                || $this->getPopulatedValue('submit'));
    }

    /**
     * Check whether the auth user has the required dashboard
     * permission to manage system and public dashboards
     *
     * @return bool
     */
    protected function hasPerm()
    {
        return $this->auth->hasPermission('application/manage/dashboards');
    }

    /**
     * Check whether the dashlet is user widget and returns true
     * if the dashlet is user widget or if the request path is new-dashlet
     *
     * @return bool
     */
    protected function isUserWidget()
    {
        if (($dashlet = $this->dashletExist())) {
            return $dashlet->isUserWidget() && ! $dashlet->isOverridingWidget();
        }

        return true;
    }

    /**
     * Check whether this dashlet being loaded is public dashlet
     *
     * @return bool
     */
    protected function isPublic()
    {
        if (($dashlet = $this->dashletExist())) {
            return $dashlet->getType() === Dashboard::PUBLIC_DS;
        }

        return false;
    }

    /**
     * Get whether the dashlet being currently loaded is shared one
     *
     * @return bool
     */
    protected function isShared()
    {
        if (($dashlet = $this->dashletExist())) {
            return $dashlet->getType() === Dashboard::SHARED;
        }

        return false;
    }

    /**
     * Render an optional checkbox to purge a dashboard from DB
     *
     * @return void
     */
    protected function renderRemoveForAllUsers()
    {
        if ($this->hasPerm() && ($this->isPublic() || $this->isShared())) {
            $this->addElement('checkbox', 'purge_from_db', [
                'value'         => 'n',
                'label'         => t('Purge from DB'),
                'description'   => t('Check this box if you want to completely remove this dashlet from DB')
            ]);
        }
    }

    protected function assemble()
    {
        $requestUrl = Url::fromRequest();
        $requestPath = $requestUrl->getPath();
        $removeDashlet = Dashboard::BASE_ROUTE . '/remove-dashlet';
        $updateDashlet = Dashboard::BASE_ROUTE . '/update-dashlet';

        $activeHome = $this->dashboard->getActiveHome();
        $home = $requestUrl->getParam('home');
        $populatedHome = $this->getPopulatedValue('home', $home);

        $paneParam = $requestUrl->getParam('pane');
        $dashletParam = $requestUrl->getParam('dashlet');

        $panes = [];
        $dashboardHomes = [];
        if ($this->dashboard) {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

            if (empty($home)) {
                $home = current($dashboardHomes);
                $populatedHome = $this->getPopulatedValue('home', $home);
            }

            if ($home === $populatedHome && $this->getPopulatedValue('create_new_home') !== 'y') {
                if (! empty($home) && $activeHome) {
                    $panes = $activeHome->getPaneKeyTitleArray();
                } else {
                    // This tab was opened from where the home parameter is not being present
                    $firstHome = $this->dashboard->rewindHomes();

                    if (! empty($firstHome)) {
                        $this->dashboard->loadDashboards($firstHome->getName());
                        $panes = $firstHome->getPaneKeyTitleArray();
                    }
                }
            } else {
                if ($this->dashboard->hasHome($populatedHome)) {
                    $this->dashboard->loadDashboards($populatedHome);

                    $panes = $this->dashboard->getActiveHome()->getPaneKeyTitleArray();
                }
            }
        }

        if ($requestPath === $removeDashlet) {
            $this->addHtml(HtmlElement::create('h1', null, sprintf(
                t('Please confirm removal of dashlet "%s"'),
                $dashletParam
            )));

            $this->renderRemoveForAllUsers();

            $this->addElement('submit', 'remove_dashlet', [
                'disabled'  => ! $this->hasPerm() && ! $this->isUserWidget() ?: null,
                'label'     => $this->hasPerm() && ! $this->isUserWidget() ? t('Disable Dashlet') : t('Remove Dashlet'),
                'title'     => ! $this->hasPerm() && ! $this->isUserWidget()
                    ? t('You have not the required permission to disable system dashlet')
                    : null
            ]);
        } else {
            $submitLabel = t('Add To Dashboard');
            $formTitle = t('Add Dashlet To Dashboard');

            if ($requestPath === $updateDashlet) {
                $submitLabel = t('Update Dashlet');
                $formTitle = t('Edit Dashlet');
            }

            $this->addHtml(HtmlElement::create('h1', null, $formTitle));
            $this->addElement('hidden', 'org_pane', ['required'     => false]);
            $this->addElement('hidden', 'org_home', ['required'     => false]);
            $this->addElement('hidden', 'org_dashlet', ['required'  => false]);
            $this->addElement('checkbox', 'create_new_home', [
                'class'         => 'autosubmit',
                'disabled'      => empty($dashboardHomes) ?: null,
                'required'      => false,
                'label'         => t('New Dashboard Home'),
                'description'   => t('Check this box if you want to add the dashboard to a new dashboard home'),
            ]);

            if (empty($dashboardHomes) || $this->getPopulatedValue('create_new_home') === 'y') {
                $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);

                $this->addElement('text', 'home', [
                    'required'      => true,
                    'label'         => t('Dashboard Home'),
                    'description'   => t('Enter a title for the new dashboard home'),
                ]);
            } else {
                $this->addElement('select', 'home', [
                    'class'         => 'autosubmit',
                    'required'      => true,
                    'label'         => t('Dashboard Home'),
                    'multiOptions'  => $dashboardHomes,
                    'value'         => $home,
                    'description'   => t('Select a home you want to add the pane to'),
                ]);
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
                    'label'         => t('New Dashboard Title'),
                    'description'   => t('Enter a title for the new dashboard'),
                ]);
            } else {
                $this->addElement('select', 'pane', [
                    'required'      => true,
                    'label'         => t('Dashboard'),
                    'multiOptions'  => $panes,
                    'value'         => reset($panes),
                    'description'   => t('Select a dashboard you want to add the dashlet to'),
                ]);
            }

            if ($this->hasPerm() && $this->isUserWidget()) {
                $this->addElement('checkbox', 'create_public_dashboard', [
                    'class'         => 'autosubmit',
                    'required'      => false,
                    'label'         => t('Public Dashboard'),
                    'description'   => t('Check this box if you want to propose this as default dashboard for others'),
                ]);
            }

            if ($this->getPopulatedValue('create_public_dashboard') === 'y') {
                if (! empty($this->dashboard->getUsers())){
                    $users = array_keys($this->dashboard->getUsers());
                    $this->addElement('select', 'user_name[]', [
                        'required'      => false,
                        'multiple'      => true,
                        'multiOptions'  => array_combine($users, $users),
                        'label'         => t('Users'),
                        'description'   => t(
                            'Select one or more users you want to set this as default dashboard for'
                        ),
                    ]);
                }

                $this->addElement('textarea', 'users', [
                    'required'      => false,
                    'label'         => t('Users'),
                    'description'   => t(
                        'Provide comma separated list of usernames to set the dashboard as default for'
                    )
                ]);
            }

            $this->add(new HtmlElement('hr'));

            if ($requestPath === $updateDashlet) {
                if ($home === $populatedHome) {
                    $dashlet = $this->dashboard->getActiveHome()->getPane($paneParam)->getDashlet($dashletParam);
                    if ($dashlet->isDisabled()) {
                        $this->addElement('checkbox', 'enable_dashlet', [
                            'value'         => 'n',
                            'label'         => t('Enable Dashlet'),
                            'description'   => t('Check this box if you want to enable this dashlet')
                        ]);
                    }
                }
            }

            $this->renderRemoveForAllUsers();
            $this->addElement('textarea', 'url', [
                'required'      => true,
                'label'         => t('Url'),
                'description'   => t(
                    'Enter url to be loaded in the dashlet. You can paste the full URL, including filters'
                ),
            ]);

            $this->addElement('text', 'dashlet', [
                'required'      => true,
                'label'         => t('Dashlet Title'),
                'description'   => t('Enter a title for the dashlet'),
            ]);

            $controlGroup = HtmlElement::create('div', [
                'class' => 'control-group form-controls',
                'style' => 'position: relative;  margin-top: 2em;'
            ]);

            if ($requestPath === $updateDashlet) {
                $controlGroup->addHtml(HtmlElement::create('input', [
                    'class'         => 'btn-primary',
                    'type'          => 'submit',
                    'name'          => 'remove_dashlet',
                    'value'         => ! $this->isUserWidget() ? t('Disable Dashlet') : t('Remove Dashlet'),
                    'formaction'    => (string) $requestUrl->setPath($removeDashlet),
                    'disabled'      => ! $this->hasPerm() && (! $this->isUserWidget() || $this->isPublic()) ?: null,
                    'title'         => ! $this->hasPerm() && (! $this->isUserWidget() || $this->isPublic())
                        ? sprintf(
                            t('You have not the required permission to %s dashlet'),
                            ! $this->isUserWidget() ? 'disable system' : 'remove public'
                        )
                        : null
                ]));
            }

            $controlGroup->addHtml(HtmlElement::create('input', [
                'class'     => 'btn-primary',
                'type'      => 'submit',
                'name'      => 'submit',
                'value'     => $submitLabel,
                'disabled'  => ! $this->hasPerm() && (! $this->isUserWidget() || $this->isPublic()) ?: null,
                'title'     => ! $this->hasPerm() && (! $this->isUserWidget() || $this->isPublic())
                    ? sprintf(
                        t('You have not the required permission to edit %s dashlet'),
                        ! $this->isUserWidget() ? 'system' : 'public'
                    )
                    : null
            ]));

            $this->addHtml($controlGroup);
        }
    }

    protected function onSuccess()
    {
        $dashboard = $this->dashboard;
        $isPublicDashboard = $this->getPopulatedValue('create_public_dashboard') === 'y';
        $usernames = $this->getShareWithUsers();

        if (Url::fromRequest()->getPath() === Dashboard::BASE_ROUTE . '/new-dashlet') {
            $newHome = new DashboardHome($this->getValue('home'));
            $newHome
                ->setAuthUser($dashboard->getAuthUser())
                ->setAdditional('with_users', $usernames)
                ->setType($isPublicDashboard ? Dashboard::PUBLIC_DS : Dashboard::PRIVATE_DS);

            $pane = new Pane($this->getValue('pane'));
            $pane
                ->setUserWidget()
                ->setType($newHome->getType())
                ->setAdditional('with_users', $usernames);

            $dashlet = new Dashlet($this->getValue('dashlet'), $this->getValue('url'), $pane);
            $dashlet
                ->setUserWidget()
                ->setType($pane->getType())
                ->setAdditional('with_users', $usernames);

            $orgHome = null;
            if ($dashboard->hasHome($newHome->getName())) {
                $orgHome = $dashboard->getActiveHome();
                if ($isPublicDashboard && $orgHome->getType() !== Dashboard::PUBLIC_DS) {
                    Notification::error(
                        sprintf(t('You cannot create public dashboard in a "%s" home'), $orgHome->getType())
                    );

                    return;
                }

                if ($orgHome->getName() !== $dashboard->getActiveHome()->getName()) {
                    $orgHome = $dashboard->getHome($newHome->getName());
                    $orgHome->setActive(true);
                    $orgHome->loadUserDashboards();
                }

                if ($orgHome->hasPane($pane->getName())) {
                    $orgPane = $orgHome->getPane($pane->getName());
                    if ($isPublicDashboard && $orgPane->getType() !== Dashboard::PUBLIC_DS) {
                        Notification::error(
                            sprintf(t('You cannot create public dashlet in a "%s" pane'), $orgHome->getType())
                        );

                        return;
                    }

                    if ($orgPane->hasDashlet($dashlet->getName())) {
                        Notification::info(t('There already exists a dashlet with the same name'));

                        return;
                    }

                    $pane
                        ->setCtime(strtotime($orgPane->getCtime()))
                        ->setMtime(strtotime($orgPane->getMtime()))
                        ->setType($orgPane->getType())
                        ->setPaneId($orgPane->getPaneId());
                }

                $newHome
                    ->setName($orgHome->getName())
                    ->setType($orgHome->getType())
                    ->setIdentifier($orgHome->getIdentifier());
            }

            $pane->addDashlet($dashlet);
            $newHome->addPane($pane);
            $dashboard->manageHome($newHome, $orgHome);

            Notification::success(t('Dashlet created'));
        } else {
            if ($this->getPopulatedValue('remove_dashlet')) {
                $requestUrl = Url::fromRequest();
                $home = $dashboard->getHome($requestUrl->getParam('home'));
                $pane = $home->getPane($requestUrl->getParam('pane'));
                $dashlet = $pane->getDashlet($requestUrl->getParam('dashlet'));

                if ($this->getPopulatedValue('purge_from_db') === 'y') {
                    $dashlet->setAdditional('unshared_users', new DBUser('*'));
                }

                $pane->removeDashlet($dashlet);

                $message = sprintf(t('Removed dashlet "%s" successfully'), $pane->getTitle());
                if (! $dashlet->isUserWidget()) {
                    $message = sprintf(t('Disabled dashlet "%s" successfully'), $pane->getTitle());
                }

                Notification::success($message);
            } else {
                $orgHome = $dashboard->getHome($this->getValue('org_home'));

                $orgPane = $orgHome->getPane($this->getValue('org_pane'));
                $orgDashlet = $orgPane->getDashlet($this->getValue('org_dashlet'));

                $newHome = new DashboardHome($this->getValue('home', $orgHome->getName()));
                $newHome
                    ->setType(Dashboard::PRIVATE_DS)
                    ->setAuthUser($orgHome->getAuthUser());

                if (
                    $this->getPopulatedValue('create_new_home') !== 'y'
                    && $newHome->getName() === $orgHome->getName()
                ) {
                    $newHome->setLabel($orgHome->getLabel());
                }

                if (
                    ! $orgDashlet->isUserWidget()
                    && (
                        $orgPane->getName() !== $this->getValue('pane')
                        || $orgHome->getName() !== $newHome->getName()
                    )
                ) {
                    Notification::info(t(
                        'It is not allowed to move system dashlet: "' . $this->getValue('org_dashlet') . '"'
                    ));

                    return;
                }

                $newPane = new Pane($this->getValue('pane'));
                $newPane
                    ->setType($orgPane->getType())
                    ->setUserWidget($orgPane->isUserWidget())
                    ->setOverride($orgPane->isOverridingWidget());

                if ($this->getPopulatedValue('create_new_pane') !== 'y') {
                    $newPane
                        ->setUserWidget()
                        ->setOverride(false)
                        ->setType(Dashboard::PRIVATE_DS)
                        ->setPaneId($orgPane->getPaneId());
                }

                $dashlet = clone $orgDashlet;
                $dashlet
                    ->setPane($newPane)
                    ->setUrl($this->getValue('url'))
                    ->setTitle($this->getValue('dashlet'));

                if ($this->getPopulatedValue('enable_dashlet') === 'y') {
                    $dashlet->setDisabled(false);
                }

                if (
                    $dashlet->isOverridingWidget()
                    && (
                        $orgPane->getName() !== $newPane->getName()
                        || $orgHome->getName() !== $newHome->getName()
                    )
                ) {
                    Notification::info(sprintf(
                        t('Dashlet "%s" cannot be moved, as it overwrites a system dashlet'),
                        $orgDashlet->getTitle()
                    ));

                    return;
                }

                if ($dashboard->hasHome($newHome->getName())) {
                    $copyHome = $dashboard->getActiveHome();
                    if (
                        $orgHome->getName() !== $newHome->getName()
                        || $dashboard->getActiveHome()->getName() !== $newHome->getName()
                    ) {
                        $copyHome = (clone $dashboard->getHome($newHome->getName()))->setPanes([]);
                        $copyHome->setActive(true);
                        $copyHome->loadUserDashboards();
                    }

                    if ($copyHome->hasPane($newPane->getName())) {
                        $pane = $copyHome->getPane($newPane->getName());
                        if (
                            $pane->hasDashlet($dashlet->getName())
                            && ! $orgDashlet->isDisabled()
                            && (
                                $dashlet->getTitle() === $orgDashlet->getTitle()
                                || $newHome->getName() !== $orgHome->getName()
                                || $pane->getName() !== $orgPane->getName()
                            )
                        ) {
                            Notification::info(sprintf(
                                t('There is already a dashlet "%s" within this pane.'),
                                $orgDashlet->getTitle()
                            ));

                            return;
                        }

                        $newPane
                            ->setUserWidget()
                            ->setType($pane->getType())
                            ->setPaneId($pane->getPaneId())
                            ->setDisabled($pane->isDisabled());
                    }

                    if (
                        $this->getPopulatedValue('create_new_pane') !== 'y'
                        && $copyHome->hasPane($newPane->getName())
                    ) {
                        $newPane->setTitle($copyHome->getPane($newPane->getName())->getTitle());
                    }

                    $newHome->setType($copyHome->getType());
                }

                $paneDiff = array_filter(array_diff_assoc($newPane->toArray(), $orgPane->toArray()));
                $dashletDiff = array_filter(
                    array_diff_assoc($dashlet->toArray(), $orgDashlet->toArray()),
                    function ($value) {
                        return $value !== null;
                    }
                );

                // Prevent meaningless updates when there weren't any changes,
                // e.g. when the user just presses the update button without changing anything
                if ($orgHome->getName() !== $newHome->getName() || ! empty($dashletDiff) || ! empty($paneDiff)) {
                    $orgPane->setDashlets([$orgDashlet->getName() => $orgDashlet]);
                    // We need to use the new pane name as a key here, because we will have an easier task
                    // to manage it afterwards. So, it mainly serves us not to create the dashlet over again,
                    // even though the user wants to move it
                    $orgHome->setPanes([$newPane->getName() => $orgPane]);

                    $newPane->addDashlet($dashlet);
                    $newHome->addPane($newPane);
                    $dashboard->manageHome($newHome, $orgHome);
                }

                Notification::success(t('Dashlet updated'));
            }
        }
    }

    /**
     * @return false|Dashlet
     *
     * @throws \Icinga\Exception\ProgrammingError
     */
    private function dashletExist()
    {
        $requestUrl = Url::fromRequest();
        $pane = $this->getPopulatedValue('pane');
        if (
            $pane
            && $requestUrl->getPath() === Dashboard::BASE_ROUTE . '/update-dashlet'
            || $requestUrl->getPath() === Dashboard::BASE_ROUTE . '/remove-dashlet'
        ) {
            $activeHome = $this->dashboard->getActiveHome();
            if (! $activeHome->hasPane($pane)) {
                return false;
            }

            $dashlet = $requestUrl->getParam('dashlet');
            $pane = $activeHome->getPane($pane);
            if (! $pane->hasDashlet($dashlet)) {
                return false;
            }

            return $pane->getDashlet($dashlet);
        }

        return false;
    }

    /**
     * Get all user to share the dashboard with
     *
     * @return DBUser[]
     */
    private function getShareWithUsers()
    {
        $usernames = $this->getPopulatedValue('user_name') ?: [];
        if (($users = $this->getValue('users'))) {
            $usernames = array_merge($usernames, array_map('trim', explode(',', $users)));
        }

        $users = [];
        foreach ($usernames as $username) {
            $users[$username] = new DBUser($username);
        }

        return $users;
    }
}
