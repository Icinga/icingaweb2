<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Exception\ProgrammingError;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Dashlet;
use ipl\Html\Html;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Form to add an url a dashboard pane
 */
class DashletForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    /** @var array dashboard pane items name=>title */
    private $panes = [];

    /** @var array dashboard home navigation item name=>NavigationItem */
    private $navigation = [];

    /**
     * DashletForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct($dashboard)
    {
        $this->setDashboard($dashboard);
    }

    protected function assemble()
    {
        $dashboardHomes = [];
        $home = Url::fromRequest()->getParam('home');
        $populated = $this->getPopulatedValue('home');

        if ($this->dashboard) {
            if ($populated === null) {
                $dashboardHomes[$home] = $home;
            }

            foreach ($this->dashboard->getDashboardHomeItems() as $name => $homeItem) {
                $this->navigation[$name] = $homeItem;
                if (! array_key_exists($name, $dashboardHomes)) {
                    $dashboardHomes[$name] = $homeItem->getLabel();
                }
            }

            if ($populated === null && $this->getPopulatedValue('create_new_home') !== 'y') {
                $this->panes = $this->dashboard->getPaneKeyNameArray($this->navigation[$home]->getAttribute('homeId'));
            } else {
                if (array_key_exists($populated, $dashboardHomes)) {
                    $homeId = $this->navigation[$populated]->getAttribute('homeId');
                    if (Url::fromRequest()->getPath() === 'dashboard/update-dashlet') {
                        $this->pane = $this->dashboard->getPane(Url::fromRequest()->getParam('pane'));
                    }

                    $this->dashboard->loadUserDashboardsFromDatabase($homeId);
                    $this->panes = $this->dashboard->getPaneKeyNameArray($homeId);
                }
            }
        }

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

        $shouldDisable = empty($this->panes) || $this->getPopulatedValue('create_new_home') === 'y';
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
                    'description'   => t('Enter a title for the new dashboard'),
                ]
            );
        } else {
            if (! empty($this->panes)) {
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
                    'description'   => t('Select a dashboard home you want to add the dashboard to'),
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

        if (empty($this->panes) || $shouldDisable || $this->getPopulatedValue('create_new_pane') === 'y') {
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
                    'multiOptions'  => $this->panes,
                    'description'   => t('Select a dashboard you want to add the dashlet to'),
                ]
            );
        }

        $this->add(Html::tag('hr'));
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
        $this->addElement('submit', 'submit', ['label' => $submitLabel]);
    }

    public function newAction()
    {
        $db = $this->dashboard->getConn();
        if (! array_key_exists($this->getValue('home'), $this->navigation)) {
            $db->insert('dashboard_home', [
                'name'  => $this->getValue('home'),
                'owner' => $this->dashboard->getUser()->getUsername()
            ]);

            $newParent = $db->lastInsertId();
        } else {
            $newParent = $this->navigation[$this->getValue('home')]->getAttribute('homeId');
        }

        try {
            $pane = $this->dashboard->getPane($this->getValue('pane'));
            $paneId = $pane->getPaneId();

            // If the selected $pane->name should exist in another dashboard home we have to raise
            // an exception so that the new pane with the same name can be created in another home.
            if ($pane->getParentId() !== (int)$newParent) {
                throw new ProgrammingError('Pane parent id does not match with the selected value of home id.');
            }
        } catch (ProgrammingError $e) {
            $db->insert('dashboard', [
                'home_id'   => $newParent,
                'name'      => $this->getValue('pane'),
            ]);

            $paneId = $db->lastInsertId();
        }

        $db->insert('dashlet', [
            'dashboard_id'  => $paneId,
            'owner'         => $this->dashboard->getUser()->getUsername(),
            'name'          => $this->getValue('dashlet'),
            'url'           => $this->getValue('url')
        ]);

        Notification::success(t('Dashlet created'));
    }

    public function updateAction()
    {
        $db = $this->dashboard->getConn();
        $orgParent = (int)$this->getValue('org_parentId');

        if (Url::fromRequest()->getParam('home') === $this->getValue('home')) {
            $newParent = $orgParent;
        } elseif (array_key_exists($this->getValue('home'), $this->navigation)) {
            $newParent = $this->navigation[$this->getValue('home')]->getAttribute('homeId');
        } else {
            $db->insert('dashboard_home', [
                'name'  => $this->getValue('home'),
                'owner' => $this->dashboard->getUser()->getUsername()
            ]);

            $newParent = $db->lastInsertId();
        }

        $pane = $this->dashboard->getPane($this->getValue('org_pane'));
        if ($pane->getParentId() !== $orgParent) {
            $this->dashboard->loadUserDashboardsFromDatabase($orgParent);
            $pane = $this->dashboard->getPane($this->getValue('org_pane'));
        }
        $pane->setTitle($this->getValue('pane'));
        $paneId = $pane->getPaneId();

        $this->dashboard->loadUserDashboardsFromDatabase($newParent);
        if ($this->dashboard->hasPane($this->getValue('pane'))) {
            $newPane = $this->dashboard->getPane($this->getValue('pane'));
            if ($newPane->getParentId() === $newParent) {
                if ($paneId !== $newPane->getPaneId()) {
                    $paneId = $newPane->getPaneId();
                }
            } else {
                $db->insert('dashboard', [
                    'home_id'   => $newParent,
                    'name'      => $this->getValue('pane')
                ]);

                $paneId = $db->lastInsertId();
            }
        } else {
            $db->insert('dashboard', [
                'home_id'   => $newParent,
                'name'      => $this->getValue('pane'),
            ]);

            $paneId = $db->lastInsertId();
        }

        $dashlet = $pane->getDashlet($this->getValue('org_dashlet'));
        $dashlet->setTitle($this->getValue('dashlet'));

        $db->update('dashlet', [
            'dashboard_id'  => $paneId,
            'owner'         => $this->dashboard->getUser()->getUsername(),
            'name'          => $this->getValue('dashlet'),
            'url'           => $this->getValue('url')
        ], ['dashlet.id=?'  => $dashlet->getDashletId()]);

        Notification::success(t('Dashlet updated'));
    }

    public function onSuccess()
    {
        if (Url::fromRequest()->getPath() === 'dashboard/new-dashlet') {
            $this->newAction();
        } else {
            $this->updateAction();
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
