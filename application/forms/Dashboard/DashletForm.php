<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Dashlet;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Form to add an url a dashboard pane
 */
class DashletForm extends CompatForm
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    private $homeCreated = false;

    /**
     * Initialize this form
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->setDashboard($dashboard);
    }

    /**
     * Get whether a new dashboard home has been created
     *
     * @return bool
     */
    public function hasBeenHomeCreated()
    {
        return $this->homeCreated;
    }

    /**
     * @inheritDoc
     */
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('remove_dashlet')
                || $this->getPopulatedValue('submit'));
    }

    /**
     * @param \Icinga\Web\Widget\Dashboard $dashboard
     */
    public function setDashboard(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * @return \Icinga\Web\Widget\Dashboard
     */
    public function getDashboard()
    {
        return $this->dashboard;
    }

    /**
     * @param Dashlet $dashlet
     */
    public function load(Dashlet $dashlet)
    {
        $this->populate([
            'pane'          => $dashlet->getPane()->getTitle(),
            'org_pane'      => $dashlet->getPane()->getName(),
            'org_home'      => $dashlet->getPane()->getHome()->getName(),
            'dashlet'       => $dashlet->getTitle(),
            'org_dashlet'   => $dashlet->getName(),
            'url'           => $dashlet->getUrl()->getRelativeUrl()
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function assemble()
    {
        $home = Url::fromRequest()->getParam('home');
        $populatedHome = $this->getPopulatedValue('home', $home);

        $panes = [];
        $dashboardHomes = [];

        $requestPath = Url::fromRequest()->getPath();
        $removeDashlet = DashboardHome::BASE_PATH . '/remove-dashlet';
        $updateDashlet = DashboardHome::BASE_PATH . '/update-dashlet';

        if ($this->dashboard) {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

            if (empty($home)) {
                $home = current($dashboardHomes);
                $populatedHome = $this->getPopulatedValue('home', $home);
            }

            if ($home === $populatedHome && $this->getPopulatedValue('create_new_home') !== 'y') {
                if ($this->dashboard->hasHome($home)) {
                    $panes = $this->dashboard->getActiveHome()->getPaneKeyTitleArray();
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
            $this->add(HtmlElement::create('h1', null, sprintf(
                t('Please confirm removal of dashlet "%s"'),
                Url::fromRequest()->getParam('dashlet')
            )));

            $this->addElement('submit', 'remove_dashlet', ['label' => t('Remove Dashlet')]);
        } else {
            $submitLabel = t('Add To Dashboard');
            $formTitle = t('Add Dashlet To Dashboard');

            if ($requestPath === $updateDashlet) {
                $submitLabel = t('Update Dashlet');
                $formTitle = t('Edit Dashlet');
            }

            $this->add(HtmlElement::create('h1', null, $formTitle));
            $this->addElement('hidden', 'org_pane', ['required'     => false]);
            $this->addElement('hidden', 'org_home', ['required'     => false]);
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
                        'value'         => $home,
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
                HtmlElement::create(
                    'div',
                    ['class' => 'control-group form-controls'],
                    [
                        $requestPath !== $updateDashlet ? '' :
                            HtmlElement::create(
                                'input',
                                [
                                    'class'         => 'btn-primary',
                                    'type'          => 'submit',
                                    'name'          => 'remove_dashlet',
                                    'value'         => t('Remove Dashlet'),
                                    'formaction'    => (string) Url::fromRequest()->setPath(DashboardHome::BASE_PATH . '/remove-dashlet')
                                ]
                            ),
                        HtmlElement::create(
                            'input',
                            [
                                'class'  => 'btn-primary',
                                'type'  => 'submit',
                                'name'  => 'submit',
                                'value' => $submitLabel,
                            ]
                        ),
                    ]
                )
            );
        }
    }

    public function createDashlet()
    {
        $dashboard = $this->getDashboard();
        $db = DashboardHome::getConn();

        $username = $dashboard->getUser()->getUsername();
        $home = $this->getValue('home');

        // Begin DB transaction
        $db->beginTransaction();

        if (! $dashboard->hasHome($home)) {
            $db->insert(DashboardHome::TABLE, ['name' => $home, 'label' => $home, 'owner' => $username]);

            $homeId = $db->lastInsertId();
            $this->homeCreated = true;
        } else {
            $homeId = $dashboard->getHome($home)->getId();

            if ($dashboard->getActiveHome()->getName() !== $home) {
                $dashboard->loadDashboards($home);
            }
        }

        $paneName = $this->getValue('pane');
        $newPaneCreated = false;

        if (! $this->hasBeenHomeCreated() && $dashboard->getActiveHome()->hasPane($paneName)) {
            $paneId = $dashboard->getActiveHome()->getPane($paneName)->getId();
        } else {
            $db->insert(Pane::TABLE, [
                'home_id'   => $homeId,
                'owner'     => $username,
                'name'      => $paneName,
                'label'     => $paneName,
                'source'    => Pane::PRIVATE_DS
            ]);

            $paneId = $db->lastInsertId();
            $newPaneCreated = true;
        }

        $dashletName = $this->getValue('dashlet');
        if (! $newPaneCreated && $dashboard->getActiveHome()->getPane($paneName)->hasDashlet($dashletName)) {
            Notification::error(
                sprintf(t('There is already a dashlet "%s" within this pane.'), $dashletName)
            );
            return;
        } else {
            $db->insert(Dashlet::TABLE, [
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'name'          => $dashletName,
                'label'         => $dashletName,
                'url'           => $this->getValue('url')
            ]);

            $dashletId = $db->lastInsertId();
            $db->insert('dashlet_order', [
                'dashlet_id'    => $dashletId,
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'priority'      => rand(1, 100)
            ]);
        }

        // Commit DB transaction
        $db->commitTransaction();

        Notification::success(t('Dashlet successfully created.'));
    }

    public function updateDashlet()
    {
        $dashboard = $this->getDashboard();
        $username = $dashboard->getUser()->getUsername();
        $db = DashboardHome::getConn();

        $orgHome = $dashboard->getHome($this->getValue('org_home'));
        $orgPane = $orgHome->getPane($this->getValue('org_pane'));
        $orgDashlet = $orgPane->getDashlet($this->getValue('org_dashlet'));

        $homeName = $this->getPopulatedValue('home', $orgHome->getName());
        $homeId = $orgHome->getId();
        $paneId = $orgPane->getId();

        $db->beginTransaction();

        if ($dashboard->hasHome($homeName) && $orgHome->getName() !== $homeName) {
            $dashboard->loadDashboards($homeName);
            $homeId = $dashboard->getHome($homeName)->getId();
        } elseif (! $dashboard->hasHome($homeName)) {
            $db->insert(DashboardHome::TABLE, ['name' => $homeName, 'label' => $homeName, 'owner' => $username]);

            $homeId = $db->lastInsertId();
            $this->homeCreated = true;
        }

        $paneName = $this->getValue('pane', $orgPane->getName());
        $newPaneCreated = false;

        if (! $this->hasBeenHomeCreated() && $dashboard->getActiveHome()->hasPane($paneName)) {
            $paneId = $dashboard->getActiveHome()->getPane($paneName)->getId();
        } elseif (! $this->hasBeenHomeCreated()) {
            $db->insert(Pane::TABLE, [
                'home_id'   => $homeId,
                'name'      => $paneName,
                'owner'     => $username,
                'label'     => $paneName,
                'source'    => Pane::PRIVATE_DS
            ]);

            $paneId = $db->lastInsertId();
            $newPaneCreated = true;
        }

        if (! $newPaneCreated) {
            $pane = $dashboard->getActiveHome()->getPane($paneName);

            if ($pane->hasDashlet($orgDashlet->getName()) && $pane->getName() !== $orgPane->getName()) {
                Notification::error(
                    sprintf(t('There is already a dashlet "%s" within this pane.'), $orgDashlet->getName())
                );
                return;
            }
        }

        $db->update(Dashlet::TABLE, [
            'dashboard_id'  => $paneId,
            'owner'         => $username,
            'name'          => $orgDashlet->getName(),
            'label'         => $this->getValue('dashlet'),
            'url'           => $this->getValue('url')
        ], ['dashlet.id = ?' => $orgDashlet->getId()]);

        // Commit DB transaction
        $db->commitTransaction();

        Notification::success(t('Dashlet successfully update.'));
    }

    public function onSuccess()
    {
        if (Url::fromRequest()->getPath() === DashboardHome::BASE_PATH . '/new-dashlet') {
            $this->createDashlet();
        } elseif ($this->getPopulatedValue('remove_dashlet')) {
            $activeHome = $this->dashboard->getActiveHome();
            $dashlet = Url::fromRequest()->getParam('dashlet');

            $activeHome->getPane(Url::fromRequest()->getParam('pane'))->removeDashlet($dashlet);

            Notification::success(t('Dashlet has been successfully removed.'));
        } else {
            $this->updateDashlet();
        }
    }
}
