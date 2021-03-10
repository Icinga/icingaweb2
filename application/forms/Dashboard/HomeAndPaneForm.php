<?php

namespace Icinga\Forms\Dashboard;

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

        $home = Url::fromRequest()->getParam('home');
        $populated = $this->getPopulatedValue('home');
        $dashboardHomes = $this->dashboard->getHomeKeyNameArray();
        if ($populated === null) {
            $dashboardHomes = $this->dashboard->switchElementPos($dashboardHomes, $home);
        }

        $dbTarget = '_main';
        $description = t('Edit the current home name');
        $btnUpdateLabel = t('Update Home');
        $btnRemoveLabel = t('Remove Home');
        $formaction = (string) Url::fromRequest()->setPath($removeHome);

        if ($renamePane === Url::fromRequest()->getPath()) {
            $dbTarget = '_self';
            $description = t('Edit the current pane name');
            $btnUpdateLabel = t('Update Pane');
            $btnRemoveLabel = t('Remove Pane');
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
                        'description'   => t('Select a dashboard home you want to move the dashboard to'),
                    ]
                );
            }

            $pane = $this->dashboard->getPane(Url::fromRequest()->getParam('pane'));
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

        if ($removePane == Url::fromRequest()->getPath()) {
            $btnRemoveLabel = t('Remove Pane');
            $formaction = (string)Url::fromRequest()->setPath($removePane);
        }

        $this->addElement('hidden', 'org_name', ['required' => false]);

        if (Url::fromRequest()->getPath() === 'dashboard/rename-home') {
            if ($this->dashboard->getHome($home)->getAttribute('disabled')) {
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

        $this->addElement(
            'text',
            'name',
            [
                'required'      => true,
                'label'         => t('Name'),
                'description'   => $description
            ]
        );

        if ($renamePane === Url::fromRequest()->getPath()) {
            $this->addElement(
                'text',
                'title',
                [
                    'required'      => true,
                    'label'         => t('Title'),
                    'description'   => t('Edit the current pane title.')
                ]
            );
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
                    $removeHome === Url::fromRequest()->getPath() || $removePane === Url::fromRequest()->getPath() ?'':
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
        $db = $this->dashboard->getConn();
        $requestPath = Url::fromRequest()->getPath();
        $orgHome = Url::fromRequest()->getParam('home');
        $home = $this->dashboard->getHome($orgHome);

        if ($requestPath === 'dashboard/rename-pane' || $requestPath === 'dashboard/remove-pane') {
            // Update the given pane
            $orgPane = $this->getValue('org_name');
            $pane = $this->dashboard->getPane($orgPane);

            if ($this->getPopulatedValue('btn_update')) {
                $newHome = $this->getPopulatedValue('home', $orgHome);
                $orgHomeId = $home->getAttribute('homeId');

                if (! $pane->getOwner() && $orgHome !== $newHome) {
                    Notification::warning(sprintf(
                        t('It is not allowed to move system dashboard: "%s"'),
                        $pane->getTitle()
                    ));

                    return;
                }

                // Begin DB transaction
                $db->beginTransaction();

                if (! $this->dashboard->hasHome($newHome)) {
                    $db->insert('dashboard_home', [
                        'name'  => $newHome,
                        'owner' => $this->dashboard->getUser()->getUsername()
                    ]);

                    $homeId = $db->lastInsertId();
                } elseif ($home->getName() !== $newHome) {
                    $homeId = $this->dashboard->getHome($newHome)->getAttribute('homeId');
                } else {
                    $homeId = $orgHomeId;
                }

                $paneUpdated = false;
                if ($this->getPopulatedValue('enable_pane') === 'y') {
                    $paneUpdated = true;

                    $db->update('dashboard_override', ['disabled' => 0], [
                        'dashboard_id = ?'  => $pane->getPaneId(),
                        'owner = ?'         => $pane->getOwner()
                    ]);
                }

                if (! $paneUpdated) {
                    if ($pane->isOverridesSystem()) {
                        $db->update('dashboard_override', [
                            'home_id'   => $homeId,
                            'label'     => $this->getValue('title'),
                        ], [
                            'dashboard_id = ?' => $pane->getPaneId(),
                            'owner = ?'         => $pane->getOwner()
                        ]);
                    } elseif (! $pane->getOwner() || $pane->getType() === Dashboard\Pane::PUBLIC) {
                        $db->insert('dashboard_override', [
                            'dashboard_id'  => $pane->getPaneId(),
                            'home_id'       => $homeId,
                            'owner'         => $this->dashboard->getUser()->getUsername(),
                            'label'         => $this->getValue('title')
                        ]);
                    } else {
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

                if ($orgHomeId !== $homeId) {
                    $message = sprintf(
                        t('Pane "%s" successfully moved from "%s" to "%s"'),
                        $this->getValue('title'),
                        $home->getName(),
                        $newHome
                    );
                }

                Notification::success($message);
            } else {
                // Remove the given pane and it's dashlets
                $pane->removeDashlets();
                $this->dashboard->removePane($pane->getName());

                Notification::success(t('Dashboard has been removed') . ': ' . $pane->getTitle());
            }
        } else {
            // Update the given dashboard home
            if ($this->getPopulatedValue('btn_update')) {
                if (! $home->getAttribute('owner')) {
                    Notification::warning(sprintf(t('It is not allowed to edit system home: "%s"'), $home->getName()));
                    return;
                }

                $newHome = $this->getValue('name');

                if ($home->getName() !== $newHome && $this->dashboard->hasHome($newHome)) {
                    Notification::warning(sprintf(t('Dashboard home "%s" already exists'), $newHome));

                    return;
                }

                $homeUpdated = false;
                if ($this->getPopulatedValue('enable_home') === 'y') {
                    $homeUpdated = true;

                    $db->update('dashboard_home', ['disabled' => 0], [
                        'id = ?'    => $home->getAttribute('homeId'),
                        'owner = ?' => $home->getAttribute('owner')
                    ]);
                }

                if (! $homeUpdated) {
                    $db->update('dashboard_home', ['name' => $newHome], [
                        'id = ?' => $home->getAttribute('homeId')
                    ]);
                }

                $notificationMsg = sprintf(
                    t('Dashboard home "%s" successfully renamed to "%s".'),
                    $home->getName(),
                    $newHome
                );

                if ($homeUpdated) {
                    $notificationMsg = sprintf(t('Dashboard home "%s" has been successfully enabled.'), $home->getName());
                }

                Notification::success($notificationMsg);
            } else {
                // Remove the given home with it's panes and dashlets
                $this->dashboard->removeHome($home->getName());

                if ($home->getAttribute('owner')) {
                    Notification::success(sprintf(t('Dashboard home has been removed: "%s"'), $orgHome));
                } else {
                    Notification::success(
                        sprintf(t('System dashboard home has been disabled: "%s"'), $home->getName())
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
        if (Url::fromRequest()->getPath() !== 'dashboard/rename-pane') {
            $this->populate([
                'name'  => $paneOrHome->getName(),
                'org_name'  => $paneOrHome->getName()
            ]);
        } else {
            $this->populate([
                'name'      => $paneOrHome->getName(),
                'org_name'  => $paneOrHome->getName(),
                'title'     => $paneOrHome->getTitle(),
                'org_title' => $paneOrHome->getTitle()
            ]);
        }
    }
}
