<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Navigation\NavigationItem;
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

    private $dashlets;

    /**
     * RenamePaneForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard, array $dashlets = [])
    {
        $this->dashboard = $dashboard;
        $this->dashlets = $dashlets;
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
        $newPanePath = 'dashboard/new-pane';

        $requestPath = Url::fromRequest()->getPath();

        $activeHome = $this->dashboard->getActiveHome();
        $populated = $this->getPopulatedValue('home', $activeHome->getName());
        $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

        $dbTarget = '_main';
        $btnUpdateLabel = t('Update Home');
        $btnRemoveLabel = t('Remove Home');
        $titleDesc = t('Edit the current home title.');
        $formaction = (string) Url::fromRequest()->setPath($removeHome);

        $this->addElement('hidden', 'org_name', ['required' => false]);

        if ($newPanePath === $requestPath) {
            $this->add(HtmlElement::create('p', ['class' => 'paragraph'], [
                'You\'re about to create a Dashboard from ',
                HtmlElement::create('span', ['class' => 'count-pinned'], count($this->dashlets)),
                ' pinned Dashlets.'
            ]));
        }

        if ($requestPath === $renamePane
            || $newPanePath === $requestPath
            || $requestPath === 'dashboard/rename-home') {
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

            if ($renamePane === $requestPath || $newPanePath === $requestPath) {
                if ($renamePane === $requestPath) {
                    $dbTarget = '_self';
                    $btnUpdateLabel = t('Update Pane');
                    $btnRemoveLabel = t('Remove Pane');
                    $titleDesc = t('Edit the current pane title.');
                    $formaction = (string) Url::fromRequest()->setPath($removePane);

                    $this->addElement('hidden', 'org_title', ['required' => false]);
                }

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
                            'label'         => $newPanePath === $requestPath ? t('Dashboard Home') : t('Move to home'),
                            'multiOptions'  => $dashboardHomes,
                            'value'         => $populated,
                            'description'   => t('Select a dashboard home you want to move the dashboard to'),
                        ]
                    );
                }

                if ($renamePane === $requestPath) {
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
            }

            if ($newPanePath !== $requestPath) {
                $this->addElement(
                    'text',
                    'title',
                    [
                        'required'      => true,
                        'label'         => t('Title'),
                        'description'   => $titleDesc
                    ]
                );
            } else {
                $this->addElement(
                    'text',
                    'pane',
                    [
                        'required'      => true,
                        'label'         => t('Dashboard Name'),
                        'description'   => t('Enter a title for the new dashboard.'),
                    ]
                );

                $this->addElement(
                    'textarea',
                    'shared-with',
                    [
                        'required'      => false,
                        'label'         => t('Share with'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to share with.'
                        ),
                    ]
                );

                $this->addElement(
                    'textarea',
                    'write-access',
                    [
                        'required'      => false,
                        'label'         => t('Write Permissions'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to grant write access to.'
                        ),
                    ]
                );
            }
        }

        if ($removePane === $requestPath || $requestPath === 'dashboard/remove-home') {
            $message = sprintf(t('Please confirm removal of dashboard home "%s"'), $activeHome->getName());

            if ($requestPath === $removePane) {
                $btnRemoveLabel = t('Remove Pane');
                $formaction = (string)Url::fromRequest()->setPath($removePane);
                $message = sprintf(t('Please confirm removal of dashboard "%s"'), Url::fromRequest()->getParam('pane'));
            }

            $this->add(new HtmlElement('h1', null, Text::create($message)));
        }

        if ($newPanePath !== $requestPath) {
            $this->add(
                HtmlElement::create(
                    'div',
                    ['class' => 'control-group form-controls'],
                    [
                        HtmlElement::create(
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
                            HtmlElement::create(
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
        } else {
            $this->addElement('submit', 'btn_new_pane', ['label' => t('Add To Dashboard')]);
        }
    }

    public function onSuccess()
    {
        $db = DashboardHome::getConn();
        $username = $this->dashboard->getUser()->getUsername();
        $requestPath = Url::fromRequest()->getPath();

        $orgHome = $this->dashboard->getActiveHome();

        if ($requestPath === 'dashboard/rename-pane'
            || $requestPath === 'dashboard/remove-pane'
            || $requestPath === 'dashboard/new-pane') {
            // Update the given pane
            $orgPane = $this->getValue('org_name');
            $pane = $orgHome->getPane($orgPane);

            if ($this->getPopulatedValue('btn_update')) {
                $newHome = $this->getPopulatedValue('home', $orgHome->getName());
                $homeId = $orgHome->getIdentifier();

                if (! $pane->isUserWidget() && $orgHome->getName() !== $newHome) {
                    Notification::error(sprintf(
                        t('It is not allowed to move system dashboard: "%s"'),
                        $pane->getTitle()
                    ));

                    return;
                }

                if ($pane->isOverridingWidget() && $orgHome->getName() !== $newHome) {
                    Notification::error(sprintf(
                        t('Pane "%s" can\'t be moved, as it overwrites a system pane'),
                        $pane->getName()
                    ));

                    return;
                }

                $createNewHome = true;
                if ($this->dashboard->hasHome($newHome)) {
                    $home = $this->dashboard->getHome($newHome);
                    $homeId = $home->getIdentifier();

                    $createNewHome = false;

                    if ($home->getName() !== $orgHome->getName()) {
                        // It's essential, so we can check with something like this
                        // „$this->dashboard->getActiveHome()->getOwner()” later on downstairs.
                        $this->dashboard->loadDashboards($home->getName());
                    }
                }

                // Begin the DB transaction
                $db->beginTransaction();

                $paneEnabled = false;
                if ($this->getPopulatedValue('enable_pane') === 'y') {
                    $paneEnabled = true;

                    $db->update(Pane::OVERRIDING_TABLE, ['disabled' => (int) false], [
                        'owner = ?'         => $pane->getOwner(),
                        'dashboard_id = ?'  => $pane->getPaneId(),
                    ]);
                }

                if (! $paneEnabled) {
                    $paneId = DashboardHome::getSHA1($username . $newHome . $pane->getName());

                    if ($pane->isOverridingWidget()) { // Custom panes that overwrites system panes
                        $db->update(Pane::OVERRIDING_TABLE, [
                            'dashboard_id'  => $paneId,
                            'home_id'       => $homeId,
                            'label'         => $this->getValue('title'),
                        ], [
                            'owner = ?'         => $pane->getOwner(),
                            'dashboard_id = ?'  => $pane->getPaneId(),
                        ]);
                    } elseif (! $pane->isUserWidget()) { // System panes
                        $db->insert(Pane::OVERRIDING_TABLE, [
                            'dashboard_id'  => $paneId,
                            'home_id'       => $homeId,
                            'owner'         => $username,
                            'label'         => $this->getValue('title')
                        ]);
                    } else { // Custom panes
                        if ($createNewHome || ($newHome !== DashboardHome::DEFAULT_HOME &&
                            $this->dashboard->getActiveHome()->getOwner() === DashboardHome::DEFAULT_IW2_USER)) {
                            $label = $newHome;

                            if (! $createNewHome) {
                                $activeHome = $this->dashboard->getActiveHome();
                                $label = $activeHome->getLabel();
                            }

                            $db->insert(DashboardHome::TABLE, [
                                'name'  => $newHome,
                                'label' => $label,
                                'owner' => $username
                            ]);

                            $homeId = $db->lastInsertId();
                        }

                        try {
                            $db->update(Pane::TABLE, [
                                'id'        => $paneId,
                                'home_id'   => $homeId,
                                'label'     => $this->getPopulatedValue('title'),
                                'source'    => Pane::PRIVATE_DS,
                            ], ['id = ?' => $pane->getPaneId()]);
                        } catch (\PDOException $err) {
                            if ($err->errorInfo[1] === Dashboard::PDO_DUPLICATE_KEY_ERR) { // Duplicate entry
                                Notification::error(
                                    sprintf(t('There is already a pane "%s" within this home.'), $pane->getName())
                                );

                                return;
                            }
                        }

                        if ($newHome !== $orgHome->getName()) {
                            // As we generate the Ids according to the following concept "user + home + pane + dashlet",
                            // we have to also renew the individual Ids each time we move to another home.
                            foreach ($pane->getDashlets() as $dashlet) {
                                $dashletId = DashboardHome::getSHA1(
                                    $username . $newHome . $pane->getName() . $dashlet->getName()
                                );

                                $db->update(Dashlet::TABLE, ['id' => $dashletId], [
                                    'id = ?'            => $dashlet->getDashletId(),
                                    'dashboard_id = ?'  => $paneId
                                ]);
                            }
                        }
                    }
                }

                // Commit DB transaction
                $db->commitTransaction();

                $message = sprintf(
                    t('Pane "%s" successfully renamed to "%s".'),
                    $pane->getTitle(),
                    $this->getValue('title')
                );

                if ($paneEnabled) {
                    $message = sprintf(t('Pane "%s" successfully enabled.'), $pane->getTitle());
                } elseif ($orgHome->getName() !== $newHome) {
                    $message = sprintf(
                        t('Pane "%s" successfully moved from "%s" to "%s"'),
                        $this->getValue('title'),
                        $orgHome->getName(),
                        $newHome
                    );
                }

                Notification::success($message);
            } elseif ($this->getPopulatedValue('btn_new_pane')) {
                // New Dashboard
            } else { // Remove a pane & it's child refs
                $pane->removeDashlets();
                $orgHome->removePane($pane->getName());

                $message = t('Pane has been successfully removed') . ': ' . $pane->getTitle();

                if (! $pane->isUserWidget()) {
                    $message = t('Pane has been successfully disabled') . ': ' . $pane->getTitle();
                }

                Notification::success($message);
            }
        } else { // Dashboard homes
            if ($this->getPopulatedValue('btn_update')) {
                if ($orgHome->getOwner() === DashboardHome::DEFAULT_IW2_USER && ! $orgHome->getDisabled()) {
                    Notification::error(
                        sprintf(t('It is not allowed to edit system home: "%s"'), $orgHome->getLabel())
                    );

                    return;
                }

                $label = $this->getValue('title');
                if ($label !== $orgHome->getLabel()
                    && in_array($label, $this->dashboard->getHomeKeyNameArray(false))) {
                    Notification::warning(sprintf(t('Dashboard home "%s" already exists.'), $label));

                    return;
                }

                $homeEnabled = false;
                if ($this->getPopulatedValue('enable_home') === 'y') {
                    $homeEnabled = true;

                    try {
                        $db->update(DashboardHome::TABLE, [
                            'owner'     => DashboardHome::DEFAULT_IW2_USER,
                            'disabled'  => (int) false
                        ], ['id = ?' => $orgHome->getIdentifier()]);
                    } catch (\PDOException $err) {
                        if ($err->errorInfo[1] === Dashboard::PDO_DUPLICATE_KEY_ERR) { // Duplicate entry
                            DashboardHome::getConn()->delete(DashboardHome::TABLE, [
                                'id = ?'    => $orgHome->getIdentifier(),
                                'owner = ?' => $orgHome->getOwner()
                            ]);
                        }
                    }
                }

                if (! $homeEnabled) {
                    $db->update(DashboardHome::TABLE, ['label' => $label], [
                        'id = ?'    => $orgHome->getIdentifier(),
                        'owner = ?' => $username
                    ]);
                }

                $notificationMsg = sprintf(
                    t('Dashboard home "%s" successfully renamed to "%s".'),
                    $orgHome->getLabel(),
                    $label
                );

                if ($homeEnabled) {
                    $notificationMsg = sprintf(
                        t('Dashboard home "%s" has been successfully enabled.'),
                        $orgHome->getLabel()
                    );
                }

                Notification::success($notificationMsg);
            } else {
                $this->dashboard->removeHome($orgHome->getName());

                $msg = sprintf(t('System dashboard home has been disabled: "%s"'), $orgHome->getLabel());

                if ($orgHome->getOwner() !== DashboardHome::DEFAULT_IW2_USER) {
                    $msg = sprintf(t('Dashboard home has been removed: "%s"'), $orgHome->getLabel());
                }

                Notification::success($msg);
            }
        }
    }

    /**
     * @param Pane|NavigationItem  $paneOrHome
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
            'org_name'  => $paneOrHome->getName(),
            'title'     => $title,
            'org_title' => $title
        ]);
    }
}
