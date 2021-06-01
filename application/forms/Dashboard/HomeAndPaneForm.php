<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class HomeAndPaneForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    /**
     * RenamePaneForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('btn_remove')
                || $this->getPopulatedValue('btn_update'));
    }

    public function assemble()
    {
        $removeHome = 'dashboard/remove-home';
        $renamePane = 'dashboard/rename-pane';
        $removePane = 'dashboard/remove-pane';

        $requestPath = Url::fromRequest()->getPath();

        $activeHome = $this->dashboard->getActiveHome();
        $populated = $this->getPopulatedValue('home', $activeHome->getName());
        $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

        $dbTarget = '_main';
        $btnUpdateLabel = t('Update Home');
        $btnRemoveLabel = t('Remove Home');
        $nameDesc = t('Edit the current home name');
        $titleDesc = t('Edit the current home title.');
        $formaction = (string) Url::fromRequest()->setPath($removeHome);

        $this->addElement('hidden', 'org_name', ['required' => false]);

        if ($requestPath === $renamePane || $requestPath === 'dashboard/rename-home') {
            if ($requestPath === 'dashboard/rename-home') {
                if ($this->dashboard->getActiveHome()->getDisabled()) {
                    $this->addElement(
                        'checkbox',
                        'enable_home',
                        [
                            'label'         => t('Enable Home'),
                            'value'         => 'n',
                            'description'   => t('Check this box if you want to enable this home.')
                        ]
                    );
                }
            }

            if ($renamePane === $requestPath) {
                $dbTarget = '_self';
                $btnUpdateLabel = t('Update Pane');
                $btnRemoveLabel = t('Remove Pane');
                $nameDesc = t('Edit the current pane name');
                $titleDesc = t('Edit the current pane title.');
                $formaction = (string) Url::fromRequest()->setPath($removePane);

                $this->addElement('hidden', 'org_title', ['required' => false]);
                $this->addElement(
                    'checkbox',
                    'create_new_home',
                    [
                        'class'         => 'autosubmit',
                        'disabled'      => empty($dashboardHomes) ?: null,
                        'required'      => false,
                        'label'         => t('New Dashboard Home'),
                        'description'   => t('Check this box if you want to move the pane to a new dashboard home.'),
                    ]
                );

                if (empty($dashboardHomes) || $this->getPopulatedValue('create_new_home') === 'y') {
                    if (empty($dashboardHomes)) {
                        $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);
                    }
                    $this->addElement(
                        'text',
                        'home',
                        [
                            'required'      => true,
                            'label'         => t('Dashboard Home'),
                            'description'   => t('Enter a title for the new dashboard home.'),
                        ]
                    );
                } else {
                    $this->addElement(
                        'select',
                        'home',
                        [
                            'required'      => true,
                            'label'         => t('Move to home'),
                            'multiOptions'  => $dashboardHomes,
                            'value'         => $populated,
                            'description'   => t('Select a dashboard home you want to move the dashboard to'),
                        ]
                    );
                }

                $pane = $this->dashboard->getActiveHome()->getPane(Url::fromRequest()->getParam('pane'));
                if ($pane->getDisabled()) {
                    $this->addElement(
                        'checkbox',
                        'enable_pane',
                        [
                            'label'         => t('Enable Pane'),
                            'value'         => 'n',
                            'description'   => t('Check this box if you want to enable this pane.')
                        ]
                    );
                }
            }

            $this->addElement(
                'text',
                'name',
                [
                    'required'      => true,
                    'label'         => t('Name'),
                    'description'   => $nameDesc
                ]
            );

            $this->addElement(
                'text',
                'title',
                [
                    'required'      => true,
                    'label'         => t('Title'),
                    'description'   => $titleDesc
                ]
            );
        }

        if ($removePane == $requestPath || $requestPath === 'dashboard/remove-home') {
            $message = sprintf(t('Please confirm removal of dashboard home "%s"'), $activeHome->getName());

            if ($requestPath === $removePane) {
                $btnRemoveLabel = t('Remove Pane');
                $formaction = (string)Url::fromRequest()->setPath($removePane);
                $message = sprintf(t('Please confirm removal of dashboard "%s"'), Url::fromRequest()->getParam('pane'));
            }

            $this->add(new HtmlElement('h1', null, $message));
        }

        $this->add(
            new HtmlElement(
                'div',
                [
                    'class' => 'control-group form-controls',
                    'style' => 'position: relative; margin-right: 1em; margin-top: 2em;'
                ],
                [
                    new HtmlElement(
                        'input',
                        [
                            'class'             => 'btn-primary',
                            'type'              => 'submit',
                            'name'              => 'btn_remove',
                            'data-base-target'  => $dbTarget,
                            'value'             => $btnRemoveLabel,
                            'formaction'        => $formaction
                        ]
                    ),
                    $removeHome === $requestPath || $removePane === $requestPath ? '' :
                    new HtmlElement(
                        'input',
                        [
                            'class' => 'btn-primary',
                            'type'  => 'submit',
                            'name'  => 'btn_update',
                            'value' => $btnUpdateLabel
                        ]
                    )
                ]
            )
        );
    }

    public function onSuccess()
    {
        $db = DashboardHome::getConn();
        $username = $this->dashboard->getUser()->getUsername();
        $requestPath = Url::fromRequest()->getPath();

        $orgHome = $this->dashboard->getActiveHome();

        if ($requestPath === 'dashboard/rename-pane' || $requestPath === 'dashboard/remove-pane') {
            // Update the given pane
            $orgPane = $this->getValue('org_name');
            $pane = $orgHome->getPane($orgPane);

            if ($this->getPopulatedValue('btn_update')) {
                $newHome = $this->getPopulatedValue('home', $orgHome->getName());
                $orgHomeId = $orgHome->getIdentifier();

                if ($pane->getOwner() === DashboardHome::DEFAULT_IW2_USER && $orgHome->getName() !== $newHome) {
                    Notification::error(sprintf(
                        t('It is not allowed to move system dashboard: "%s"'),
                        $pane->getTitle()
                    ));

                    return;
                }

                // Begin DB transaction
                $db->beginTransaction();

                $homeId = $orgHomeId;
                if (! $this->dashboard->hasHome($newHome) || /** prevents from failing foreign key constraint */
                    ($this->dashboard->getHome($newHome)->getOwner() === DashboardHome::DEFAULT_IW2_USER &&
                    $this->dashboard->getHome($newHome)->getName() !== DashboardHome::DEFAULT_HOME)) {
                    $label = $newHome;

                    if ($this->dashboard->hasHome($newHome)) {
                        $label = $this->dashboard->getHome($newHome)->getLabel();
                    }

                    $db->insert('dashboard_home', ['name' => $newHome, 'label' => $label, 'owner' => $username]);

                    $homeId = $db->lastInsertId();
                } elseif ($orgHome->getName() !== $newHome) {
                    $homeId = $this->dashboard->getHome($newHome)->getIdentifier();
                }

                $paneUpdated = false;
                if ($this->getPopulatedValue('enable_pane') === 'y') {
                    $paneUpdated = true;

                    $db->update('dashboard_override', ['disabled' => (int) false], [
                        'owner = ?'         => $pane->getOwner(),
                        'dashboard_id = ?'  => $pane->getPaneId(),
                    ]);
                }

                if (! $paneUpdated) {
                    if ($pane->isOverridingPane()) { // Custom panes that overwrites system panes
                        $db->update('dashboard_override', [
                            'home_id'   => $homeId,
                            'label'     => $this->getValue('title'),
                        ], [
                            'owner = ?'         => $pane->getOwner(),
                            'dashboard_id = ?'  => $pane->getPaneId(),
                        ]);

                    } elseif ($pane->getOwner() === DashboardHome::DEFAULT_IW2_USER) { // System panes
                        $paneId = DashboardHome::getSHA1($username . $newHome . $pane->getName());

                        $db->insert('dashboard_override', [
                            'dashboard_id'  => $paneId,
                            'home_id'       => $homeId,
                            'owner'         => $username,
                            'label'         => $this->getValue('title')
                        ]);
                    } else { // Custom panes
                        $db->update('dashboard', [
                            'home_id'   => $homeId,
                            'name'      => $this->getValue('name'),
                            'label'     => $this->getPopulatedValue('title'),
                        ], ['id = ?' => $pane->getPaneId()]);
                    }
                }

                // Commit DB transaction
                $db->commitTransaction();

                $message = sprintf(
                    t('Pane "%s" successfully renamed to "%s".'),
                    $pane->getTitle(),
                    $this->getValue('title')
                );

                if ($orgHome->getName() !== $newHome) {
                    $message = sprintf(
                        t('Pane "%s" successfully moved from "%s" to "%s"'),
                        $this->getValue('title'),
                        $orgHome->getName(),
                        $newHome
                    );
                }

                Notification::success($message);
            } else {
                // Remove the given pane and it's dashlets
                $pane->removeDashlets();
                $orgHome->removePane($pane->getName());

                Notification::success(t('Dashboard has been removed') . ': ' . $pane->getTitle());
            }
        } else {
            if ($this->getPopulatedValue('btn_update')) {
                if ($orgHome->getOwner() === DashboardHome::DEFAULT_IW2_USER && ! $orgHome->getDisabled()) {
                    Notification::error(sprintf(t('It is not allowed to edit system home: "%s"'), $orgHome->getName()));

                    return;
                }

                $newHome = $this->getValue('name');
                $label = $this->getValue('title');

                if ($orgHome->getName() !== $newHome && $this->dashboard->hasHome($newHome)) {
                    Notification::warning(sprintf(t('Dashboard home "%s" already exists'), $newHome));

                    return;
                }

                $homeUpdated = false;
                if ($this->getPopulatedValue('enable_home') === 'y') {
                    $homeUpdated = true;

                    $db->update('dashboard_home', ['disabled' => 0], [
                        'id = ?'    => $orgHome->getIdentifier(),
                        'owner = ?' => $orgHome->getOwner()
                    ]);
                }

                if (! $homeUpdated) {
                    $db->update('dashboard_home', ['name' => $newHome, 'label' => $label], [
                        'id = ?' => $orgHome->getIdentifier(),
                        'owner = ?' => $username
                    ]);
                }

                $notificationMsg = sprintf(
                    t('Dashboard home "%s" successfully renamed to "%s".'),
                    $orgHome->getName(),
                    $newHome
                );

                if ($homeUpdated) {
                    $notificationMsg = sprintf(
                        t('Dashboard home "%s" has been successfully enabled.'),
                        $orgHome->getName()
                    );
                }

                Notification::success($notificationMsg);
            } else {
                // Remove the given home with it's panes and dashlets
                $this->dashboard->removeHome($orgHome->getName());

                if ($orgHome->getAttribute('owner')) {
                    Notification::success(sprintf(t('Dashboard home has been removed: "%s"'), $orgHome));
                } else {
                    Notification::success(
                        sprintf(t('System dashboard home has been disabled: "%s"'), $orgHome->getName())
                    );
                }
            }
        }
    }

    /**
     * @param Dashboard\Pane|NavigationItem  $paneOrHome
     */
    public function load($paneOrHome)
    {
        $requestPath = Url::fromRequest()->getPath();

        if ($requestPath === 'dashboard/rename-home' || $requestPath === 'dashboard/remove-home') {
            $title = $paneOrHome->getLabel();
        } else {
            $title = $paneOrHome->getTitle();
        }

        $this->populate([
            'name'      => $paneOrHome->getName(),
            'org_name'  => $paneOrHome->getName(),
            'title'     => $title,
            'org_title' => $title
        ]);
    }
}
