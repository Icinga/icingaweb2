<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Html\Html;
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

    /** @var string Name of the pane to be edited */
    public $paneName;

    /**
     * DashletForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->setDashboard($dashboard);
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('remove_dashlet')
                || $this->getPopulatedValue('submit'));
    }

    protected function assemble()
    {
        $home = Url::fromRequest()->getParam('home');
        $populated = $this->getPopulatedValue('home');

        $dashboardHomes = [];
        $panes = [];
        if ($this->dashboard) {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray();
            if (empty($populated) && ! empty($home)) {
                $dashboardHomes = $this->dashboard->switchElementPos($dashboardHomes, $home);
            }

            if (empty($populated) && $this->getPopulatedValue('create_new_home') !== 'y') {
                if (! empty($home)) {
                    $panes = $this->dashboard->getPaneKeyTitleArray();
                } else {
                    // This tab was opened from where the home parameter is not present
                    $firstHome = $this->dashboard->rewindHomes();
                    if (! empty($firstHome)) {
                        $firstHomeId = $firstHome->getAttribute('homeId');

                        $this->dashboard->loadSystemPanesOfTheGivenHome($firstHome->getName());

                        if ($firstHome->getAttribute('owner')) {
                            $this->dashboard->loadUserDashboards($firstHomeId);
                        }

                        $panes = $this->dashboard->getPaneKeyTitleArray($firstHomeId);
                    }
                }
            } else {
                if ($this->dashboard->hasHome($populated)) {
                    $dashboardHome = $this->dashboard->getHome($populated);

                    $this->dashboard->loadSystemPanesOfTheGivenHome($dashboardHome->getName());
                    $this->dashboard->loadSystemDashboards($dashboardHome->getName());

                    if ($dashboardHome->getAttribute('owner')) {
                        $this->dashboard->loadUserDashboards($dashboardHome->getAttribute('homeId'));
                    }

                    $panes = $this->dashboard->getPaneKeyTitleArray($dashboardHome->getAttribute('homeId'));
                }
            }
        }

        if (Url::fromRequest()->getPath() === 'dashboard/remove-dashlet') {
            $this->add(new HtmlElement('h1', null, sprintf(
                t('Please confirm removal of dashlet "%s"'),
                Url::fromRequest()->getParam('dashlet')
            )));
            $this->addElement('submit', 'remove_dashlet', [
                'label' => t('Remove Dashlet'),
            ]);
        } else {
            $submitLabel = t('Add To Dashboard');
            $formTitle = t('Add Dashlet To Dashboard');

            if (Url::fromRequest()->getPath() === 'dashboard/update-dashlet') {
                $submitLabel = t('Update Dashlet');
                $formTitle = t('Edit Dashlet');
            }

            $this->add(Html::tag('h1', $formTitle));
            $this->addElement('hidden', 'org_pane', ['required'     => false]);
            $this->addElement('hidden', 'org_parentId', ['required' => false]);
            $this->addElement('hidden', 'org_dashlet', ['required'  => false]);
            $this->addElement(
                'checkbox',
                'create_new_home',
                [
                    'class'         => 'autosubmit',
                    'disabled'      => empty($dashboardHomes) ?: null,
                    'required'      => false,
                    'label'         => t('New Dashboard Home'),
                    'description'   => t('Check this box if you want to add the dashboard to a new dashboard home.'),
                ]
            );

            $shouldDisable = empty($panes) || $this->getPopulatedValue('create_new_home') === 'y';
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
                if (! empty($panes)) {
                    $shouldDisable = false;
                }

                $this->addElement(
                    'select',
                    'home',
                    [
                        'class'         => 'autosubmit',
                        'required'      => true,
                        'label'         => t('Dashboard Home'),
                        'multiOptions'  => $dashboardHomes,
                        'description'   => t('Select a home you want to add the pane to'),
                    ]
                );
            }

            $this->addElement(
                'checkbox',
                'create_new_pane',
                [
                    'class'         => 'autosubmit',
                    'disabled'      => $shouldDisable ?: null,
                    'required'      => false,
                    'label'         => t('New Dashboard'),
                    'description'   => t('Check this box if you want to add the dashlet to a new dashboard'),
                ]
            );

            if (empty($panes) || $shouldDisable || $this->getPopulatedValue('create_new_pane') === 'y') {
                if ($shouldDisable) {
                    $this->getElement('create_new_pane')->addAttributes(['checked' => 'checked']);
                }

                $this->addElement(
                    'text',
                    'pane',
                    [
                        'required'      => true,
                        'label'         => t('New Dashboard Title'),
                        'description'   => t('Enter a title for the new dashboard'),
                    ]
                );
            } else {
                $this->addElement(
                    'select',
                    'pane',
                    [
                        'required'      => true,
                        'label'         => t('Dashboard'),
                        'multiOptions'  => $panes,
                        'description'   => t('Select a dashboard you want to add the dashlet to'),
                    ]
                );
            }

            $this->add(new HtmlElement('hr'));

            if (Url::fromRequest()->getPath() === 'dashboard/update-dashlet') {
                if ($home === $this->getPopulatedValue('home', reset($dashboardHomes))) {
                    $pane = $this->dashboard->getPane(Url::fromRequest()->getParam('pane'));
                    $dashlet = $pane->getDashlet(Url::fromRequest()->getParam('dashlet'));

                    if ($dashlet->getDisabled()) {
                        $this->addElement(
                            'checkbox',
                            'enable_dashlet',
                            [
                                'label'         => t('Enable Dashlet'),
                                'value'         => 'n',
                                'description'   => t('Check this box if you want to enable this dashlet.')
                            ]
                        );
                    }
                }
            }
            $this->addElement(
                'textarea',
                'url',
                [
                    'required'      => true,
                    'label'         => t('Url'),
                    'description'   => t(
                        'Enter url to be loaded in the dashlet. You can paste the full URL, including filters.'
                    ),
                ]
            );

            $this->addElement(
                'text',
                'dashlet',
                [
                    'required'      => true,
                    'label'         => t('Dashlet Title'),
                    'description'   => t('Enter a title for the dashlet.'),
                ]
            );
            $this->add(
                new HtmlElement(
                    'div',
                    [
                        'class' => 'control-group form-controls',
                        'style' => 'position: relative;  margin-top: 2em;'
                    ],
                    [
                        Url::fromRequest()->getPath() !== 'dashboard/update-dashlet' ? '' :
                            new HtmlElement(
                                'input',
                                [
                                    'class'         => 'btn-primary',
                                    'type'          => 'submit',
                                    'name'          => 'remove_dashlet',
                                    'value'         => t('Remove Dashlet'),
                                    'formaction'    => (string)Url::fromRequest()->setPath('dashboard/remove-dashlet')
                                ]
                            ),
                        new HtmlElement(
                            'input',
                            [
                                'class' => 'btn-primary',
                                'type'  => 'submit',
                                'name'  => 'submit',
                                'value' => $submitLabel
                            ]
                        ),
                    ]
                )
            );
        }
    }

    public function createDashlet()
    {
        $db = $this->dashboard->getConn();
        $username = $this->dashboard->getUser()->getUsername();
        $home = $this->getValue('home');

        // Begin DB transaction
        $db->beginTransaction();

        if (! $this->dashboard->hasHome($home) ||
            ! $this->dashboard->getHome($home)->getAttribute('owner')) {
            $db->insert('dashboard_home', [
                'name'  => $home,
                'owner' => $username
            ]);

            $homeId = $db->lastInsertId();
        } else {
            $homeId = $this->dashboard->getHome($home)->getAttribute('homeId');
        }

        try {
            $this->paneName = $this->getValue('pane');
            $pane = $this->dashboard->getPane($this->paneName);
            $paneId = $pane->getPaneId();

            if ($homeId !== $pane->getParentId()) {
                throw new ProgrammingError('Pane already exist in another home.');
            }

            if (! $pane->getOwner()) {
                throw new ProgrammingError('User is going to create a dashlet in a system pane.');
            }
        } catch (ProgrammingError $e) {
            $pane = null;
            $type = Pane::PRIVATE;

            $paneName = $this->getValue('pane');
            $paneLabel = $paneName;
            $paneId = $this->dashboard->getSHA1($username . $home . $paneName);

            if ($this->dashboard->hasPane($paneName)) {
                $tmpPane = $this->dashboard->getPane($paneName);
                if ($tmpPane->getParentId() === $homeId) {
                    $paneLabel = $tmpPane->getTitle();
                }

                if (! $tmpPane->getOwner()) {
                    $type = Pane::PUBLIC;
                }
            }

            $db->insert('dashboard', [
                'id'        => $paneId,
                'home_id'   => $homeId,
                'owner'     => $username,
                'name'      => $paneName,
                'label'     => $paneLabel,
                'source'    => $type
            ]);
        }

        try {
            if (! empty($pane)) {
                $dashlet = array_filter($pane->getDashlets(), function ($dashlet) {
                    if ($dashlet->getName() === $this->getValue('dashlet')) {
                        return $dashlet;
                    }
                });
            } else {
                $dashlet = null;
            }

            if (empty($dashlet)) {
                throw new ProgrammingError('Dashlet does not exist.');
            }

            Notification::warning(t('There already exists a Dashlet with the same name.'));
        } catch (ProgrammingError $err) {
            $dashletId = $this->dashboard->getSHA1(
                $username . $home . $this->paneName . $this->getValue('dashlet')
            );
            $db->insert('dashlet', [
                'id'            => $dashletId,
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'name'          => $this->getValue('dashlet'),
                'label'         => $this->getValue('dashlet'),
                'url'           => $this->getValue('url')
            ]);

            // Commit DB transaction
            $db->commitTransaction();

            Notification::success(t('Dashlet created'));
        }
    }

    public function updateDashlet()
    {
        $db = $this->dashboard->getConn();
        $username = $this->dashboard->getUser()->getUsername();

        // Original pane and dashlet
        $orgPane = $this->dashboard->getPane($this->getValue('org_pane'));
        $orgDashlet = $orgPane->getDashlet($this->getValue('org_dashlet'));

        if (! $orgDashlet->isUserWidget() && $orgPane->getName() !== $this->getValue('pane')) {
            Notification::warning(sprintf(
                t('It is not allowed to move system dashlet: "%s"'),
                $this->getValue('org_dashlet')
            ));

            return;
        }

        $homeName = $this->getValue('home');
        $insertHomeIntoDB = false;

        if ($this->dashboard->hasHome($homeName)) {
            $home = $this->dashboard->getHome($homeName);
            $homeId = $home->getAttribute('homeId');

            // Home already exists but it doesn't have an owner
            if (! $home->getAttribute('owner')) {
                $insertHomeIntoDB = true;
            }
        } else {
            $insertHomeIntoDB = true;
        }

        // Begin DB transaction
        $db->beginTransaction();

        if ($insertHomeIntoDB) {
            $db->insert('dashboard_home', [
                'name'  => $this->getValue('home'),
                'owner' => $username
            ]);

            $homeId = $db->lastInsertId();
        }

        $this->dashboard->loadUserDashboards($homeId);
        $insertPaneIntoDB = false;
        $isClonedPane = false;

        if ($this->dashboard->hasPane($this->getValue('pane'))) {
            $newPane = $this->dashboard->getPane($this->getValue('pane'));
            $paneId = $newPane->getPaneId();

            // User is going to clone a system pane
            if (! $newPane->getOwner()) {
                $insertPaneIntoDB = true;
                $isClonedPane = true;
            }
        } else {
            $insertPaneIntoDB = true;
        }

        if ($insertPaneIntoDB) {
            $paneId = $this->dashboard->getSHA1($username . $homeName . $this->getValue('pane'));

            $type = Pane::PRIVATE;
            if ($isClonedPane) {
                $type = Pane::PUBLIC;
            }

            $db->insert('dashboard', [
                'id'        => $paneId,
                'home_id'   => $homeId,
                'name'      => $this->getValue('pane'),
                'owner'     => $username,
                'label'     => $this->getValue('pane'),
                'source'    => $type
            ]);
        }

        $dashletUrl = $orgDashlet->getUrl()->getRelativeUrl();
        $dashletLabel = $this->getValue('dashlet');
        $dashletDisabled = $orgDashlet->getDisabled();

        if (! $orgDashlet->getUrl()->matches($this->getValue('url'))) {
            $dashletUrl = $this->getValue('url');
        }

        if ($this->getPopulatedValue('enable_dashlet') === 'y') {
            $dashletDisabled = false;
        }

        if (! $orgDashlet->isUserWidget()) {
            // Since system dashlets can be edited by multiple users, we need to change
            // the original id here so we don't encounter a duplicate key error
            $dashletId = $this->dashboard->getSHA1(
                $username . Dashboard::DEFAULT_HOME . $orgDashlet->getPane()->getName() . $orgDashlet->getName()
            );

            $db->insert('dashlet_override', [
                'dashlet_id'    => $dashletId,
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'label'         => $dashletLabel,
                'url'           => $dashletUrl,
                'disabled'      => (int)$dashletDisabled
            ]);
        } elseif ($orgDashlet->isOverridesSystem()) {
            $db->update('dashlet_override', [
                'label'     => $dashletLabel,
                'url'       => $dashletUrl,
                'disabled'  => (int)$dashletDisabled
            ], [
                'dashlet_id = ?'    => $orgDashlet->getDashletId(),
                'owner = ?'         => $username
            ]);
        } else {
            $db->update('dashlet', [
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'name'          => $orgDashlet->getName(),
                'label'         => $this->getValue('dashlet'),
                'url'           => $this->getValue('url')
            ], ['dashlet.id = ?'  => $orgDashlet->getDashletId()]);
        }

        // Commit DB transaction
        $db->commitTransaction();

        Notification::success(t('Dashlet updated'));
    }

    public function onSuccess()
    {
        if (Url::fromRequest()->getPath() === 'dashboard/new-dashlet') {
            $this->createDashlet();
        } else {
            if ($this->getPopulatedValue('remove_dashlet')) {
                $dashlet = Url::fromRequest()->getParam('dashlet');
                $pane = $this->dashboard->getPane(Url::fromRequest()->getParam('pane'));
                $pane->removeDashlet($dashlet);

                Notification::success(t('Dashlet has been removed from') . ' ' . $pane->getTitle());
            } else {
                $this->updateDashlet();
            }
        }
    }

    /**
     * @param \Icinga\Web\Widget\Dashboard $dashboard
     */
    public function setDashboard(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @param Dashlet $dashlet
     */
    public function load(Dashlet $dashlet)
    {
        $this->populate(array(
            'pane'          => $dashlet->getPane()->getName(),
            'org_pane'      => $dashlet->getPane()->getName(),
            'org_parentId'  => $dashlet->getPane()->getParentId(),
            'dashlet'       => $dashlet->getTitle(),
            'org_dashlet'   => $dashlet->getName(),
            'url'           => $dashlet->getUrl()->getRelativeUrl()
        ));
    }
}
