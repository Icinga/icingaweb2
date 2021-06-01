<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Exception\InvalidPropertyException;
use Icinga\Exception\ProgrammingError;
use Icinga\Web\Navigation\DashboardHome;
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
    private $paneName;

    /** @var bool A flag whether a new home has been created */
    private $homeCreated = true;

    /**
     * DashletForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * Allow to access private properties
     *
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            $class = get_class($this);
            $message = "Call to undefined property $class::$name";

            throw new InvalidPropertyException($message);
        }

        return $this->$name;
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
        $populatedHome = $this->getPopulatedValue('home', $home);

        $panes = [];
        $dashboardHomes = [];
        $requestPath = Url::fromRequest()->getPath();
        $removeDashlet = 'dashboard/remove-dashlet';
        $updateDashlet = 'dashboard/update-dashlet';

        if ($this->dashboard) {
            $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

            if (empty($home)) {
                $home = current($dashboardHomes);
                $populatedHome = $this->getPopulatedValue('home', $home);
            }

            if ($home === $populatedHome && $this->getPopulatedValue('create_new_home') !== 'y') {
                if (! empty($home)) {
                    $panes = $this->dashboard->getActiveHome()->getPaneKeyTitleArray();
                } else {
                    // This tab was opened from where the home parameter is not present
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

            if ($requestPath === $updateDashlet) {
                $submitLabel = t('Update Dashlet');
                $formTitle = t('Edit Dashlet');
            }

            $this->add(Html::tag('h1', $formTitle));
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

            if ($requestPath === $updateDashlet) {
                if ($home === $populatedHome) {
                    $pane = Url::fromRequest()->getParam('pane');
                    $pane = $this->dashboard->getActiveHome()->getPane($pane);
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
                        $requestPath !== $updateDashlet ? '' :
                            new HtmlElement(
                                'input',
                                [
                                    'class'         => 'btn-primary',
                                    'type'          => 'submit',
                                    'name'          => 'remove_dashlet',
                                    'value'         => t('Remove Dashlet'),
                                    'formaction'    => (string) Url::fromRequest()->setPath('dashboard/remove-dashlet')
                                ]
                            ),
                        new HtmlElement(
                            'input',
                            [
                                'class' => 'btn-primary',
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
        $db = DashboardHome::getConn();
        $username = $this->dashboard->getUser()->getUsername();
        $home = $this->getValue('home');

        // Begin DB transaction
        $db->beginTransaction();

        if (! $this->dashboard->hasHome($home) || /** prevents from failing foreign key constraint */
            ($this->dashboard->getHome($home)->getOwner() === DashboardHome::DEFAULT_IW2_USER &&
            $this->dashboard->getHome($home)->getName() !== DashboardHome::DEFAULT_HOME)) {
            $db->insert('dashboard_home', ['name' => $home, 'label' => $home, 'owner' => $username]);

            $homeId = $db->lastInsertId();
        } else {
            $homeId = $this->dashboard->getHome($home)->getIdentifier();
        }

        if ($this->dashboard->hasHome($home)) {
            $this->dashboard->loadDashboards($home);
        }

        try {
            $this->paneName = $this->getValue('pane');
            $pane = $this->dashboard->getActiveHome()->getPane($this->paneName);
            $paneId = $pane->getPaneId();

            if ($pane->getOwner() === DashboardHome::DEFAULT_IW2_USER) {
                throw new ProgrammingError('User is going to create a dashlet in a system pane.');
            }
        } catch (ProgrammingError $e) {
            $pane = null;
            $type = Pane::PRIVATE;

            $paneName = $this->getValue('pane');
            $paneLabel = $paneName;
            $paneId = DashboardHome::getSHA1($username . $home . $paneName);

            if ($this->dashboard->getActiveHome()->hasPane($paneName)) {
                $tmpPane = $this->dashboard->getActiveHome()->getPane($paneName);
                $paneLabel = $tmpPane->getTitle();

                if ($tmpPane->getOwner() === DashboardHome::DEFAULT_IW2_USER) {
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
            $dashletFound = false;
            if (! empty($pane)) {
                if ($pane->hasDashlet($this->getValue('dashlet'))) {
                    $dashletFound = true;
                }
            }

            if (! $dashletFound) {
                throw new ProgrammingError('Dashlet does not exist');
            }

            Notification::error(t('There already exists a Dashlet with the same name'));
        } catch (ProgrammingError $err) {
            $dashletId = DashboardHome::getSHA1(
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
        $db = DashboardHome::getConn();
        $username = $this->dashboard->getUser()->getUsername();
        $orgHome = $this->dashboard->getHome($this->getValue('org_home'));

        // Original pane and dashlet
        $orgPane = $orgHome->getPane($this->getValue('org_pane'));
        $orgDashlet = $orgPane->getDashlet($this->getValue('org_dashlet'));

        $homeName = $this->getValue('home');

        if (! $orgDashlet->isUserWidget() &&
            ($orgPane->getName() !== $this->getValue('pane') || $orgHome->getName() !== $homeName)) {
            Notification::error(sprintf(
                t('It is not allowed to move system dashlet: "%s"'),
                $this->getValue('org_dashlet')
            ));

            $this->homeCreated = false;

            return;
        }

        // Begin DB transaction
        $db->beginTransaction();

        $createNewHome = true;
        if ($this->dashboard->hasHome($homeName)) {
            $home = $this->dashboard->getHome($homeName);
            $homeId = $home->getIdentifier();

            $createNewHome = false;

            if ($orgHome->getName() !== $home->getName()) {
                $this->dashboard->loadDashboards($homeName);
                $homeId = $this->dashboard->getActiveHome()->getIdentifier();
            }
        }

        $activeHome = $this->dashboard->getActiveHome();
        $paneName = $this->getValue('pane');
        $createNewPane = true;

        if ($activeHome && $activeHome->hasPane($paneName)) {
            $newPane = $activeHome->getPane($paneName);
            $paneId = $newPane->getPaneId();

            $createNewPane = false;
        }

        $dashletUrl = null;
        $dashletLabel = $this->getValue('dashlet');
        $dashletDisabled = $orgDashlet->getDisabled();

        if (! $orgDashlet->getUrl()->matches($this->getValue('url'))) {
            $dashletUrl = $this->getValue('url');
        }

        if ($this->getPopulatedValue('enable_dashlet') === 'y') {
            $dashletDisabled = false;
        }

        if (! $orgDashlet->isUserWidget()) { // System dashlets
            // Since system dashlets can be edited by multiple users, we need to change
            // the original id here so we don't encounter a duplicate key error
            $dashletId = DashboardHome::getSHA1(
                $username . $homeName . $orgDashlet->getPane()->getName() . $orgDashlet->getName()
            );

            $db->insert('dashlet_override', [
                'dashlet_id'    => $dashletId,
                'dashboard_id'  => $paneId,
                'owner'         => $username,
                'label'         => $dashletLabel,
                'url'           => $dashletUrl,
                'disabled'      => (int) $dashletDisabled
            ]);
        } elseif ($orgDashlet->isOverriding()) { // Custom dashelts that overwrites system dashlets
            $db->update('dashlet_override', [
                'label'     => $dashletLabel,
                'url'       => $dashletUrl,
                'disabled'  => (int)$dashletDisabled
            ], [
                'dashlet_id = ?'    => $orgDashlet->getDashletId(),
                'dashboard_id = ?'  => $paneId
            ]);
        } else { // Custom
            if ($createNewHome || ($homeName !== DashboardHome::DEFAULT_HOME &&
                    $activeHome->getOwner() === DashboardHome::DEFAULT_IW2_USER)) {
                $db->insert('dashboard_home', ['name' => $homeName, 'label' => $homeName, 'owner' => $username]);

                $homeId = $db->lastInsertId();
            }

            if ($createNewPane || $activeHome->getPane($paneName)->getOwner() === DashboardHome::DEFAULT_IW2_USER) {
                $paneLabel = $paneName;
                $source = Pane::PRIVATE;
                $paneId = DashboardHome::getSHA1($username . $homeName . $paneName);

                if (! $createNewPane) {
                    $paneLabel = $activeHome->getPane($paneName)->getTitle();
                    $source = Pane::PUBLIC;
                }

                $db->insert('dashboard', [
                    'id'        => $paneId,
                    'home_id'   => $homeId,
                    'name'      => $paneName,
                    'owner'     => $username,
                    'label'     => $paneLabel,
                    'source'    => $source,
                ]);
            }

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
                $activeHome = $this->dashboard->getActiveHome();
                $dashlet = Url::fromRequest()->getParam('dashlet');
                $pane = $activeHome->getPane(Url::fromRequest()->getParam('pane'));

                $dashletId = DashboardHome::getSHA1(
                    $activeHome->getUser()->getUsername() . $activeHome->getName() . $pane->getName() . $dashlet
                );

                $pane->getDashlet($dashlet)->setDashletId($dashletId);
                $pane->removeDashlet($dashlet);

                Notification::success(t('Dashlet has been removed from') . ' ' . $pane->getTitle());
            } else {
                $this->updateDashlet();
            }
        }
    }

    /**
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
}
